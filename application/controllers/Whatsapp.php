<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Whatsapp
 *
 * Webhook endpoint for the WhatsApp Cloud API.
 *
 *   GET  /whatsapp/webhook  -> Meta verification handshake
 *   POST /whatsapp/webhook  -> inbound message events (stored, replied to by cron)
 *
 * NOTE: this URI is excluded from CSRF protection in config.php.
 */
class Whatsapp extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		// Libraries are loaded lazily inside webhook(): the GET verification
		// handshake only needs whatsapp_api, and loading bot_engine pulls in
		// the AI client + settings on every request.
	}

	public function webhook()
	{
		// Inline replies can take a few seconds (AI call) — allow enough time.
		@set_time_limit(120);

		// ---- GET: verification handshake ----
		if ($this->input->method(TRUE) === 'GET')
		{
			$this->load->library('whatsapp_api');
			$challenge = $this->whatsapp_api->verify_webhook();
			if ($challenge !== FALSE)
			{
				$this->output->set_content_type('text/plain')->set_output($challenge);
			}
			else
			{
				log_message('error', 'Webhook verification failed.');
				$this->output->set_status_header(403)->set_output('Verification failed');
			}
			return;
		}

		// ---- POST: validate signature, store inbound messages, return fast ----
		$raw = $this->input->raw_input_stream;
		$signature = isset($_SERVER['HTTP_X_HUB_SIGNATURE_256']) ? $_SERVER['HTTP_X_HUB_SIGNATURE_256'] : '';

		$this->load->library('whatsapp_api');
		if ( ! $this->whatsapp_api->verify_signature($raw, $signature))
		{
			log_message('error', 'Webhook signature verification failed.');
			$this->output->set_status_header(403)->set_output('Invalid signature');
			return;
		}

		$payload = json_decode($raw, TRUE);
		if (is_array($payload))
		{
			$messages = $this->whatsapp_api->parse_incoming($payload);

			// Near-instant replies: process each message right here in the
			// webhook. The cron worker stays on as a safety net (it retries
			// anything that fails or times out — no double replies thanks
			// to the message lock).
			$this->load->library('bot_engine');
			$inline = $this->settings_model->get('wa_process_inline', '1') !== '0';

			foreach ($messages as $m)
			{
				$message_id = $this->bot_engine->handle_webhook_message(
					$m['wa_id'],
					$m['name'],
					$m['body'],
					$m['wa_message_id'],
					$m['type']
				);
				if ($message_id && $inline)
				{
					$this->bot_engine->process_message_inline($message_id);
				}
			}
		}

		$this->output->set_content_type('application/json')->set_output(json_encode(array('status' => 'ok')));
	}
}
