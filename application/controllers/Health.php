<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Health
 *
 * Lightweight public health check for uptime monitors (UptimeRobot etc.).
 * GET /health -> {"status":"ok","db":true,...}
 */
class Health extends CI_Controller
{
	public function index()
	{
		$db_ok = FALSE;
		try
		{
			$db_ok = (bool)$this->db->query('SELECT 1');
		}
		catch (Exception $e)
		{
			$db_ok = FALSE;
		}

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(array(
				'status' => $db_ok ? 'ok' : 'degraded',
				'db'     => $db_ok,
				'time'   => date('c'),
			)));
	}
}
