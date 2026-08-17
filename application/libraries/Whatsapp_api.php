<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Whatsapp_api
 *
 * Minimal WhatsApp Cloud API client (Meta Graph API).
 * Handles webhook verification, signature validation, incoming payload
 * parsing and sending text/template messages.
 *
 * Credentials come from the `settings` table (admin panel) with fallback
 * to the config files.
 */
class Whatsapp_api
{
	protected $token = '';
	protected $phone_number_id = '';
	protected $graph_version = 'v25.0';
	protected $graph_url = 'https://graph.facebook.com';
	protected $app_secret = '';
	protected $verify_token = '';

	public function __construct()
	{
		$CI =& get_instance();
		if ( ! isset($CI->settings_model))
		{
			$CI->load->model('settings_model');
		}
		$s = $CI->settings_model->merged();

		$this->token           = $s['wa_token'];
		$this->phone_number_id = $s['wa_phone_number_id'];
		$this->graph_version   = $s['wa_graph_version'] !== '' ? $s['wa_graph_version'] : 'v25.0';
		$this->app_secret      = $s['wa_app_secret'];
		$this->verify_token    = $s['wa_verify_token'];
		if (isset($s['wa_graph_url']) && $s['wa_graph_url'] !== '')
		{
			$this->graph_url = rtrim($s['wa_graph_url'], '/');
		}
	}

	public function is_configured()
	{
		return $this->token !== '' && $this->phone_number_id !== '';
	}

	/* ---------------- Webhook ---------------- */

	/**
	 * GET verification. Returns hub_challenge string, or FALSE on failure.
	 */
	public function verify_webhook()
	{
		$CI =& get_instance();
		$mode = $CI->input->get('hub_mode');
		$token = $CI->input->get('hub_verify_token');
		$challenge = $CI->input->get('hub_challenge');

		if ($mode === 'subscribe' && $token !== '' && $this->verify_token !== ''
			&& hash_equals($this->verify_token, $token))
		{
			return $challenge;
		}
		return FALSE;
	}

	/**
	 * Validate the X-Hub-Signature-256 header against the raw request body.
	 */
	public function verify_signature($raw_body, $signature)
	{
		if ($this->app_secret === '' || $signature === '')
		{
			return FALSE;
		}
		$expected = 'sha256=' . hash_hmac('sha256', $raw_body, $this->app_secret);
		return hash_equals($expected, $signature);
	}

	/**
	 * Parse a webhook POST payload into a flat list of inbound messages.
	 */
	public function parse_incoming(array $payload)
	{
		$messages = array();
		if (empty($payload['entry']))
		{
			return $messages;
		}
		foreach ($payload['entry'] as $entry)
		{
			if (empty($entry['changes']))
			{
				continue;
			}
			foreach ($entry['changes'] as $change)
			{
				$value = isset($change['value']) && is_array($change['value']) ? $change['value'] : array();
				if (empty($value['messages']))
				{
					continue;
				}
				$contacts = isset($value['contacts']) ? $value['contacts'] : array();
				foreach ($value['messages'] as $msg)
				{
					$from = isset($msg['from']) ? (string)$msg['from'] : '';
					$name = '';
					foreach ($contacts as $c)
					{
						if (isset($c['wa_id']) && (string)$c['wa_id'] === $from)
						{
							$name = isset($c['profile']['name']) ? (string)$c['profile']['name'] : '';
							break;
						}
					}
					$type = isset($msg['type']) ? (string)$msg['type'] : 'unknown';
					$body = '';
					if ($type === 'text' && isset($msg['text']['body']))
					{
						$body = (string)$msg['text']['body'];
					}
					$messages[] = array(
						'wa_message_id' => isset($msg['id']) ? (string)$msg['id'] : '',
						'wa_id'         => $from,
						'name'          => $name,
						'type'          => $type,
						'body'          => $body,
						'timestamp'     => isset($msg['timestamp']) ? (string)$msg['timestamp'] : '',
					);
				}
			}
		}
		return $messages;
	}

	/* ---------------- Sending ---------------- */

	/**
	 * Send a free-form text message (only valid inside a 24h window).
	 */
	public function send_text($to, $body)
	{
		return $this->_post('messages', array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $to,
			'type'              => 'text',
			'text'              => array(
				'preview_url' => FALSE,
				'body'        => (string)$body,
			),
		));
	}

	/**
	 * Send an approved template message (needed outside the 24h window).
	 */
	public function send_template($to, $template_name, $language = 'en_US', array $components = array())
	{
		$data = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $to,
			'type'              => 'template',
			'template'          => array(
				'name'     => $template_name,
				'language' => array('code' => $language),
			),
		);
		if ($components)
		{
			$data['template']['components'] = $components;
		}
		return $this->_post('messages', $data);
	}

	/**
	 * Mark an inbound message as read.
	 */
	public function mark_read($message_id)
	{
		return $this->_post('messages', array(
			'messaging_product' => 'whatsapp',
			'status'            => 'read',
			'message_id'        => $message_id,
		));
	}

	/* ---------------- Diagnostics ---------------- */

	/**
	 * Live-check the WhatsApp credentials against the Graph API.
	 * Returns array('ok' => bool, 'message' => string).
	 */
	public function verify_credentials()
	{
		if ( ! $this->is_configured())
		{
			return array('ok' => FALSE, 'message' => 'Missing access token or phone number id.');
		}

		$url = $this->graph_url . '/' . $this->graph_version . '/' . $this->phone_number_id
			. '?fields=display_phone_number,verified_name';

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $this->token),
			CURLOPT_TIMEOUT        => 20,
			CURLOPT_CONNECTTIMEOUT => 10,
		));
		$response = curl_exec($ch);
		$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($response === FALSE)
		{
			return array('ok' => FALSE, 'message' => 'Network error: ' . $error);
		}
		$decoded = json_decode($response, TRUE);
		if ($http_code >= 200 && $http_code < 300 && isset($decoded['display_phone_number']))
		{
			$name = isset($decoded['verified_name']) ? $decoded['verified_name'] : '(unverified name)';
			return array('ok' => TRUE, 'message' => 'Connected to ' . $decoded['display_phone_number'] . ' — ' . $name);
		}
		$msg = isset($decoded['error']['message']) ? (string)$decoded['error']['message'] : 'HTTP ' . $http_code;
		return array('ok' => FALSE, 'message' => $msg);
	}

	/* ---------------- Internals ---------------- */

	protected function _post($endpoint, array $data)
	{
		if ( ! $this->is_configured())
		{
			log_message('error', 'WhatsApp API not configured (token or phone number id missing).');
			return FALSE;
		}

		$url = $this->graph_url . '/' . $this->graph_version . '/' . $this->phone_number_id . '/' . $endpoint;

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_POST           => TRUE,
			CURLOPT_POSTFIELDS     => json_encode($data),
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Bearer ' . $this->token,
				'Content-Type: application/json',
			),
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_CONNECTTIMEOUT => 10,
		));
		$response = curl_exec($ch);
		$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($response === FALSE)
		{
			log_message('error', 'WhatsApp API curl error: ' . $error);
			return FALSE;
		}
		$decoded = json_decode($response, TRUE);
		if ($http_code >= 200 && $http_code < 300)
		{
			return $decoded;
		}
		log_message('error', 'WhatsApp API error (' . $http_code . '): ' . $response);
		return FALSE;
	}
}
