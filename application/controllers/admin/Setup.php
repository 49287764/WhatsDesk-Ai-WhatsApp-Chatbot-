<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Setup
 *
 * One-click setup checker: verifies PHP extensions, database, config,
 * WhatsApp + AI credentials (with a live Graph API test) and the cron
 * worker — then shows a pass/warn/fail report with fixes.
 */
class Setup extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	public function index()
	{
		$data = array(
			'page_title' => 'Setup check',
			'page_sub'   => 'One-click health check for your environment, database, WhatsApp and AI.',
		);

		$data['groups'] = array();
		$data['groups']['Environment'] = $this->_check_environment();
		$data['groups']['Database'] = $this->_check_database();
		$data['groups']['Configuration & security'] = $this->_check_config();
		$data['groups']['WhatsApp Cloud API'] = $this->_check_whatsapp();
		$data['groups']['AI provider'] = $this->_check_ai();
		$data['groups']['Operations'] = $this->_check_operations();

		$fails = 0;
		$warns = 0;
		foreach ($data['groups'] as $items)
		{
			foreach ($items as $item)
			{
				if ($item['status'] === 'fail') $fails++;
				if ($item['status'] === 'warn') $warns++;
			}
		}
		$data['fails'] = $fails;
		$data['warns'] = $warns;
		$data['ready'] = ($fails === 0);
		$data['webhook_url'] = site_url('whatsapp/webhook');
		$data['cron_url'] = site_url('cron/run?key=' . $this->settings_model->get('cron_key', ''));

		$data['steps'] = $this->_steps();
		$done = 0;
		foreach ($data['steps'] as $st)
		{
			if ($st['done']) $done++;
		}
		$data['steps_done'] = $done;
		$data['steps_total'] = count($data['steps']);

		$this->render('admin/setup', $data);
	}

	/**
	 * The guided onboarding steps. Each step knows whether it's done so the
	 * wizard can show progress and jump to the right page.
	 */
	protected function _steps()
	{
		$s = $this->settings_model->merged();
		$this->load->model('admin_model');

		$doc_set = isset($s['business_document']) && trim((string)$s['business_document']) !== '';
		$biz_set = $doc_set || (isset($s['business_name']) && $s['business_name'] !== '' && $s['business_name'] !== 'Your Business');
		$wa_set = $s['wa_token'] !== '' && $s['wa_phone_number_id'] !== '' && $s['wa_app_secret'] !== '' && $s['wa_verify_token'] !== '';
		$ai_set = $s['ai_api_key'] !== '';
		$owner_set = $s['owner_wa_id'] !== '';
		$cron_set = $s['cron_key'] !== '' && $s['cron_key'] !== 'change-me';
		$account_set = ! $this->admin_model->is_seed_account();

		// Step 7 is verifiable: run a live WhatsApp credentials test (proves
		// the token + phone number ID work against Meta) and check whether
		// the optional cron worker heartbeat is fresh.
		$wa_live = FALSE;
		if ($wa_set)
		{
			$this->load->library('whatsapp_api');
			$result = $this->whatsapp_api->verify_credentials();
			$wa_live = (bool)$result['ok'];
		}
		$last_cron = $this->settings_model->get('last_cron_run', '');
		$cron_fresh = ($last_cron !== '' && (time() - strtotime($last_cron)) <= 300);
		if ($wa_live)
		{
			$live_note = 'WhatsApp connection verified against Meta — your bot is live! ';
			$live_note .= $cron_fresh ? 'Worker heartbeat is fresh.' : 'Cron worker is off (optional — the webhook replies instantly on its own).';
		}
		else
		{
			$live_note = 'Credentials are set but the live WhatsApp test failed — open Settings → WhatsApp and re-check the token / phone number ID.';
		}

		return array(
			array(
				'key'  => 'account',
				'num'  => 1,
				'title' => 'Create your account',
				'desc'  => 'Your account is secured with your own username and password — no default credentials.',
				'done'  => $account_set,
				'url'   => site_url('admin/auth/change_password'),
				'cta'   => $account_set ? 'Review password' : 'Set your password',
			),
			array(
				'key'  => 'business',
				'num'  => 2,
				'title' => 'Tell the bot about your business',
				'desc'  => 'Paste (or upload) one document about your business — services, prices, hours, policies. The bot answers customers from it.',
				'done'  => $biz_set,
				'url'   => site_url('admin/business_info'),
				'cta'   => $biz_set ? 'Edit business info' : 'Add business info',
			),
			array(
				'key'  => 'whatsapp',
				'num'  => 3,
				'title' => 'Connect WhatsApp',
				'desc'  => 'Add your Meta WhatsApp Cloud API credentials (token, phone number ID, app secret, verify token).',
				'done'  => $wa_set,
				'url'   => site_url('admin/settings') . '#sec-whatsapp',
				'cta'   => $wa_set ? 'Review WhatsApp settings' : 'Add WhatsApp keys',
			),
			array(
				'key'  => 'ai',
				'num'  => 4,
				'title' => 'Add your AI key',
				'desc'  => 'Paste an AI provider key (free option: Groq — no card; or OpenAI / DeepSeek) so the bot can chat.',
				'done'  => $ai_set,
				'url'   => site_url('admin/settings') . '#sec-ai',
				'cta'   => $ai_set ? 'Review AI settings' : 'Add AI key',
			),
			array(
				'key'  => 'owner',
				'num'  => 5,
				'title' => 'Set your notification number',
				'desc'  => 'Your WhatsApp number — you\'ll get a message for every new order and human handoff.',
				'done'  => $owner_set,
				'url'   => site_url('admin/settings') . '#sec-whatsapp',
				'cta'   => $owner_set ? 'Review notification number' : 'Add your number',
			),
			array(
				'key'  => 'cron',
				'num'  => 6,
				'title' => 'Secure the cron key',
				'desc'  => 'Replace the default \'change-me\' key — it protects the background worker that sends replies.',
				'done'  => $cron_set,
				'url'   => site_url('admin/settings') . '#sec-security',
				'cta'   => $cron_set ? 'Review cron key' : 'Change the cron key',
			),
			array(
				'key'  => 'live',
				'num'  => 7,
				'title' => 'Go live: webhook + cron',
				'desc'  => $live_note,
				'done'  => $wa_live,
				'url'   => '#go-live',
				'cta'   => $wa_live ? 'All live — review' : 'See instructions',
			),
		);
	}

	/* ---------------- Checks ---------------- */

	protected function _check_environment()
	{
		$items = array();

		$php = PHP_VERSION;
		if (version_compare($php, '7.4.0', '>='))
		{
			$status = 'ok';
		}
		elseif (version_compare($php, '7.2.0', '>='))
		{
			$status = 'warn';
		}
		else
		{
			$status = 'fail';
		}
		$hint = 'PHP 7.4+ recommended. On PHP 8.x, upgrade the system/ folder to CodeIgniter 3.1.13.';
		if (version_compare($php, '8.0.0', '>='))
		{
			$hint = 'PHP 8.x: upgrade system/ to CI 3.1.13 to avoid deprecation warnings.';
		}
		$items[] = $this->_item($status, 'PHP version', $php, $hint);

		foreach (array('curl' => 'cURL (WhatsApp & AI API calls)', 'mbstring' => 'mbstring (text/emoji handling)', 'mysqli' => 'MySQLi (database driver)', 'openssl' => 'OpenSSL (HTTPS)') as $ext => $label)
		{
			$loaded = extension_loaded($ext);
			$items[] = $this->_item($loaded ? 'ok' : 'fail', $label, $loaded ? 'Loaded' : 'Missing', 'Enable the ' . $ext . ' extension in your host\'s PHP configuration.');
		}

		foreach (array('logs' => APPPATH . 'logs', 'cache' => APPPATH . 'cache') as $name => $dir)
		{
			$writable = is_dir($dir) && is_writable($dir);
			$items[] = $this->_item($writable ? 'ok' : 'fail', $name . ' directory writable', $writable ? 'Yes' : 'No', 'Make sure ' . $dir . ' is writable by PHP.');
		}

		$htaccess = file_exists(FCPATH . '.htaccess');
		$items[] = $this->_item($htaccess ? 'ok' : 'warn', 'Clean URLs (.htaccess)', $htaccess ? 'Present' : 'Missing', 'Without it, URLs keep index.php (works, but ugly). On nginx, add try_files instead.');

		return $items;
	}

	protected function _check_database()
	{
		$items = array();

		$db_ok = FALSE;
		try
		{
			$db_ok = (bool)$this->db->query('SELECT 1');
		}
		catch (Exception $e)
		{
			$db_ok = FALSE;
		}
		$items[] = $this->_item($db_ok ? 'ok' : 'fail', 'Database connection', $db_ok ? 'Connected' : 'Failed', 'Check the credentials in application/config/database.php.');

		$required = array('settings', 'admin_users', 'menu_categories', 'menu_items', 'knowledge', 'customers', 'conversations', 'messages', 'orders', 'contact_messages');
		$missing = array();
		foreach ($required as $table)
		{
			if ( ! $this->db->table_exists($table))
			{
				$missing[] = $table;
			}
		}
		$items[] = $this->_item(
			$missing ? 'fail' : 'ok',
			'Database tables',
			$missing ? 'Missing: ' . implode(', ', $missing) : 'All ' . count($required) . ' tables present',
			'Import database/whatsapp_chatbot.sql — it creates all tables and seed data in one go.'
		);

		return $items;
	}

	protected function _check_config()
	{
		$items = array();

		$base = (string)$this->config->item('base_url');
		$placeholder = (strpos($base, 'testdomain') !== FALSE || strpos($base, 'localhost') !== FALSE || $base === '');
		$items[] = $this->_item($placeholder ? 'warn' : 'ok', 'Base URL configured', $base !== '' ? $base : '(empty)', 'Set the real domain in application/config/config.php.');

		$key = (string)$this->config->item('encryption_key');
		$default_key = (strpos($key, 'CHANGE_ME') !== FALSE || $key === '');
		$items[] = $this->_item($default_key ? 'warn' : 'ok', 'Encryption key', $default_key ? 'Default placeholder' : 'Set', 'Replace with a long random string in config.php.');

		$env = defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown';
		$items[] = $this->_item($env === 'production' ? 'ok' : 'warn', 'Application environment', $env, 'Set CI_ENV=production on the server so errors stay hidden.');

		$plain = FALSE;
		$users = $this->db->select('username, password')->get('admin_users')->result_array();
		$plain = FALSE;
		foreach ($users as $u)
		{
			if (strncmp($u['password'], '$2y$', 4) !== 0)
			{
				$plain = TRUE;
			}
		}
		$items[] = $this->_item($plain ? 'warn' : 'ok', 'Admin passwords hashed', $plain ? 'Plaintext password detected' : 'bcrypt hashed', $plain ? 'Update your password from the Accounts page — plaintext passwords are never accepted.' : 'All panel passwords are stored as bcrypt hashes.');

		$cron_key = $this->settings_model->get('cron_key', '');
		$items[] = $this->_item($cron_key === 'change-me' || $cron_key === '' ? 'warn' : 'ok', 'Cron secret key', $cron_key !== '' ? 'Set' : 'Default', 'Change it in Settings → Security.');

		return $items;
	}

	protected function _check_whatsapp()
	{
		$items = array();
		$s = $this->settings_model->merged();

		$fields = array(
			'wa_token'           => 'Access token',
			'wa_phone_number_id' => 'Phone number ID',
			'wa_app_secret'      => 'App secret',
			'wa_verify_token'    => 'Webhook verify token',
		);
		foreach ($fields as $key => $label)
		{
			$value = (string)$s[$key];
			$items[] = $this->_item($value !== '' ? 'ok' : 'fail', $label, $value !== '' ? 'Set' : 'Missing', 'Add it in Settings → WhatsApp Cloud API.');
		}

		if ($s['wa_token'] !== '' && $s['wa_phone_number_id'] !== '')
		{
			$this->load->library('whatsapp_api');
			$result = $this->whatsapp_api->verify_credentials();
			$items[] = $this->_item($result['ok'] ? 'ok' : 'fail', 'Live API credentials test', $result['ok'] ? 'Valid' : 'Invalid', $result['message']);
		}
		else
		{
			$items[] = $this->_item('warn', 'Live API credentials test', 'Skipped', 'Set the token and phone number ID to enable the live test.');
		}

		$items[] = $this->_item('ok', 'Webhook URL', site_url('whatsapp/webhook'), 'Enter this in the Meta dashboard under WhatsApp → Configuration. (Info — verify in Meta, not here.)');

		return $items;
	}

	protected function _check_ai()
	{
		$items = array();
		$s = $this->settings_model->merged();

		$items[] = $this->_item($s['ai_api_key'] !== '' ? 'ok' : 'fail', 'AI API key', $s['ai_api_key'] !== '' ? 'Set' : 'Missing', 'Add a key in Settings → AI (free option: Groq, no card needed).');
		$items[] = $this->_item($s['ai_model'] !== '' ? 'ok' : 'warn', 'AI model', $s['ai_model'] !== '' ? $s['ai_model'] : '(empty)', 'e.g. gpt-4o-mini (OpenAI) or deepseek-chat (DeepSeek).');

		return $items;
	}

	protected function _check_operations()
	{
		$items = array();

		$last_run = $this->settings_model->get('last_cron_run', '');
		if ($last_run === '')
		{
			$items[] = $this->_item('warn', 'Cron worker (optional)', 'Not set up', 'Not required — the webhook replies instantly on its own. Cron is only a safety net for missed messages. Add it if you want it: * * * * * php /path/index.php cron run, or cron-job.org with the cron URL.');
		}
		else
		{
			$age = time() - strtotime($last_run);
			$fresh = $age <= 300;
			$items[] = $this->_item($fresh ? 'ok' : 'warn', 'Cron worker (optional)', $fresh ? 'Running (last run ' . $age . 's ago)' : 'Last ran ' . $age . 's ago', $fresh ? 'Safety net is alive — the webhook does the instant replies.' : 'The safety net hasn\'t run in over 5 minutes. Not a problem — the webhook still replies instantly.');
		}

		$s = $this->settings_model->merged();
		$items[] = $this->_item($s['owner_wa_id'] !== '' ? 'ok' : 'warn', 'Owner notification number', $s['owner_wa_id'] !== '' ? 'Set' : 'Missing', 'Set it in Settings so you get order alerts (the owner must message the bot once first).');

		return $items;
	}

	protected function _item($status, $label, $detail, $hint)
	{
		return array('status' => $status, 'label' => $label, 'detail' => $detail, 'hint' => $hint);
	}
}
