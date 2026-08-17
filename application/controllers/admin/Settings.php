<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller
{
	// Only these keys can be edited from the panel (blocks arbitrary inserts).
	protected $editable_keys = array(
		'business_name', 'business_address', 'business_phone',
		'business_hours', 'delivery_info', 'greeting', 'fallback_msg', 'currency_symbol',
		'owner_wa_id', 'wa_token', 'wa_phone_number_id', 'wa_app_secret',
		'wa_verify_token', 'wa_graph_version', 'wa_order_template',
		'ai_provider', 'ai_api_key', 'ai_model', 'ai_base_url', 'ai_temperature', 'ai_max_tokens',
		'collect_customer_details', 'wa_process_inline', 'wa_notify_status', 'wa_status_message', 'cron_key',
	);

	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	public function index()
	{
		$settings = $this->settings_model->get_all();

		// Connection status hub — computed server-side so the badges are
		// accurate the moment the page loads.
		$conn = array();
		$conn['ai'] = array(
			'label'    => 'AI provider',
			'icon'     => 'bi-stars',
			'ok'       => ! empty($settings['ai_api_key']),
			'detail'   => ! empty($settings['ai_api_key']) ? ($settings['ai_model'] !== '' ? $settings['ai_model'] : 'model not set') : 'no API key yet',
			'hint'     => 'Add a key in the AI section, then hit “Test AI connection”.',
			'url'      => '#sec-ai',
		);
		$conn['whatsapp'] = array(
			'label'    => 'WhatsApp Cloud API',
			'icon'     => 'bi-whatsapp',
			'ok'       => ! empty($settings['wa_token']) && ! empty($settings['wa_phone_number_id']),
			'detail'   => (! empty($settings['wa_token']) && ! empty($settings['wa_phone_number_id'])) ? 'token + phone id set' : 'token or phone id missing',
			'hint'     => 'Needed to send and receive messages. Test it with “Check WhatsApp connection”.',
			'url'      => '#sec-whatsapp',
		);
		$conn['webhook'] = array(
			'label'    => 'Webhook (Meta)',
			'icon'     => 'bi-broadcast',
			'ok'       => ! empty($settings['wa_verify_token']) && ! empty($settings['wa_app_secret']),
			'detail'   => (! empty($settings['wa_verify_token']) && ! empty($settings['wa_app_secret'])) ? 'verify token + app secret set' : 'verify token or app secret missing',
			'hint'     => 'Enter the Callback URL + verify token in the Meta dashboard to receive messages.',
			'url'      => '#sec-whatsapp',
		);
		$conn['cron'] = array(
			'label'    => 'Cron worker',
			'icon'     => 'bi-clock-history',
			'ok'       => ! empty($settings['cron_key']) && $settings['cron_key'] !== 'change-me',
			'detail'   => (! empty($settings['cron_key']) && $settings['cron_key'] !== 'change-me') ? 'secure key set' : 'key not set or default',
			'hint'     => 'Keep it simple: the webhook replies instantly — cron is only the safety net.',
			'url'      => '#sec-security',
		);
		$conn['owner'] = array(
			'label'    => 'Owner notifications',
			'icon'     => 'bi-bell',
			'ok'       => ! empty($settings['owner_wa_id']),
			'detail'   => ! empty($settings['owner_wa_id']) ? $settings['owner_wa_id'] : 'no number yet',
			'hint'     => 'New-order and human-handoff alerts are sent to this WhatsApp number.',
			'url'      => '#sec-whatsapp',
		);

		// Token expiry warning — Meta temporary tokens expire in ~24 h.
		// If the last successful check was >20 h ago, warn the user.
		$token_warn = '';
		$token_warn_type = 'info'; // 'warning' or 'danger'
		if ( ! empty($settings['wa_token']) && ! empty($settings['wa_token_last_verified']))
		{
			$last = strtotime($settings['wa_token_last_verified']);
			$hours_ago = ($last > 0) ? (time() - $last) / 3600 : 999;
			if ($hours_ago > 20)
			{
				$token_warn = 'Your WhatsApp token was last verified ' . round($hours_ago) . ' hours ago. Meta temporary tokens expire after ~24 hours — your bot may stop sending messages soon. Generate a permanent System User token (Settings → WhatsApp → the guide under the token field) and paste it here.';
				$token_warn_type = ($hours_ago > 24) ? 'danger' : 'warning';
			}
		}
		// Also show a danger warning if the connection check itself fails
		// (the chip already shows red, but a top-level banner is harder to miss).
		if (empty($settings['wa_token_last_verified']) && ! empty($settings['wa_token']))
		{
			$token_warn = 'You have a WhatsApp token set but have never verified it. Click "Check WhatsApp connection" below to test it.';
			$token_warn_type = 'info';
		}

		$data = array(
			'page_title' => 'Settings',
			'settings'   => $settings,
			'keys'       => $this->editable_keys,
			'conn'       => $conn,
			'token_warn' => $token_warn,
			'token_warn_type' => $token_warn_type,
		);
		$this->render('admin/settings', $data);
	}

	public function save()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/settings');
		}

		$values = array();
		foreach ($this->editable_keys as $key)
		{
			$value = $this->input->post($key);
			if (is_string($value))
			{
				$values[$key] = trim($value);
			}
		}

		// Never let the panel store blank credentials over real ones accidentally.
		$current = $this->settings_model->get_all();
		foreach (array('wa_token', 'wa_phone_number_id', 'wa_app_secret', 'wa_verify_token', 'ai_api_key') as $secret)
		{
			if (isset($values[$secret]) && $values[$secret] === '' && ! empty($current[$secret]))
			{
				unset($values[$secret]);
			}
		}

		$this->settings_model->set_many($values);
		$this->flash('Settings saved.', 'ok');
		redirect('admin/settings');
	}

	/**
	 * Send a quick test prompt to the configured AI provider so the owner
	 * can verify the key/model/base URL work without sending any WhatsApp.
	 */
	public function test_ai()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/settings');
		}

		$this->load->library('ai_bot');
		$result = $this->ai_bot->chat(array(
			array('role' => 'system', 'content' => 'You are a quick connection test. Reply with exactly: AI connection OK'),
			array('role' => 'user', 'content' => 'ping'),
		));

		if ($result && isset($result['content']) && trim($result['content']) !== '')
		{
			$this->flash('AI test passed! Reply from ' . $this->ai_bot->model_name() . ': “' . mb_strimwidth(trim($result['content']), 0, 120, '…') . '”', 'ok');
		}
		else
		{
			$this->flash('AI test failed. Check the API key, model and base URL — and make sure the provider allows your region.', 'err');
		}
		redirect('admin/settings');
	}

	/**
	 * Live-check the WhatsApp credentials against the Graph API (no message
	 * is sent). Validates the token + phone number ID pair directly.
	 */
	public function test_whatsapp()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/settings');
		}

		$this->load->library('whatsapp_api');
		$result = $this->whatsapp_api->verify_credentials();

		if ($result['ok'])
		{
			// Record the last successful verification time so the Settings
			// page can warn when the token is likely expired (>20 h since
			// last success — Meta temporary tokens die in ~24 h).
			$this->settings_model->set('wa_token_last_verified', date('Y-m-d H:i:s'));
			$this->flash('WhatsApp connected! ' . $result['message'], 'ok');
		}
		else
		{
			$this->flash('WhatsApp check failed: ' . $result['message'], 'err');
		}
		redirect('admin/settings');
	}

	/**
	 * Send a test WhatsApp message to the owner number to verify the API config.
	 */
	public function test_message()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/settings');
		}

		$this->load->library('whatsapp_api');
		$settings = $this->settings_model->merged();
		$owner = $settings['owner_wa_id'];

		if ($owner === '')
		{
			$this->flash('Set the owner WhatsApp number first.', 'err');
			redirect('admin/settings');
		}
		if ( ! $this->whatsapp_api->is_configured())
		{
			$this->flash('WhatsApp API is not configured yet (token or phone number id missing).', 'err');
			redirect('admin/settings');
		}

		$sent = $this->whatsapp_api->send_text($owner, "✅ Test message from your WhatsDesk assistant.\nIf you received this, WhatsApp is connected correctly!");
		$this->flash($sent
			? 'Test message sent to ' . $owner . '. Check your WhatsApp!'
			: 'Test message failed. Check the token and phone number id — and remember the owner must message the bot once so WhatsApp opens a 24h window.',
			$sent ? 'ok' : 'err');
		redirect('admin/settings');
	}
}
