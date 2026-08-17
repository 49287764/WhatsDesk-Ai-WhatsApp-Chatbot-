<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cron
 *
 * Background workers. The webhook already replies instantly (inline mode);
 * these workers are the safety net and the fallback for messages that
 * couldn't be handled inline.
 *
 * Standard worker (every minute):
 *   * * * * * php /path/to/project/index.php cron run
 *   or https://your-domain.com/cron/run?key=YOUR_CRON_KEY
 *
 * Fast poller (optional, near-instant replies on shared hosting):
 * polls the queue every second for up to 55s, then hands over to the next
 * cron minute. Single-instance guarded.
 *   * * * * * php /path/to/project/index.php cron fast
 */
class Cron extends CI_Controller
{
	protected function _authorize(&$key)
	{
		if (is_cli())
		{
			return TRUE;
		}
		if ($key === '')
		{
			$key = (string)$this->input->get('key');
		}
		$expected = $this->settings_model->get('cron_key', 'change-me');
		if ($key === '' || ! hash_equals($expected, $key))
		{
			show_error('Invalid cron key.', 403);
		}
		return TRUE;
	}

	public function run($key = '')
	{
		$this->_authorize($key);
		@set_time_limit(120);

		$this->load->library('bot_engine');
		$processed = $this->bot_engine->process_pending(10);

		// Housekeeping: purge old landing-page demo data once a day so the
		// public demo never grows the database without bound.
		if (date('G') === '3')
		{
			$purged = $this->conversation_model->purge_demo_data(30);
			$this->customer_model->purge_demo_guests();
			log_message('info', 'Demo data housekeeping: purged ' . $purged . ' old demo conversations.');
		}

		// Heartbeat so the dashboard can show whether the worker is alive.
		$this->settings_model->set('last_cron_run', date('Y-m-d H:i:s'));

		$this->_respond(is_cli(), 'Processed ' . $processed . " message(s)\n", array('status' => 'ok', 'processed' => $processed));
	}

	/**
	 * Fast poller: check the queue every second for ~55 seconds, then exit.
	 * Gives ~1s reply latency without needing a VPS or queue server.
	 */
	public function fast($key = '')
	{
		$this->_authorize($key);
		@set_time_limit(120);

		// Single-instance guard so overlapping cron minutes don't double-run.
		$lock_file = APPPATH . 'cache/cron_fast.lock';
		if (file_exists($lock_file))
		{
			$stamp = (int)file_get_contents($lock_file);
			if (time() - $stamp < 50)
			{
				// Previous cycle still running — do nothing this minute.
				$this->_respond(is_cli(), "Fast worker already running — skipping.\n", array('status' => 'ok', 'processed' => 0));
				return;
			}
		}
		file_put_contents($lock_file, time());

		$this->load->library('bot_engine');
		$start = time();
		$processed = 0;
		while (time() - $start < 55)
		{
			$processed += $this->bot_engine->process_pending(5);
			sleep(1);
		}

		@unlink($lock_file);
		$this->settings_model->set('last_cron_run', date('Y-m-d H:i:s'));

		$this->_respond(is_cli(), "Fast worker cycle done — processed " . $processed . " message(s).\n", array('status' => 'ok', 'processed' => $processed));
	}

	protected function _respond($is_cli, $text, array $json)
	{
		if ($is_cli)
		{
			echo $text;
			return;
		}
		$this->output->set_content_type('application/json')->set_output(json_encode($json));
	}
}
