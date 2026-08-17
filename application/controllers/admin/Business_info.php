<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Business_info
 *
 * The "tell us everything about your business" page. The owner pastes (or
 * uploads) one long document describing the business — services, prices,
 * hours, policies, FAQs, contact details… The bot uses this document as
 * its authoritative knowledge source and answers customer questions from it.
 *
 * The document is stored in the `settings` table under the key
 * `business_document` (long text) so it travels with the other settings.
 *
 * When the document is saved, the key business facts (name, hours, address,
 * phone, delivery info) are auto-extracted from it and written to their own
 * settings — so a fresh install never shows "Your Business / 123 Main
 * Street" dummy values once the owner uploads their real document.
 */
class Business_info extends MY_Controller
{
	// Values in these fields mean "still the demo seed" — safe to overwrite.
	protected $dummy = array(
		'business_name'    => 'Your Business',
		'business_address' => '123 Main Street, Your City',
		'business_phone'   => '+15551234567',
		'business_hours'   => 'Mon–Sun: 11:00 – 22:00',
		'delivery_info'    => 'We offer delivery and pickup. Ask us about delivery times and fees for your area.',
	);

	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	public function index()
	{
		$doc = (string)$this->settings_model->get('business_document', '');

		$data = array(
			'page_title' => 'Business info',
			'page_sub'   => 'Tell the bot everything about your business — it answers customers from this document.',
			'document'   => $doc,
			'doc_chars'  => mb_strlen($doc),
			'doc_words'  => str_word_count($doc),
			'facts'      => $this->_facts_status(),
		);
		$this->render('admin/business_info', $data);
	}

	public function save()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/business_info');
		}

		$doc = trim((string)$this->input->post('document', TRUE));
		$trimmed = FALSE;
		if (mb_strlen($doc) > 60000)
		{
			$doc = mb_substr($doc, 0, 60000);
			$trimmed = TRUE;
		}

		$this->settings_model->set('business_document', $doc);

		// Auto-fill any business details that are still empty/dummy from the
		// freshly saved document — the owner shouldn't have to fill the same
		// info twice.
		$filled = $this->_autofill_from_document($doc);

		$msg = $trimmed
			? 'Saved — the document was longer than 60,000 characters, so it was trimmed. Try splitting it into the most important parts.'
			: 'Business info saved! The bot will answer customers from it right away.';
		if ($filled)
		{
			$msg .= ' I also filled in: ' . implode(', ', $filled) . '.';
		}
		elseif ($doc !== '')
		{
			$msg .= ' (Your business name / hours / address were already set, so I left them untouched — edit them in Settings if needed.)';
		}
		$this->flash($msg, 'ok');
		redirect('admin/business_info');
	}

	/**
	 * Re-run the auto-extraction manually (button on the page) without
	 * touching the document itself.
	 */
	public function autofill()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/business_info');
		}

		$doc = (string)$this->settings_model->get('business_document', '');
		if (trim($doc) === '')
		{
			$this->flash('Save your business document first — then I can pull the details from it.', 'err');
			redirect('admin/business_info');
		}

		$filled = $this->_autofill_from_document($doc);
		$this->flash($filled
			? 'Auto-filled from your document: ' . implode(', ', $filled) . '. You can tweak any of them in Settings.'
			: 'Everything was already set, so nothing changed. Edit individual details in Settings.',
			'ok');
		redirect('admin/business_info');
	}

	/**
	 * Extract business_name / hours / address / phone / delivery_info from
	 * the document and save them — but ONLY where the current value is empty
	 * or still the demo seed. Returns the list of keys that were filled.
	 */
	protected function _autofill_from_document($doc)
	{
		$facts = $this->_extract_facts($doc);
		$current = $this->settings_model->get_all();
		$filled = array();

		foreach ($facts as $key => $value)
		{
			if ($value === '')
			{
				continue;
			}
			$cur = isset($current[$key]) ? trim((string)$current[$key]) : '';
			$is_dummy = ($cur === '')
				|| (isset($this->dummy[$key]) && $cur === $this->dummy[$key])
				|| preg_match('/^[\*\-:•|\s]+$/', $cur); // junk leftover (e.g. "*")
			if ($is_dummy)
			{
				$this->settings_model->set($key, $value);
				$filled[] = $key;
			}
		}
		return $filled;
	}

	/**
	 * Quick-and-dirty but effective extraction of the key facts from a
	 * typical business document (markdown or plain text).
	 */
	protected function _extract_facts($doc)
	{
		$lines = preg_split('/\R/', $doc);
		$lines = array_map('trim', $lines);
		$facts = array(
			'business_name'    => '',
			'business_hours'   => '',
			'business_address' => '',
			'business_phone'   => '',
			'delivery_info'    => '',
		);

		// --- Name: first # heading, or "We are X", or first line ---
		foreach ($lines as $i => $line)
		{
			if ($line === '') continue;
			if (preg_match('/^#+\s*(.+)$/', $line, $m))
			{
				$facts['business_name'] = $this->_clean_fact($m[1]);
				break;
			}
			if (preg_match('/we\s+are\s+([^\.,!]+)/i', $line, $m))
			{
				$facts['business_name'] = $this->_clean_fact($m[1]);
				break;
			}
			if ($i === 0 && mb_strlen($line) < 80)
			{
				$facts['business_name'] = $this->_clean_fact($line);
				break;
			}
		}

		// --- Hours: lines mentioning a weekday + a time ---
		$hour_lines = array();
		foreach ($lines as $line)
		{
			if (preg_match('/\b(mon|tue|wed|thu|fri|sat|sun|monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', $line)
				&& preg_match('/\b\d{1,2}(:\d{2})?\s*(am|pm|noon|midnight)\b/i', $line))
			{
				$hour_lines[] = $this->_clean_fact(preg_replace('/^\s*[-*•]\s*/', '', $line));
			}
		}
		if ($hour_lines)
		{
			$facts['business_hours'] = mb_substr(implode('; ', array_slice($hour_lines, 0, 10)), 0, 500);
		}

		// --- Address: "Address:" line (value on same or next line), else a
		//     street-looking line ---
		foreach ($lines as $i => $line)
		{
			if (preg_match('/\baddress\s*[:\-]\s*(.+)$/i', $line, $m))
			{
				$v = $this->_clean_fact($m[1]);
				if ($this->_looks_like_fact($v))
				{
					$facts['business_address'] = $v;
					break;
				}
				// Value is on the following line(s) — common markdown pattern:
				//   **Address:**
				//   25 Main Boulevard, Model Town, Lahore
				for ($j = $i + 1; $j < count($lines) && $j <= $i + 3; $j++)
				{
					$v2 = $this->_clean_fact($lines[$j]);
					if ($this->_looks_like_fact($v2))
					{
						$facts['business_address'] = $v2;
						break 2;
					}
				}
			}
		}
		if ($facts['business_address'] === '')
		{
			foreach ($lines as $line)
			{
				if (preg_match('/\b(\d+\s+[A-Za-z].*(street|road|boulevard|avenue|lane|rd|st\.?|main)|(lahore|karachi|islamabad|rawalpindi|multan|faisalabad|peshawar|quetta|hyderabad|sialkot|gujranwala))\b/i', $line))
				{
					$v = $this->_clean_fact(preg_replace('/^.*?\b(we are|located|at)\b\s*/i', '', $line));
					if ($this->_looks_like_fact($v)) { $facts['business_address'] = $v; break; }
				}
			}
		}

		// --- Phone: prefer a "Phone:"/"WhatsApp:"/"Contact:" line, else any +92/+1 style number ---
		foreach ($lines as $line)
		{
			if (preg_match('/\b(phone|whatsapp|contact|call)\s*[:\-]/i', $line) && preg_match('/\+?\d[\d\s\-]{8,}/', $line, $m))
			{
				$facts['business_phone'] = $this->_clean_fact($m[0]);
				break;
			}
		}
		if ($facts['business_phone'] === '')
		{
			foreach ($lines as $line)
			{
				if (preg_match('/\+\d[\d\s\-]{8,}/', $line, $m))
				{
					$facts['business_phone'] = $this->_clean_fact($m[0]);
					break;
				}
			}
		}

		// --- Delivery: the lines between a "delivery" heading and the next
		//     heading / blank block, compressed. ---
		$in_delivery = FALSE;
		$delivery_bits = array();
		foreach ($lines as $line)
		{
			if (preg_match('/^#+\s*.*(deliver|shipping|home delivery)/i', $line))
			{
				$in_delivery = TRUE;
				continue;
			}
			if ($in_delivery)
			{
				if (preg_match('/^#+/', $line)) break;
				if ($line !== '' && mb_strlen($line) < 200)
				{
					$delivery_bits[] = $this->_clean_fact(preg_replace('/^\s*[-*•]\s*/', '', $line));
				}
			}
		}
		if ($delivery_bits)
		{
			$facts['delivery_info'] = mb_substr(implode(' ', array_slice($delivery_bits, 0, 6)), 0, 600);
		}

		return $facts;
	}

	protected function _clean_fact($s)
	{
		$s = trim((string)$s);
		$s = preg_replace('/\*\*(.+?)\*\*/', '$1', $s); // strip **bold**
		$s = str_replace('**', '', $s);                     // strip lone ** leftovers
		$s = preg_replace('/^[-*•]\s*/', '', $s);
		$s = preg_replace('/\s+/', ' ', $s);
		return trim($s);
	}

	/**
	 * A cleaned fact is usable when it has real words (not just markdown
	 * leftovers like "**" / ":" / "-").
	 */
	protected function _looks_like_fact($v)
	{
		return $v !== '' && mb_strlen($v) >= 4 && ! preg_match('/^[\*\-:•|\s]+$/', $v);
	}

	/**
	 * Which business details are set vs still the demo default — shown on
	 * the page so the owner can see at a glance whether the bot will greet
	 * customers with their real name.
	 */
	protected function _facts_status()
	{
		$s = $this->settings_model->get_all();
		$labels = array(
			'business_name'    => 'Business name',
			'business_hours'   => 'Opening hours',
			'business_address' => 'Address',
			'business_phone'   => 'Phone',
			'delivery_info'    => 'Delivery info',
		);
		$out = array();
		foreach ($labels as $key => $label)
		{
			$cur = isset($s[$key]) ? trim((string)$s[$key]) : '';
			$dummy = isset($this->dummy[$key]) && $cur === $this->dummy[$key];
			$out[] = array(
				'key'   => $key,
				'label' => $label,
				'ok'    => ($cur !== '' && ! $dummy),
				'value' => $cur !== '' ? mb_strimwidth($cur, 0, 60, '…') : '(empty)',
			);
		}
		return $out;
	}
}
