<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bot_engine
 *
 * The conversation engine:
 *  - stores inbound webhook messages
 *  - processes pending messages (called by the cron worker)
 *  - runs the deterministic order state machine
 *  - drives the LLM with business tools (catalog, info, cart, checkout)
 *
 * Conversation states:
 *   idle             - free conversation via LLM
 *   awaiting_confirm - cart finalized, waiting for YES/NO
 *   awaiting_name    - capturing the customer's name for the order
 *   awaiting_address - capturing the delivery address
 *   human            - an admin has taken over the chat (bot paused)
 */
class Bot_engine
{
	protected $settings = array();
	protected $conversation = NULL;
	protected $state = array();
	protected $preview = FALSE; // dry-run mode (landing demo): no sends, no orders, no notifications

	/* ================= Public API ================= */

	public function __construct()
	{
		$CI =& get_instance();
		$CI->load->library('whatsapp_api');
		$CI->load->library('ai_bot');
		$this->whatsapp = $CI->whatsapp_api;
		$this->ai = $CI->ai_bot;
		$this->settings = $CI->settings_model->merged();
	}

	/**
	 * Called by the webhook controller: record an inbound message.
	 * Returns the new message id (or NULL for duplicates).
	 */
	public function handle_webhook_message($wa_id, $name, $body, $wa_message_id, $type = 'text')
	{
		$CI =& get_instance();
		$customer = $CI->customer_model->get_or_create($wa_id, $name);
		$CI->customer_model->touch($customer['id']);
		$conv = $CI->conversation_model->get_or_create($wa_id, $customer['id']);

		if ($type !== 'text')
		{
			$body = '[Received a ' . $type . ' message]';
		}
		return $CI->conversation_model->add_inbound($conv['id'], $wa_id, $body, $wa_message_id, $type);
	}

	/**
	 * Process a single message immediately (called by the webhook for
	 * near-instant replies). Locks the message so the cron worker can't
	 * double-reply; on failure the lock is released and cron retries.
	 */
	public function process_message_inline($message_id)
	{
		$CI =& get_instance();
		if ( ! $message_id)
		{
			return FALSE;
		}

		$msg = $CI->conversation_model->get_message($message_id);
		if ( ! $msg)
		{
			return FALSE;
		}

		$CI->conversation_model->lock_message($message_id);
		try
		{
			$this->_process_message($msg);
			return TRUE;
		}
		catch (Exception $e)
		{
			log_message('error', 'Inline message processing failed: ' . $e->getMessage());
			$CI->conversation_model->unlock_message($message_id);
			return FALSE;
		}
	}

	/**
	 * Process the next batch of pending inbound messages.
	 * Called by the cron worker every minute (safety net / fast poller).
	 * Each message is processed through the same locked path as inline so
	 * overlapping workers can't double-reply.
	 */
	public function process_pending($limit = 10)
	{
		$CI =& get_instance();
		$pending = $CI->conversation_model->pending_messages($limit);
		$processed = 0;
		foreach ($pending as $msg)
		{
			$this->process_message_inline($msg['id']);
			$processed++;
		}
		return $processed;
	}

	/**
	 * Dry-run the bot on a message without sending anything to WhatsApp.
	 * Used by the public landing page demo. Each browser session gets its
	 * own demo conversation (wa_id "demo…") so state persists across taps.
	 *
	 * @param string $body Customer message text.
	 * @return string The reply the bot would send ('' if none).
	 */
	public function preview_reply($body)
	{
		$CI =& get_instance();
		$this->preview = TRUE;

		$sid = function_exists('session_id') ? (string)session_id() : '';
		$wa_id = 'demo' . substr(md5($sid !== '' ? $sid : uniqid('', TRUE)), 0, 10);

		$customer = $CI->customer_model->get_or_create($wa_id, 'Demo Guest');
		$CI->customer_model->touch($customer['id']);
		$conv = $CI->conversation_model->get_or_create($wa_id, $customer['id']);
		$CI->conversation_model->set_bot_active($conv['id'], 1);

		$message_id = $CI->conversation_model->add_inbound($conv['id'], $wa_id, (string)$body, NULL, 'text');
		$msg = $CI->conversation_model->get_message($message_id);

		// Lock the demo message immediately so the cron worker can never pick
		// it up mid-AI-call and send a real WhatsApp message to a demo ID.
		if ($msg)
		{
			$CI->conversation_model->lock_message($msg['id']);
			$msg = $CI->conversation_model->get_message($msg['id']);
		}

		$conv = $CI->conversation_model->get_by_id($conv['id']);
		$reply = $msg ? $this->_run_bot($conv, $msg) : NULL;

		$this->_save_state();
		if ($message_id)
		{
			$CI->conversation_model->mark_processed($message_id);
		}
		$this->preview = FALSE;

		return $reply !== NULL ? (string)$reply : '';
	}

	/* ================= Message processing ================= */

	protected function _process_message(array $msg)
	{
		$CI =& get_instance();
		$conv = $CI->conversation_model->get_by_id($msg['conversation_id']);
		if ( ! $conv)
		{
			$CI->conversation_model->mark_processed($msg['id']);
			return;
		}

		// Never send real WhatsApp messages for landing-demo conversations
		// (wa_id prefix "demo") — belt-and-braces guard on top of the lock.
		if (strpos((string)$msg['wa_id'], 'demo') === 0)
		{
			$CI->conversation_model->mark_processed($msg['id']);
			return;
		}

		// Admin has taken over this conversation; the bot stays silent.
		if ( ! (int)$conv['bot_active'])
		{
			$CI->conversation_model->mark_processed($msg['id']);
			return;
		}

		// Any engine/provider hiccup must never 500 the webhook — degrade to
		// the deterministic reply (or the static fallback) and log it.
		try
		{
			$reply = $this->_run_bot($conv, $msg);
		}
		catch (Throwable $e)
		{
			log_message('error', 'Bot engine exception: ' . $e->getMessage());
			$reply = isset($this->settings['fallback_msg']) && $this->settings['fallback_msg'] !== ''
				? $this->settings['fallback_msg']
				: 'Sorry, I am having trouble right now.';
		}

		if ($reply !== NULL && $reply !== '' && ! $this->preview)
		{
			try
			{
				$sent = $this->whatsapp->send_text($msg['wa_id'], $reply);
			}
			catch (Throwable $e)
			{
				log_message('error', 'WhatsApp send exception: ' . $e->getMessage());
				$sent = FALSE;
			}
			$CI->conversation_model->add_outbound($conv['id'], $msg['wa_id'], $reply, $sent ? 'sent' : 'failed');
		}

		// Tell WhatsApp we've read the message (shows the double blue ticks).
		if ( ! $this->preview && ! empty($msg['wa_message_id']))
		{
			try
			{
				$this->whatsapp->mark_read($msg['wa_message_id']);
			}
			catch (Throwable $e)
			{
				log_message('error', 'WhatsApp mark_read exception: ' . $e->getMessage());
			}
		}

		$this->_save_state();
		$CI->conversation_model->mark_processed($msg['id']);
	}

	/**
	 * Core bot logic: load conversation state and produce a reply.
	 * Never sends messages or persists anything — the caller does that.
	 */
	protected function _run_bot(array $conv, array $msg)
	{
		$this->conversation = $conv;
		$this->state = json_decode((string)$conv['state_data'], TRUE);
		if ( ! is_array($this->state))
		{
			$this->state = array();
		}
		if ( ! isset($this->state['cart']))
		{
			$this->state['cart'] = array();
		}

		$reply = NULL;
		switch ($conv['state'])
		{
			case 'awaiting_confirm':
				$reply = $this->_handle_confirm($msg);
				break;
			case 'awaiting_name':
				$reply = $this->_handle_name($msg);
				break;
			case 'awaiting_address':
				$reply = $this->_handle_address($msg);
				break;
			case 'human':
				// Admin handles; nothing for the bot to do.
				break;
			default:
				if ($this->_is_human_request($msg['body']))
				{
					$reply = $this->_handle_human_request($msg);
				}
				else
				{
					$reply = $this->_handle_llm($msg);
				}
		}
		return $reply;
	}

	protected function _save_state()
	{
		$CI =& get_instance();
		if ($this->conversation !== NULL)
		{
			$CI->conversation_model->update_state($this->conversation['id'], $this->conversation['state'], $this->state);
		}
	}

	/* ================= Order state machine ================= */

	protected function _needs_details()
	{
		// Demo (preview) mode never asks for name/address — confirm completes instantly.
		if ($this->preview)
		{
			return FALSE;
		}
		$collect = isset($this->settings['collect_customer_details']) ? $this->settings['collect_customer_details'] : '1';
		if ($collect !== '1')
		{
			return FALSE;
		}
		$name = isset($this->state['customer_name']) ? trim($this->state['customer_name']) : '';
		$address = isset($this->state['customer_address']) ? trim($this->state['customer_address']) : '';
		return $name === '' || $address === '';
	}

	protected function _handle_confirm(array $msg)
	{
		$body = mb_strtolower(trim($msg['body']));

		if (preg_match('/^(no|nope|cancel|not now|forget it|never mind)\b/', $body))
		{
			$this->state['cart'] = array();
			$this->conversation['state'] = 'idle';
			return "No problem! I've cleared your cart. 😊 Is there anything else I can help you with?";
		}

		if (preg_match('/^(yes|yep|yeah|yup|ok|okay|sure|confirm|place the order|go ahead|do it|order it|that\'?s right|correct)\b/', $body))
		{
			if ($this->_needs_details())
			{
				$this->conversation['state'] = 'awaiting_name';
				return "Great! 🎉 What name should the order be under?";
			}
			return $this->_finalize_order();
		}

		return "Should I go ahead and place your order? Just reply YES or NO.";
	}

	protected function _handle_name(array $msg)
	{
		$this->state['customer_name'] = trim($msg['body']);
		$this->conversation['state'] = 'awaiting_address';
		return 'Thanks, ' . trim($msg['body']) . "! Where should we deliver or send it?";
	}

	protected function _handle_address(array $msg)
	{
		$this->state['customer_address'] = trim($msg['body']);
		return $this->_finalize_order();
	}

	protected function _finalize_order()
	{
		$CI =& get_instance();
		$cart = isset($this->state['cart']) ? $this->state['cart'] : array();
		if ( ! $cart)
		{
			$this->conversation['state'] = 'idle';
			return 'Your cart is empty. Would you like to add something? 😊';
		}

		$items = array();
		$total = 0.0;
		foreach ($cart as $entry)
		{
			$qty = (int)$entry['qty'];
			$price = (float)$entry['price'];
			$items[] = array(
				'name'     => (string)$entry['name'],
				'price'    => $price,
				'quantity' => $qty,
			);
			$total += $price * $qty;
		}

		// Demo (preview) mode: simulate confirmation without touching the DB.
		if ($this->preview)
		{
			$this->state['cart'] = array();
			$this->conversation['state'] = 'idle';
			return "✅ Order confirmed!\n\n" . $this->_format_items($items)
				. "\nTotal: " . $this->_currency() . money_fmt($total)
				. "\n\nThis is a live preview — no real order was placed.";
		}

		$order_id = $CI->order_model->create(array(
			'customer_id'      => $this->conversation['customer_id'],
			'wa_id'            => $this->conversation['wa_id'],
			'customer_name'    => isset($this->state['customer_name']) ? $this->state['customer_name'] : NULL,
			'customer_address' => isset($this->state['customer_address']) ? $this->state['customer_address'] : NULL,
			'items_json'       => json_encode($items),
			'total'            => $total,
			'status'           => 'placed',
		));

		$this->state['cart'] = array();
		$this->conversation['state'] = 'idle';

		$this->_notify_owner($order_id, $items, $total);

		$reply = "✅ Order #" . $order_id . " confirmed!\n\n" . $this->_format_items($items)
			. "\nTotal: " . $this->_currency() . money_fmt($total)
			. "\n\nWe'll get it ready for you. Thank you! 🙌";
		return $reply;
	}

	protected function _notify_owner($order_id, array $items, $total)
	{
		if ($this->preview)
		{
			return;
		}
		$owner = isset($this->settings['owner_wa_id']) ? $this->settings['owner_wa_id'] : '';
		if ($owner === '')
		{
			return;
		}
		$text = "🛎️ NEW ORDER #" . $order_id . "\n\n" . $this->_format_items($items)
			. "\nTotal: " . $this->_currency() . money_fmt($total);
		if (isset($this->state['customer_name']) && $this->state['customer_name'] !== '')
		{
			$text .= "\nName: " . $this->state['customer_name'];
		}
		if (isset($this->state['customer_address']) && $this->state['customer_address'] !== '')
		{
			$text .= "\nAddress: " . $this->state['customer_address'];
		}
		$this->whatsapp->send_text($owner, $text);
	}

	protected function _notify_owner_human()
	{
		if ($this->preview)
		{
			return;
		}
		$owner = isset($this->settings['owner_wa_id']) ? $this->settings['owner_wa_id'] : '';
		if ($owner === '')
		{
			return;
		}
		$text = "👤 A customer (" . $this->conversation['wa_id'] . ") asked to speak with a human.\nReply from the admin panel: " . site_url('admin/chats/view/' . $this->conversation['id']);
		$this->whatsapp->send_text($owner, $text);
	}

	protected function _handle_human_request(array $msg)
	{
		$CI =& get_instance();
		$this->conversation['bot_active'] = 0;
		$this->conversation['state'] = 'human';
		$CI->conversation_model->set_bot_active($this->conversation['id'], 0);
		$this->_notify_owner_human();
		return "Sure! One of our staff members will reply to you here shortly. 🙌";
	}

	protected function _is_human_request($body)
	{
		$body = mb_strtolower(trim((string)$body));
		return (bool)preg_match('/\b(human|real person|talk to someone|speak to someone|staff member|representative|agent)\b/', $body);
	}

	/* ================= LLM handling ================= */

	protected function _handle_llm(array $msg)
	{
		$CI =& get_instance();
		if ( ! $this->ai->is_configured())
		{
			return $this->_handle_offline($msg);
		}

		$system = $this->_system_prompt();
		$history = $CI->conversation_model->recent_messages($this->conversation['id'], 12);
		$messages = array_merge(array(array('role' => 'system', 'content' => $system)), $history);

		$result = NULL;
		try
		{
			$result = $this->ai->chat($messages, $this->_tools(), array($this, '_execute_tool'));
		}
		catch (Throwable $e)
		{
			log_message('error', 'AI chat exception: ' . $e->getMessage());
			$result = NULL;
		}

		// AI unavailable, errored or returned nothing — serve the built-in
		// deterministic brain so the bot stays useful (menu, hours, cart,
		// checkout, knowledge) even with no AI key or a provider outage.
		if ( ! $result || ! isset($result['content']) || trim($result['content']) === '')
		{
			return $this->_handle_offline($msg);
		}
		return $result['content'];
	}

	/* ================= Deterministic offline brain ================= */

	/**
	 * Answers common requests entirely in PHP — no AI needed. Kicks in when
	 * the AI is unconfigured or fails, so the bot keeps working (menu,
	 * hours, delivery, cart and even a full checkout) without any AI key.
	 * Mirrors the capabilities of the old IRIS chatbot and exceeds them.
	 */
	protected function _handle_offline(array $msg)
	{
		$body = trim((string)$msg['body']);
		$low = mb_strtolower($body);
		$lang = $this->_detect_lang($low);   // 'en' | 'ur' | 'hi'
		$urdu = ($lang === 'ur');
		$hindi = ($lang === 'hi');
		$tail = $this->_local_tail($lang);

		// 1. Greeting / opener.
		// NOTE: Devanagari/Urdu words often end in combining marks (्, े, ो…)
		// which are NOT \w chars, so \b never fires after them — use an
		// end-of-text/space lookahead for the script words instead.
		if (preg_match('/^(hi+|hello+|hey+|salam|assalam\s*ualaikum|aoa|good\s*(morning|afternoon|evening)|hii+|hiii+|yo)\b|[\p{L}\p{N}]*\b(assalam|salam|hello|hi)\b|(?:नमस्ते|हैलो|हाय|سلام|ہیلو|السلام)(?=$|\s)/iu', $body) && mb_strlen($body) < 40)
		{
			return $this->_offline_greeting($lang);
		}

		// 2. Help / what can you do
		if ($this->_contains_any($low, array('help', 'what can you do', 'options', 'kya kar', 'kya karte', 'kaise', 'how do', 'how can', 'guide', 'start', 'madad', 'मदद', 'مدد', 'क्या कर')))
		{
			return $this->_offline_help($lang);
		}

		// 3. Cart actions (do these before generic menu matches)
		$cart_reply = $this->_offline_cart_reply($low, $body, $lang);
		if ($cart_reply !== NULL)
		{
			return $cart_reply;
		}

		// 4. Checkout request (empty cart is handled inside)
		if ($this->_contains_any($low, array('checkout', 'place order', 'place my order', 'confirm order', 'order now', 'order karo', 'order de', 'buy now', 'complete my order', 'submit order', 'चेकआउट', 'چیک آؤٹ', 'ऑर्डर करो', 'آرڈر کرو')))
		{
			return $this->_tool_checkout() . $tail;
		}

		// 5. Business facts
		if ($this->_contains_any($low, array('hours', 'timing', 'timings', 'open', 'close', 'closed', 'khula', 'kab khol', 'kab tak', 'time', 'समय', 'وقت', 'कितने बजे', 'کتنے بجے', 'खुला', 'کھلا', 'खुली', 'کھلی', 'बंद', 'بند')) && $this->_contains_any($low, array('hour', 'time', 'timing', 'open', 'close', 'khul', 'kab', 'समय', 'وقت', 'बजे', 'بجے', 'खुल', 'کھل')))
		{
			return $this->_business_line('hours') . $tail;
		}
		if ($this->_contains_any($low, array('address', 'location', 'where are you', 'kahan', 'pata', 'address kya', 'पता', 'پتہ', 'कहाँ', 'کہاں')))
		{
			return $this->_business_line('address') . $tail;
		}
		if ($this->_contains_any($low, array('phone', 'number', 'contact', 'call you', 'rabta', 'call kar', 'फ़ोन', 'फोन', 'فون', 'नंबर', 'نمبر')))
		{
			return $this->_business_line('phone') . $tail;
		}
		if ($this->_contains_any($low, array('delivery', 'deliver', 'shipping', 'home delivery', 'deliver karte', 'डिलीवरी', 'ڈیلیوری')))
		{
			return $this->_business_line('delivery') . $tail;
		}

		// 6. A specific item (price + description)
		$item_reply = $this->_offline_item_reply($low, $lang);
		if ($item_reply !== NULL)
		{
			return $item_reply;
		}

		// 7. Menu (full or by category)
		if ($this->_contains_any($low, array('menu', 'catalog', 'products', 'prices', 'price list', 'list', 'dikhao', 'dikha', 'dikhay', 'show me', 'what do you have', 'kya hai', 'kya kuch', 'items', 'मेनू', 'مینو', 'दिखाओ', 'दिखा', 'دکھاؤ', 'دکھا')))
		{
			return $this->_offline_menu_reply($body, $lang);
		}

		// 8. Knowledge base / business document (search the owner's data)
		$knowledge = $this->_tool_knowledge($body);
		if (strpos($knowledge, 'No knowledge base entries matched') !== 0)
		{
			return $knowledge . $tail;
		}
		$doc = $this->_tool_document_search($body);
		if (strpos($doc, 'No match found in the business document') !== 0 && strpos($doc, 'The business document is empty') !== 0)
		{
			return $doc . $tail;
		}

		// 9. Nothing matched — friendly fallback with directions.
		$fallback = isset($this->settings['fallback_msg']) && $this->settings['fallback_msg'] !== ''
			? $this->settings['fallback_msg']
			: 'Sorry, I am having trouble right now. A staff member will get back to you shortly.';
		return $fallback . "\n\n💡 Try \"menu\", \"hours\" or \"add 1x [item name]\" — or reply \"human\" to talk to a staff member."
			. ($urdu ? "\n\nآپ \"menu\"، \"hours\" لکھ سکتے ہیں یا \"human\" لکھ کر عملے سے بات کر سکتے ہیں۔" : '')
			. ($hindi ? "\n\nआप \"menu\", \"hours\" लिख सकते हैं या \"human\" लिखकर कर्मचारी से बात कर सकते हैं।" : '');
	}

	protected function _offline_greeting($lang)
	{
		$name = $this->_business_name();
		$first = $this->_first_menu_name();
		$greeting = "Hello! 👋 Welcome to {$name}!\n\nI can show you the menu and prices, tell you our hours and delivery info, and take your order right here. Just tell me what you need — e.g. \"menu\" or \"add 1x {$first}\".";
		if ($lang === 'ur')
		{
			$greeting .= "\n\nآپ مینو دیکھ سکتے ہیں، اوقات معلوم کر سکتے ہیں، اور یہیں آرڈر دے سکتے ہیں۔ مثلاً لکھیں: \"menu\" یا \"add 1x {$first}\"۔";
		}
		elseif ($lang === 'hi')
		{
			$greeting .= "\n\nआप मेनू देख सकते हैं, समय जान सकते हैं, और यहीं ऑर्डर दे सकते हैं। जैसे लिखें: \"menu\" या \"add 1x {$first}\"।";
		}
		return $greeting;
	}

	protected function _offline_help($lang)
	{
		$out = "Here's what I can do for you:\n\n"
			. "• 📋 Menu & prices — type \"menu\"\n"
			. "• 🕒 Hours — type \"hours\"\n"
			. "• 🛵 Delivery info — type \"delivery\"\n"
			. "• 🛒 Order — type an item, e.g. \"add 1x " . $this->_first_menu_name() . "\", then \"checkout\"\n"
			. "• 👤 Talk to a human — type \"human\"";
		if ($lang === 'ur')
		{
			$out .= "\n\nمینو کے لیے \"menu\"، اوقات کے لیے \"hours\"، ڈیلیوری کے لیے \"delivery\" اور آرڈر کے لیے آئٹم کا نام لکھیں۔";
		}
		elseif ($lang === 'hi')
		{
			$out .= "\n\nमेनू के लिए \"menu\", समय के लिए \"hours\", डिलीवरी के लिए \"delivery\" और ऑर्डर के लिए आइटम का नाम लिखें।";
		}
		return $out;
	}

	protected function _offline_menu_reply($body, $lang)
	{
		$CI =& get_instance();
		$category = '';
		// Try to match a category name in the request.
		$cats = $CI->menu_model->get_categories();
		foreach ($cats as $cat)
		{
			if ($cat['name'] !== '' && mb_stripos($body, $cat['name']) !== FALSE)
			{
				$category = $cat['name'];
				break;
			}
		}
		$menu = $this->_tool_get_menu($category);
		if (strpos($menu, 'The catalog is currently empty') === 0)
		{
			return "The catalog is empty right now. Please check back later."
				. ($lang === 'ur' ? "\n\nابھی مینو خالی ہے، بعد میں دیکھیں۔" : '')
				. ($lang === 'hi' ? "\n\nअभी मेनू खाली है, बाद में देखें।" : '');
		}
		$head = $category !== '' ? "Here's our {$category}:\n\n" : "Here's what we offer:\n\n";
		return $head . $menu . "\n\nTo order, tell me what you want, e.g. \"add 1x " . $this->_first_menu_name() . "\"."
			. ($lang === 'ur' ? "\n\nآرڈر کرنے کے لیے لکھیں: \"add 1x [item name]\"۔" : '')
			. ($lang === 'hi' ? "\n\nऑर्डर करने के लिए लिखें: \"add 1x [item name]\"।" : '');
	}

	protected function _offline_item_reply($low, $lang)
	{
		$CI =& get_instance();
		$items = $CI->menu_model->get_items(TRUE);
		foreach ($items as $item)
		{
			$name = (string)$item['name'];
			if ($name !== '' && mb_strlen($name) >= 3 && mb_stripos($low, mb_strtolower($name)) !== FALSE)
			{
				$out = $this->_tool_get_item($name);
				if (strpos($out, 'Item not found') !== 0)
				{
					if ($lang === 'ur')
					{
						return $out . "\n\nاسے آرڈر کرنے کے لیے لکھیں: \"add 1x {$name}\"۔";
					}
					if ($lang === 'hi')
					{
						return $out . "\n\nइसे ऑर्डर करने के लिए लिखें: \"add 1x {$name}\"।";
					}
					return $out . "\n\nTo order, type: \"add 1x {$name}\".";
				}
			}
		}
		return NULL;
	}

	protected function _offline_cart_reply($low, $body, $lang)
	{
		// Clear cart first, so "clear cart" is never caught by the bare "cart" trigger below
		if ($this->_contains_any($low, array('clear cart', 'empty cart', 'clear my cart', 'remove all', 'cart hata', 'कार्ट खाली', 'کارٹ خالی')))
		{
			return $this->_tool_cart_clear()
				. ($lang === 'ur' ? "\n\nآپ کی کارٹ خالی کر دی گئی ہے۔" : '')
				. ($lang === 'hi' ? "\n\nआपकी कार्ट खाली कर दी गई है।" : '');
		}
		// Show cart (bare "cart" included)
		if ($this->_contains_any($low, array('cart', 'show cart', 'my cart', 'my order', 'basket', 'cart dikhao', 'kya cart', 'cart kya', 'order summary', 'see cart', 'कार्ट', 'کارٹ', 'मेरा ऑर्डर', 'میرا آرڈر')))
		{
			if ( ! $this->state['cart'])
			{
				return "Your cart is empty. Add something first, e.g. \"add 1x " . $this->_first_menu_name() . "\"."
					. ($lang === 'ur' ? "\n\nآپ کی کارٹ خالی ہے۔ پہلے کچھ شامل کریں۔" : '')
					. ($lang === 'hi' ? "\n\nआपकी कार्ट खाली है। पहले कुछ जोड़ें।" : '');
			}
			return "Your cart:\n" . $this->_format_cart() . "\nTotal: " . $this->_currency() . money_fmt($this->_cart_total())
				. "\n\nReply \"checkout\" to place this order, or \"clear cart\" to empty it."
				. ($lang === 'ur' ? "\n\nآرڈر کے لیے \"checkout\" لکھیں۔" : '')
				. ($lang === 'hi' ? "\n\nऑर्डर के लिए \"checkout\" लिखें।" : '');
		}
		// Add item (also catches "i want X", "give me X", "X add karo")
		if ($this->_contains_any($low, array('add', 'want', 'give me', 'i want', 'order', 'karo', 'karo.', 'lana', 'chahiye', 'चाहिए', 'چاہیے', 'लाना', 'لانا')))
		{
			$CI =& get_instance();
			$items = $CI->menu_model->get_items(TRUE);
			foreach ($items as $item)
			{
				$name = (string)$item['name'];
				if ($name === '' || mb_strlen($name) < 3)
				{
					continue;
				}
				if (mb_stripos($low, mb_strtolower($name)) !== FALSE)
				{
					$qty = 1;
					// Quantity right before the item: "2 chicken biryani", "2x biryani", "2 of biryani"
					$pos = mb_stripos($low, mb_strtolower($name));
					$before = mb_substr($low, 0, $pos);
					if (preg_match('/(\d+)\s*(?:[xX×]|of|nos?\.?)?\s*$/', $before, $qm))
					{
						$qty = (int)$qm[1];
					}
					$result = $this->_tool_cart_add($name, $qty);
					if ($lang === 'ur')
					{
						return $result . "\n\nآرڈر مکمل کرنے کے لیے \"checkout\" لکھیں۔";
					}
					if ($lang === 'hi')
					{
						return $result . "\n\nऑर्डर पूरा करने के लिए \"checkout\" लिखें।";
					}
					return $result . "\n\nReady to order? Reply \"checkout\".";
				}
			}
		}
		return NULL;
	}

	protected function _business_line($key)
	{
		$s = $this->settings;
		$skey = array(
			'hours'    => 'business_hours',
			'address'  => 'business_address',
			'phone'    => 'business_phone',
			'delivery' => 'delivery_info',
		)[$key];
		$val = isset($s[$skey]) ? trim((string)$s[$skey]) : '';

		// If the field is empty or still the demo seed, try the business
		// document — the owner uploaded the real details there.
		if ($val === '' || $this->_is_dummy($skey, $val))
		{
			$val = $this->_doc_fact($skey);
		}
		if ($val === '')
		{
			$val = 'please contact us for ' . $key . ' details.';
		}

		$prefixes = array(
			'hours'    => '🕒 Our hours: ',
			'address'  => '📍 Our address: ',
			'phone'    => '📞 Call us: ',
			'delivery' => '🛵 ',
		);
		return $prefixes[$key] . $val;
	}

	protected function _first_menu_name()
	{
		$CI =& get_instance();
		$items = $CI->menu_model->get_items(TRUE);
		return $items ? $items[0]['name'] : 'an item';
	}

	/**
	 * Detect the customer's language: 'en' (English), 'ur' (Urdu — Arabic
	 * script or roman Urdu) or 'hi' (Hindi — Devanagari script or roman
	 * Hindi). Script detection is unambiguous; roman Urdu/Hindi share nearly
	 * all markers, so roman text with 2+ markers is treated as Urdu (which
	 * roman-Hindi speakers also read fine).
	 */
	protected function _detect_lang($low)
	{
		// 1. Script detection — a single Arabic-script char is Urdu,
		//    a single Devanagari char is Hindi.
		if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $low))
		{
			return 'ur';
		}
		if (preg_match('/[\x{0900}-\x{097F}]/u', $low))
		{
			return 'hi';
		}
		// 2. Roman Urdu / Hindi markers (both share these)
		$markers = array(
			'assalam', 'salam', 'aoa', 'batao', 'bata', 'chaiye', 'chahye', 'chahiye',
			'kya', 'hai', 'nahi', 'mujhe', 'karo', 'kar', 'dikhao', 'dikha',
			'kahan', 'pata', 'rabta', 'aap', 'kaise', 'kab', 'khana', 'paisa',
			'kitna', 'kitne', 'chahte', 'chaho', 'dena', 'kharid', 'biryani', 'chawal',
		);
		$count = 0;
		foreach ($markers as $m)
		{
			if ($m !== '' && mb_strpos($low, $m) !== FALSE)
			{
				$count++;
			}
		}
		return $count >= 2 ? 'ur' : 'en';
	}

	/**
	 * Closing line in the customer's language ('' for English).
	 */
	protected function _local_tail($lang)
	{
		if ($lang === 'ur')
		{
			return "\n\nمزید معلومات کے لیے کوئی سوال پوچھیں یا \"human\" لکھ کر عملے سے بات کریں۔";
		}
		if ($lang === 'hi')
		{
			return "\n\nअधिक जानकारी के लिए कोई सवाल पूछें या \"human\" लिखकर कर्मचारी से बात करें।";
		}
		return '';
	}

	/**
	 * Is this still the demo seed value for that setting?
	 */
	protected function _is_dummy($skey, $val)
	{
		$dummy = array(
			'business_name'    => 'Your Business',
			'business_address' => '123 Main Street, Your City',
			'business_phone'   => '+15551234567',
			'business_hours'   => 'Mon–Sun: 11:00 – 22:00',
			'delivery_info'    => 'We offer delivery and pickup. Ask us about delivery times and fees for your area.',
		);
		return (isset($dummy[$skey]) && $val === $dummy[$skey])
			|| preg_match('/^[\*\-:•|\s]+$/', $val); // junk leftover (e.g. "*")
	}

	/**
	 * Fall back to the business document for a quick fact when the dedicated
	 * setting is empty/dummy. Compact extraction — the panel's auto-fill
	 * does the full job on save; this is a safety net for pre-filled docs.
	 */
	protected function _doc_fact($skey)
	{
		$doc = isset($this->settings['business_document']) ? (string)$this->settings['business_document'] : '';
		if (trim($doc) === '')
		{
			return '';
		}
		$lines = preg_split('/\R/', $doc);
		$lines = array_map('trim', $lines);

		if ($skey === 'business_name')
		{
			foreach ($lines as $i => $line)
			{
				if ($line === '') continue;
				if (preg_match('/^#+\s*(.+)$/', $line, $m)) return $this->_clean_fact($m[1]);
				if (preg_match('/we\s+are\s+([^\.,!]+)/i', $line, $m)) return $this->_clean_fact($m[1]);
				if ($i === 0 && mb_strlen($line) < 80) return $this->_clean_fact($line);
			}
		}

		if ($skey === 'business_hours')
		{
			$out = array();
			foreach ($lines as $line)
			{
				if (preg_match('/\b(mon|tue|wed|thu|fri|sat|sun|monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', $line)
					&& preg_match('/\b\d{1,2}(:\d{2})?\s*(am|pm|noon|midnight)\b/i', $line))
				{
					$out[] = $this->_clean_fact($line);
				}
			}
			return $out ? mb_substr(implode('; ', array_slice($out, 0, 10)), 0, 500) : '';
		}

		if ($skey === 'business_address')
		{
			foreach ($lines as $i => $line)
			{
				if (preg_match('/\baddress\s*[:\-]\s*(.+)$/i', $line, $m))
				{
					$v = $this->_clean_fact($m[1]);
					if ($this->_fact_usable($v)) return $v;
					for ($j = $i + 1; $j < count($lines) && $j <= $i + 3; $j++)
					{
						$v2 = $this->_clean_fact($lines[$j]);
						if ($this->_fact_usable($v2)) return $v2;
					}
				}
			}
			foreach ($lines as $line)
			{
				if (preg_match('/\b\d+\s+[A-Za-z].*(street|road|boulevard|avenue|lane|main)\b/i', $line)
					|| preg_match('/\b(lahore|karachi|islamabad|rawalpindi|multan|faisalabad|peshawar|quetta)\b/i', $line))
				{
					$v = $this->_clean_fact($line);
					if ($this->_fact_usable($v)) return $v;
				}
			}
		}

		if ($skey === 'business_phone')
		{
			foreach ($lines as $line)
			{
				if (preg_match('/\b(phone|whatsapp|contact|call)\s*[:\-]/i', $line) && preg_match('/\+?\d[\d\s\-]{8,}/', $line, $m))
				{
					return $this->_clean_fact($m[0]);
				}
			}
			foreach ($lines as $line)
			{
				if (preg_match('/\+\d[\d\s\-]{8,}/', $line, $m)) return $this->_clean_fact($m[0]);
			}
		}

		if ($skey === 'delivery_info')
		{
			$in = FALSE;
			$bits = array();
			foreach ($lines as $line)
			{
				if (preg_match('/^#+\s*.*(deliver|shipping|home delivery)/i', $line)) { $in = TRUE; continue; }
				if ($in)
				{
					if (preg_match('/^#+/', $line)) break;
					if ($line !== '' && mb_strlen($line) < 200) $bits[] = $this->_clean_fact($line);
				}
			}
			return $bits ? mb_substr(implode(' ', array_slice($bits, 0, 6)), 0, 600) : '';
		}

		return '';
	}

	protected function _clean_fact($s)
	{
		$s = trim((string)$s);
		$s = preg_replace('/\*\*(.+?)\*\*/', '$1', $s);
		$s = str_replace('**', '', $s);
		$s = preg_replace('/^[-*•]\s*/', '', $s);
		$s = preg_replace('/\s+/', ' ', $s);
		return trim($s);
	}

	protected function _fact_usable($v)
	{
		return $v !== '' && mb_strlen($v) >= 4 && ! preg_match('/^[\*\-:•|\s]+$/', $v);
	}

	/**
	 * Business name for greetings — prefers the setting, falls back to the
	 * document, then a generic label.
	 */
	protected function _business_name()
	{
		$name = isset($this->settings['business_name']) ? trim((string)$this->settings['business_name']) : '';
		if ($name === '' || $this->_is_dummy('business_name', $name))
		{
			$doc = $this->_doc_fact('business_name');
			if ($doc !== '') $name = $doc;
		}
		return $name !== '' ? $name : 'our business';
	}

	/**
	 * Currency symbol from settings (defaults to $) — shown before prices in
	 * the menu, cart and order totals.
	 */
	protected function _currency()
	{
		$cur = isset($this->settings['currency_symbol']) ? trim((string)$this->settings['currency_symbol']) : '';
		return $cur !== '' ? $cur : '$';
	}

	protected function _contains_any($low, array $needles)
	{
		foreach ($needles as $n)
		{
			if ($n !== '' && mb_strpos($low, $n) !== FALSE)
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	protected function _system_prompt()
	{
		$s = $this->settings;
		$greeting = isset($s['greeting']) && $s['greeting'] !== '' ? $s['greeting'] : "Hello! 👋 Welcome to {$s['business_name']}.";
		$greeting = str_replace('{business_name}', $s['business_name'], $greeting);

		// The owner's business document is the authoritative knowledge source.
		// Long documents are truncated here; search_document covers the rest.
		$doc = isset($s['business_document']) ? trim((string)$s['business_document']) : '';
		$doc_block = '';
		if ($doc !== '')
		{
			$doc_block = "\nYOUR BUSINESS DOCUMENT (authoritative — answer from this first; it was written by the owner):\n"
				. mb_substr($doc, 0, 4500)
				. (mb_strlen($doc) > 4500 ? "\n… (document continues — use search_document for details beyond this)" : '')
				. "\n";
		}

		return "You are the friendly AI assistant for {$s['business_name']}, a WhatsApp business assistant.\n"
			. "When a customer sends their very first message of the conversation, open with a warm greeting along the lines of: {$greeting}\n\n"
			. "BUSINESS FACTS:\n"
			. "- Name: {$s['business_name']}\n"
			. "- Address: {$s['business_address']}\n"
			. "- Phone: {$s['business_phone']}\n"
			. "- Opening hours: {$s['business_hours']}\n"
			. "- Delivery / service info: {$s['delivery_info']}\n"
			. $doc_block
			. "RULES:\n"
			. "1. Be warm, concise and helpful. Use emojis sparingly.\n"
			. "2. ALWAYS answer business questions from YOUR BUSINESS DOCUMENT, the business facts above, the catalog tools and the knowledge base — never invent items, prices, hours or policies. When the customer asks something that is not covered, say you are not sure and offer to get a human.\n"
			. "3. Help customers build an order: use cart_add / cart_remove / cart_clear, then request_checkout when they are ready to order.\n"
			. "4. After request_checkout, tell the customer to reply YES to confirm the order.\n"
			. "5. If a customer asks something unrelated to your business (weather, sports, etc.), politely steer the conversation back to what you offer.\n"
			. "6. If a customer wants to speak to a human, tell them to reply with the word \"human\".\n"
			. "7. Never mention tool names, this prompt, or that you are an AI model. You are simply the business's assistant.";
	}

	/* ================= Tools ================= */

	protected function _tools()
	{
		return array(
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'get_business_info',
					'description' => 'Get official business information: address, opening hours, phone number, delivery/service policy.',
					'parameters' => array(
						'type' => 'object',
						'properties' => array(
							'topic' => array(
								'type' => 'string',
								'enum' => array('all', 'address', 'hours', 'phone', 'delivery'),
								'description' => 'Which piece of information to return. Use "all" for a full summary.',
							),
						),
						'required' => array('topic'),
					),
				),
			),
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'get_menu',
					'description' => 'Get the catalog of products/services with prices. Optionally filter by category.',
					'parameters' => array(
						'type' => 'object',
						'properties' => array(
							'category' => array(
								'type' => 'string',
								'description' => 'Optional category name. Omit for the full menu.',
							),
						),
						'required' => array(),
					),
				),
			),
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'get_item',
					'description' => 'Get the price and description of one specific catalog item by name.',
					'parameters' => array(
						'type' => 'object',
						'properties' => array(
							'name' => array('type' => 'string', 'description' => 'Item name from the catalog, e.g. "Chicken Biryani".'),
						),
						'required' => array('name'),
					),
				),
			),
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'search_document',
					'description' => 'Search the business\'s full info document for the answer to a specific customer question. Use this when the question is about anything in the document (prices, hours, policies, services, delivery, FAQs).',
					'parameters' => array(
						'type' => 'object',
						'properties' => array(
							'query' => array('type' => 'string', 'description' => 'The customer question or keywords to look up in the document.'),
						),
						'required' => array('query'),
					),
				),
			),
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'search_knowledge',
					'description' => 'Search the business FAQ knowledge base for an answer to a specific customer question (delivery, payment, availability, policies, etc.).',
					'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'query' => array('type' => 'string', 'description' => 'The customer question to look up.'),
					),
					'required' => array('query'),
				),
			),
			),
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'cart_add',
					'description' => 'Add a menu item to the customer\'s order cart. Returns the current cart and total.',
					'parameters' => array(
						'type' => 'object',
						'properties' => array(
							'name' => array('type' => 'string', 'description' => 'Menu item name.'),
							'quantity' => array('type' => 'integer', 'description' => 'How many. Default 1.'),
						),
						'required' => array('name'),
					),
				),
			),
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'cart_remove',
					'description' => 'Remove a menu item from the customer\'s order cart.',
					'parameters' => array(
						'type' => 'object',
						'properties' => array(
							'name' => array('type' => 'string', 'description' => 'Menu item name to remove.'),
						),
						'required' => array('name'),
					),
				),
			),
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'cart_clear',
					'description' => 'Remove everything from the customer\'s order cart.',
					'parameters' => array('type' => 'object', 'properties' => new stdClass()),
				),
			),
			array(
				'type' => 'function',
				'function' => array(
					'name' => 'request_checkout',
					'description' => 'Call when the customer is ready to place their order. Shows the summary and asks them to confirm.',
					'parameters' => array('type' => 'object', 'properties' => new stdClass()),
				),
			),
		);
	}

	/**
	 * Tool executor called by Ai_bot during the tool-calling loop.
	 */
	public function _execute_tool($name, array $args)
	{
		// Cart tools need the cart to exist, even if state wasn't fully
		// initialised (defensive — the normal flow always sets it up).
		if (in_array($name, array('cart_add', 'cart_remove', 'cart_clear', 'request_checkout'), TRUE)
			&& ( ! isset($this->state['cart']) || ! is_array($this->state['cart'])))
		{
			$this->state['cart'] = array();
		}

		switch ($name)
		{
			case 'get_business_info':
				return $this->_tool_business_info(isset($args['topic']) ? (string)$args['topic'] : 'all');
			case 'get_menu':
				return $this->_tool_get_menu(isset($args['category']) ? (string)$args['category'] : '');
			case 'get_item':
				return $this->_tool_get_item(isset($args['name']) ? (string)$args['name'] : '');
			case 'search_document':
				return $this->_tool_document_search(isset($args['query']) ? (string)$args['query'] : '');
			case 'search_knowledge':
				return $this->_tool_knowledge(isset($args['query']) ? (string)$args['query'] : '');
			case 'cart_add':
				return $this->_tool_cart_add(
					isset($args['name']) ? (string)$args['name'] : '',
					isset($args['quantity']) ? (int)$args['quantity'] : 1
				);
			case 'cart_remove':
				return $this->_tool_cart_remove(isset($args['name']) ? (string)$args['name'] : '');
			case 'cart_clear':
				return $this->_tool_cart_clear();
			case 'request_checkout':
				return $this->_tool_checkout();
		}
		return 'Unknown tool: ' . $name;
	}

	protected function _tool_business_info($topic)
	{
		$s = $this->settings;
		$map = array(
			'name'     => 'Business name: ' . $s['business_name'],
			'address'  => 'Address: ' . $s['business_address'],
			'hours'    => 'Opening hours: ' . $s['business_hours'],
			'phone'    => 'Phone: ' . $s['business_phone'],
			'delivery' => 'Delivery / service info: ' . $s['delivery_info'],
		);
		if ($topic !== 'all' && isset($map[$topic]))
		{
			return $map[$topic];
		}
		return implode("\n", $map);
	}

	protected function _tool_get_menu($category)
	{
		$CI =& get_instance();
		$items = $CI->menu_model->get_items(TRUE);
		if ( ! $items)
		{
			return 'The catalog is currently empty.';
		}
		if ($category !== '')
		{
			$filtered = array();
			foreach ($items as $item)
			{
				if (strcasecmp(trim((string)$item['category_name']), trim($category)) === 0)
				{
					$filtered[] = $item;
				}
			}
			$items = $filtered;
			if ( ! $items)
			{
				return 'No items found in category "' . $category . '". Get the full catalog to see the available categories.';
			}
		}

		$groups = array();
		foreach ($items as $item)
		{
			$cat = $item['category_name'] !== NULL && $item['category_name'] !== '' ? $item['category_name'] : 'Other';
			$groups[$cat][] = $item;
		}
		$out = '';
		foreach ($groups as $cat => $group)
		{
			$out .= $cat . ":\n";
			foreach ($group as $item)
			{
				$out .= '• ' . $item['name'] . ' – ' . $this->_currency() . money_fmt($item['price']) . "\n";
			}
			$out .= "\n";
		}
		return trim($out);
	}

	protected function _tool_get_item($name)
	{
		$CI =& get_instance();
		$item = $CI->menu_model->find_by_name($name);
		if ( ! $item)
		{
			return 'Item not found: "' . $name . '". Check the menu with get_menu.';
		}
		$out = $item['name'] . ' – ' . $this->_currency() . money_fmt($item['price']);
		if ( ! empty($item['description']))
		{
			$out .= "\n" . $item['description'];
		}
		return $out;
	}

	/**
	 * Search the owner's business document by keywords and return the most
	 * relevant paragraphs (covers parts truncated out of the system prompt).
	 */
	protected function _tool_document_search($query)
	{
		$doc = isset($this->settings['business_document']) ? trim((string)$this->settings['business_document']) : '';
		if ($doc === '')
		{
			return 'The business document is empty. Answer using the other tools or say you are not sure.';
		}
		if ($query === '')
		{
			return 'No search query provided.';
		}

		$paragraphs = preg_split('/\n\s*\n|\r\n\s*\r\n/', $doc);
		$terms = preg_split('/[\s,.;!?]+/', mb_strtolower($query));
		$terms = array_filter($terms, function ($t) { return mb_strlen($t) >= 3; });

		$scored = array();
		foreach ($paragraphs as $i => $para)
		{
			$para = trim($para);
			if ($para === '')
			{
				continue;
			}
			$low = mb_strtolower($para);
			$score = 0;
			foreach ($terms as $term)
			{
				$count = mb_substr_count($low, $term);
				$score += min($count, 3);
			}
			if ($score > 0)
			{
				$scored[] = array('idx' => $i, 'score' => $score, 'text' => $para);
			}
		}
		usort($scored, function ($a, $b) { return $b['score'] - $a['score']; });

		if ( ! $scored)
		{
			return 'No match found in the business document for: "' . $query . '". Answer using the other tools or say you are not sure.';
		}
		$out = array();
		foreach (array_slice($scored, 0, 4) as $hit)
		{
			$text = mb_strimwidth($hit['text'], 0, 900, '…');
			$out[] = '• ' . $text;
		}
		return "From the business document:\n\n" . implode("\n\n", $out);
	}

	protected function _tool_knowledge($query)
	{
		$CI =& get_instance();
		$hits = $CI->knowledge_model->search($query, 3);
		if ( ! $hits)
		{
			return 'No knowledge base entries matched. Answer using the other tools, or say you are not sure.';
		}
		$s = $this->settings;
		$replace = array(
			'{business_name}'    => $s['business_name'],
			'{business_address}' => $s['business_address'],
			'{business_hours}'   => $s['business_hours'],
			'{business_phone}'   => $s['business_phone'],
			'{delivery_info}'      => $s['delivery_info'],
		);
		$out = array();
		foreach ($hits as $hit)
		{
			$out[] = 'Q: ' . $hit['question'] . "\nA: " . strtr($hit['answer'], $replace);
		}
		return implode("\n\n", $out);
	}

	protected function _tool_cart_add($name, $qty)
	{
		$CI =& get_instance();
		if ($name === '')
		{
			return 'No item name provided.';
		}
		$item = $CI->menu_model->find_by_name($name);
		if ( ! $item)
		{
			return 'Item not found: "' . $name . '". Get the catalog first with get_menu.';
		}
		if ($qty < 1)
		{
			$qty = 1;
		}
		$found = FALSE;
		foreach ($this->state['cart'] as &$entry)
		{
			if ((int)$entry['item_id'] === (int)$item['id'])
			{
				$entry['qty'] += $qty;
				$found = TRUE;
				break;
			}
		}
		unset($entry);
		if ( ! $found)
		{
			$this->state['cart'][] = array(
				'item_id' => (int)$item['id'],
				'name'    => $item['name'],
				'price'   => (float)$item['price'],
				'qty'     => $qty,
			);
		}
		return "Added " . $qty . "x " . $item['name'] . " to the cart.\n\n" . $this->_format_cart();
	}

	protected function _tool_cart_remove($name)
	{
		if ($name === '')
		{
			return 'No item name provided.';
		}
		$before = count($this->state['cart']);
		foreach ($this->state['cart'] as $i => $entry)
		{
			if (strcasecmp((string)$entry['name'], $name) === 0)
			{
				unset($this->state['cart'][$i]);
				$this->state['cart'] = array_values($this->state['cart']);
				break;
			}
		}
		if (count($this->state['cart']) === $before)
		{
			return 'That item is not in the cart.';
		}
		return "Removed " . $name . " from the cart.\n\n" . $this->_format_cart();
	}

	protected function _tool_cart_clear()
	{
		$this->state['cart'] = array();
		return 'The cart is now empty.';
	}

	protected function _tool_checkout()
	{
		if ( ! $this->state['cart'])
		{
			return 'The cart is empty. Add items first with cart_add.';
		}
		$this->conversation['state'] = 'awaiting_confirm';
		return "Order summary:\n" . $this->_format_cart()
			. "\nTotal: " . $this->_currency() . money_fmt($this->_cart_total())
			. "\n\nShall I place this order? Reply YES to confirm, or NO to change it.";
	}

	/* ================= Formatting helpers ================= */

	protected function _format_cart()
	{
		$lines = array();
		foreach ($this->state['cart'] as $entry)
		{
			$subtotal = (float)$entry['price'] * (int)$entry['qty'];
			$lines[] = '• ' . $entry['qty'] . 'x ' . $entry['name'] . ' – ' . $this->_currency() . money_fmt($subtotal);
		}
		$out = implode("\n", $lines);
		if ($out === '')
		{
			return '(empty cart)';
		}
		return $out;
	}

	protected function _format_items(array $items)
	{
		$lines = array();
		foreach ($items as $item)
		{
			$lines[] = '• ' . $item['quantity'] . 'x ' . $item['name'] . ' – ' . $this->_currency() . money_fmt($item['price'] * (int)$item['quantity']);
		}
		return implode("\n", $lines);
	}

	protected function _cart_total()
	{
		$total = 0.0;
		foreach ($this->state['cart'] as $entry)
		{
			$total += (float)$entry['price'] * (int)$entry['qty'];
		}
		return $total;
	}
}
