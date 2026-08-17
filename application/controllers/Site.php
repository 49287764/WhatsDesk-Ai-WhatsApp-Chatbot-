<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Site
 *
 * Public marketing site: landing page, pricing, contact (stores messages),
 * docs, and the live demo endpoint that runs the REAL bot in preview mode.
 */
class Site extends CI_Controller
{
	protected $site_data = array();

	public function __construct()
	{
		parent::__construct();

		$phone = '';
		$name = 'Your Business';
		$hours = 'Mon–Sun: 11:00 – 22:00';
		$address = '';

		if ($this->db->table_exists('settings'))
		{
			$phone = (string)$this->settings_model->get('business_phone', '');
			$name = (string)$this->settings_model->get('business_name', 'Your Business');
			$hours = (string)$this->settings_model->get('business_hours', 'Mon–Sun: 11:00 – 22:00');
			$address = (string)$this->settings_model->get('business_address', '');
		}

		$digits = preg_replace('/\D+/', '', $phone);

		// "Create account" is only offered while the factory seed account is
		// still unclaimed. Once the owner has their account, the buttons
		// switch to "Sign in" — no more confusing dead-ends.
		$this->load->model('admin_model');
		$can_register = $this->admin_model->is_seed_account();

		$this->site_data = array(
			'wa_link'          => $digits !== '' ? 'https://wa.me/' . $digits : '',
			'business_name'  => $name !== '' ? $name : 'Your Business',
			'business_hours' => $hours,
			'business_address' => $address,
			'can_register'   => $can_register,
			'cta_url'        => $can_register ? site_url('admin/auth/register') : site_url('admin/auth/login'),
			'cta_label'      => $can_register ? 'Start free — 5 min setup' : 'Sign in to your dashboard',
		);
	}

	protected function _render($view, $data = array())
	{
		$data = array_merge($this->site_data, $data);
		$this->load->view('site/header', $data);
		$this->load->view($view, $data);
		$this->load->view('site/footer', $data);
	}

	public function index()
	{
		// Real catalog items for the live demo chips — the demo talks about
		// the actual business, never placeholder "Package" products.
		$demo_items = array();
		$first_names = array();
		if ($this->db->table_exists('menu_items'))
		{
			$items = $this->menu_model->get_items(TRUE);
			foreach ($items as $item)
			{
				$demo_items[] = array(
					'name' => $item['name'],
					'q'    => 'I want 1 ' . $item['name'],
				);
				if (count($demo_items) >= 3)
				{
					break;
				}
			}
			if ($items)
			{
				$first_names[] = $items[0]['name'];
				if (isset($items[1])) $first_names[] = $items[1]['name'];
			}
		}

		$this->_render('site/landing', array(
			'nav_active'  => 'home',
			'page_title'  => '',
			'demo_items'  => $demo_items,
			'first_names' => $first_names,
		));
	}

	public function pricing()
	{
		$this->_render('site/pricing', array(
			'nav_active' => 'pricing',
			'page_title' => 'Pricing',
		));
	}

	public function contact()
	{
		$this->_render('site/contact', array(
			'nav_active' => 'contact',
			'page_title' => 'Contact',
		));
	}

	public function docs()
	{
		$this->_render('site/docs', array(
			'nav_active' => 'docs',
			'page_title' => 'Docs',
		));
	}

	/**
	 * Live demo endpoint. Runs the real bot in preview (dry-run) mode:
	 * no WhatsApp messages are sent, no orders are created, the owner is
	 * never notified. Each browser session keeps its own demo conversation.
	 */
	public function demo_chat()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			show_404();
		}

		$body = trim((string)$this->input->post('body', TRUE));
		$reply = '';
		if ($body !== '')
		{
			$this->load->library('bot_engine');
			$reply = $this->bot_engine->preview_reply($body);
		}

		// csrf_regenerate is on, so the token is rotated after every POST.
		// Return the fresh hash so the demo JS can keep talking on the next tap.
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(array(
				'reply'     => $reply,
				'csrf_hash' => $this->security->get_csrf_hash(),
			)));
	}

	/**
	 * Store a contact form submission (shown in the admin Messages inbox).
	 */
	public function contact_send()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('site/contact');
		}

		$data = array_merge($this->site_data, array(
			'nav_active' => 'contact',
			'page_title' => 'Contact',
		));
		$data['success'] = FALSE;
		$data['errors'] = array();

		$name = trim((string)$this->input->post('name', TRUE));
		$phone = trim((string)$this->input->post('phone', TRUE));
		$email = trim((string)$this->input->post('email', TRUE));
		$message = trim((string)$this->input->post('message', TRUE));

		if ($name === '')
		{
			$data['errors'][] = 'Please enter your name.';
		}
		if ($message === '')
		{
			$data['errors'][] = 'Please write a message.';
		}
		if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$data['errors'][] = 'That email address doesn\'t look right.';
		}

		if ( ! $data['errors'])
		{
			$this->load->model('contact_model');
			$this->contact_model->save(array(
				'name'    => $name,
				'phone'   => $phone,
				'email'   => $email,
				'message' => $message,
			));
			$data['success'] = TRUE;
		}

		$this->load->view('site/header', $data);
		$this->load->view('site/contact', $data);
		$this->load->view('site/footer', $data);
	}
}
