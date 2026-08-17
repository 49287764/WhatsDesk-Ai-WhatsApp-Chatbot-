<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings_model extends CI_Model
{
	/**
	 * Return all settings as a key => value array.
	 */
	public function get_all()
	{
		$result = $this->db->get('settings')->result_array();
		$out = array();
		foreach ($result as $row)
		{
			$out[$row['key']] = $row['value'];
		}
		return $out;
	}

	/**
	 * Get one setting value (or $default if missing/empty).
	 */
	public function get($key, $default = '')
	{
		$row = $this->db->get_where('settings', array('key' => $key), 1)->row_array();
		return ($row && $row['value'] !== '' && $row['value'] !== NULL) ? $row['value'] : $default;
	}

	/**
	 * Set one setting (upsert). Checks existence first so saving an
	 * unchanged value doesn't trip a duplicate-key INSERT.
	 */
	public function set($key, $value)
	{
		$value = (string)$value;
		$exists = $this->db->get_where('settings', array('key' => $key), 1)->row_array();
		if ($exists)
		{
			$this->db->where('key', $key);
			$this->db->update('settings', array('value' => $value));
		}
		else
		{
			$this->db->insert('settings', array('key' => $key, 'value' => $value));
		}
	}

	/**
	 * Save many settings at once (POST array).
	 */
	public function set_many(array $values)
	{
		foreach ($values as $key => $value)
		{
			if (is_string($key))
			{
				$this->set($key, $value);
			}
		}
	}

	/**
	 * Effective settings: DB value wins, otherwise config file fallback.
	 */
	public function merged()
	{
		$db = $this->get_all();
		$CI =& get_instance();

		// NOTE: the config files are autoloaded WITHOUT sections, so their
		// values live at the top level — config->item('wa_token'), NOT
		// config->item('wa_token', 'whatsapp') (which always returned NULL
		// and made empty credentials look configured).
		$fallbacks = array(
			'wa_token'           => $CI->config->item('wa_token'),
			'wa_phone_number_id' => $CI->config->item('wa_phone_number_id'),
			'wa_app_secret'      => $CI->config->item('wa_app_secret'),
			'wa_verify_token'    => $CI->config->item('wa_verify_token'),
			'wa_graph_version'   => $CI->config->item('wa_graph_version'),
			'owner_wa_id'        => $CI->config->item('wa_owner_wa_id'),
			'ai_provider'        => $CI->config->item('ai_provider'),
			'ai_api_key'         => $CI->config->item('ai_api_key'),
			'ai_model'           => $CI->config->item('ai_model'),
			'ai_base_url'        => $CI->config->item('ai_base_url'),
			'ai_temperature'     => $CI->config->item('ai_temperature'),
			'ai_max_tokens'      => $CI->config->item('ai_max_tokens'),
			'wa_notify_status'   => $CI->config->item('wa_notify_status'),
			'wa_status_message'  => $CI->config->item('wa_status_message'),
			'currency_symbol'    => $CI->config->item('currency_symbol'),
		);

		foreach ($fallbacks as $key => $fallback)
		{
			if ($fallback === NULL)
			{
				$fallback = ''; // never let NULL leak into merged settings
			}
			if ( ! isset($db[$key]) || $db[$key] === '' || $db[$key] === NULL)
			{
				$db[$key] = $fallback;
			}
		}
		return $db;
	}
}
