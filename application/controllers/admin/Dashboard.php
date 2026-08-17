<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	public function index()
	{
		$data = array('page_title' => 'Dashboard', 'include_chart' => TRUE);

		$data['order_counts'] = $this->order_model->count_by_status();
		$data['revenue_today'] = $this->order_model->revenue_today();
		$data['customers_total'] = $this->customer_model->count_all();
		$data['recent_orders'] = $this->order_model->recent(6);
		$data['chart'] = $this->order_model->orders_per_day(7);
		$data['revenue_chart'] = $this->order_model->revenue_per_day(7);
		$data['status_labels'] = array('Placed', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Cancelled');
		$status_map = $data['order_counts']; // placed/confirmed/preparing/ready/delivered/cancelled
		$data['status_values'] = array(
			(int)$status_map['placed'], (int)$status_map['confirmed'], (int)$status_map['preparing'],
			(int)$status_map['ready'], (int)$status_map['delivered'], (int)$status_map['cancelled'],
		);

		$conversations = $this->conversation_model->list_conversations();
		$unread = 0;
		foreach ($conversations as $c)
		{
			$unread += (int)$c['unread'];
		}
		$data['unread_chats'] = $unread;

		$settings = $this->settings_model->merged();
		$data['cur'] = $settings['currency_symbol'];
		$data['wa_configured'] = $settings['wa_token'] !== '' && $settings['wa_phone_number_id'] !== '';
		$data['ai_configured'] = $settings['ai_api_key'] !== '';
		$data['owner_set'] = $settings['owner_wa_id'] !== '';

		$this->load->model('admin_model');
		$doc_set = isset($settings['business_document']) && trim((string)$settings['business_document']) !== '';
		$data['biz_set'] = $doc_set || (isset($settings['business_name']) && $settings['business_name'] !== '' && $settings['business_name'] !== 'Your Business');
		$data['cron_set'] = $settings['cron_key'] !== '' && $settings['cron_key'] !== 'change-me';
		$data['account_set'] = ! $this->admin_model->is_seed_account();

		// Mirror the top-bar pill: is the cron worker actually beating?
		$last_run = $this->settings_model->get('last_cron_run', '');
		$data['worker_ok'] = ($last_run !== '' && (time() - strtotime($last_run)) <= 300);

		// Live WhatsApp credential check (cached ~3 min so the dashboard
		// stays snappy; ?check=1 forces a fresh check).
		$data['wa_valid'] = FALSE;
		$data['wa_check_msg'] = '';
		$force = $this->input->get('check') === '1';
		$cache = $this->session->userdata('wa_health_cache');
		$now = time();
		if ($force || ! is_array($cache) || ! isset($cache['at']) || ($now - (int)$cache['at']) > 180)
		{
			$this->load->library('whatsapp_api');
			$result = $this->whatsapp_api->verify_credentials();
			$this->session->set_userdata('wa_health_cache', array(
				'at'      => $now,
				'ok'      => (bool)$result['ok'],
				'message' => (string)$result['message'],
			));
			$cache = array('at' => $now, 'ok' => (bool)$result['ok'], 'message' => (string)$result['message']);
		}
		$data['wa_valid'] = ! empty($cache['ok']);
		$data['wa_check_msg'] = isset($cache['message']) ? $cache['message'] : '';

		// Launch checklist (mirrors the Setup guide steps).
		$data['launch'] = array(
			array('label' => 'Create your account',        'done' => $data['account_set'], 'url' => 'admin/accounts'),
			array('label' => 'Add your business info',     'done' => $data['biz_set'],     'url' => 'admin/business_info'),
			array('label' => 'Connect WhatsApp',           'done' => $data['wa_configured'], 'url' => 'admin/settings#sec-whatsapp'),
			array('label' => 'Add your AI key',            'done' => $data['ai_configured'], 'url' => 'admin/settings#sec-ai'),
			array('label' => 'Set notification number',    'done' => $data['owner_set'],   'url' => 'admin/settings#sec-whatsapp'),
			array('label' => 'Secure the cron key',        'done' => $data['cron_set'],    'url' => 'admin/settings#sec-security'),
		);
		$done_count = 0;
		foreach ($data['launch'] as $item)
		{
			if ($item['done']) $done_count++;
		}
		$data['launch_done'] = $done_count;
		$data['launch_total'] = count($data['launch']);
		$data['setup_complete'] = ($done_count === count($data['launch']));

		$hour = (int)date('G');
		$data['greeting'] = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
		$data['admin_name'] = isset($this->admin_user['username']) ? $this->admin_user['username'] : 'Admin';

		$this->render('admin/dashboard', $data);
	}
}
