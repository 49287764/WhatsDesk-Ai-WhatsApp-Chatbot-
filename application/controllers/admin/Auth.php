<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('admin_model');
	}

	public function login()
	{
		if ($this->admin_logged_in)
		{
			redirect('admin/dashboard');
		}

		$data = array('page_title' => 'Login', 'error' => '');
		$data['is_seed'] = $this->admin_model->is_seed_account();

		// Brute-force protection: lock out for 15 min after 5 failed attempts.
		$locked_until = (int)$this->session->userdata('login_locked_until');
		if (time() < $locked_until)
		{
			$mins = (int)ceil(($locked_until - time()) / 60);
			$data['error'] = 'Too many failed attempts. Try again in ' . $mins . ' minute' . ($mins === 1 ? '' : 's') . '.';
			$this->load->view('admin/login', $data);
			return;
		}

		if ($this->input->method(TRUE) === 'POST')
		{
			$username = trim((string)$this->input->post('username', TRUE));
			$password = (string)$this->input->post('password'); // raw, no XSS filter

			if ($username !== '' && $password !== '')
			{
				$user = $this->admin_model->verify($username, $password);
				if ($user)
				{
					$this->session->unset_userdata(array('login_attempts', 'login_locked_until'));
					$this->session->set_userdata(array(
						'admin_logged_in' => TRUE,
						'admin_user'      => $user,
					));
					$this->_redirect_after_login($user);
				}
			}

			$attempts = (int)$this->session->userdata('login_attempts') + 1;
			if ($attempts >= 5)
			{
				$this->session->set_userdata(array(
					'login_attempts'    => 0,
					'login_locked_until' => time() + 900,
				));
				$data['error'] = 'Too many failed attempts. Try again in 15 minutes.';
			}
			else
			{
				$this->session->set_userdata('login_attempts', $attempts);
				$data['error'] = 'Invalid username or password.';
			}
		}

		$this->load->view('admin/login', $data);
	}

	/**
	 * Where to send the admin right after logging in:
	 *  1. setup not finished -> the guided setup page
	 *  2. otherwise          -> dashboard
	 */
	protected function _redirect_after_login($user = NULL)
	{
		if ($this->_setup_incomplete())
		{
			$this->session->set_flashdata('msg', 'Welcome! Your assistant is almost ready — follow the steps to connect WhatsApp and the AI.');
			redirect('admin/setup');
		}
		redirect('admin/dashboard');
	}

	protected function _setup_incomplete()
	{
		$s = $this->settings_model->merged();
		return ($s['wa_token'] === '' || $s['wa_phone_number_id'] === ''
			|| $s['ai_api_key'] === '' || $s['cron_key'] === 'change-me' || $s['cron_key'] === '');
	}

	/**
	 * Create / claim the panel account (the site's "Create account" button).
	 * Only possible while the factory seed account is still in place, so a
	 * fresh owner can claim it — afterwards it redirects to login.
	 */
	public function register()
	{
		if ($this->admin_logged_in)
		{
			redirect('admin/dashboard');
		}

		$data = array('page_title' => 'Create your account', 'error' => '');

		// If a real (non-seed) account already exists, point to login.
		if ( ! $this->admin_model->is_seed_account())
		{
			redirect('admin/auth/login');
		}

		if ($this->input->method(TRUE) === 'POST')
		{
			$username = trim((string)$this->input->post('username', TRUE));
			$password = (string)$this->input->post('password');
			$confirm = (string)$this->input->post('confirm_password');

			$errors = array();
			if ( ! preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username))
			{
				$errors[] = 'Username must be 3–30 characters (letters, numbers, . _ -).';
			}
			if (strlen($password) < 8)
			{
				$errors[] = 'Password must be at least 8 characters.';
			}
			if ($password !== $confirm)
			{
				$errors[] = 'Passwords do not match.';
			}
			if ($errors)
			{
				$data['error'] = implode(' ', $errors);
			}
			else
			{
				$this->admin_model->create_account($username, $password);
				$user = $this->admin_model->verify($username, $password);
				$this->session->set_userdata(array(
					'admin_logged_in' => TRUE,
					'admin_user'      => $user,
				));
				$this->session->set_flashdata('msg', 'Account created! Now let\'s connect your WhatsApp and AI.');
				redirect('admin/setup');
			}
		}

		$this->load->view('admin/register', $data);
	}

	public function logout()
	{
		$this->session->sess_destroy();
		redirect('admin/auth/login');
	}

	public function change_password()
	{
		$this->require_admin();

		$data = array('page_title' => 'Change Password', 'error' => '', 'ok' => '');

		if ($this->input->method(TRUE) === 'POST')
		{
			$current = (string)$this->input->post('current_password');
			$new = (string)$this->input->post('new_password');
			$confirm = (string)$this->input->post('confirm_password');

			if (strlen($new) < 8)
			{
				$data['error'] = 'New password must be at least 8 characters.';
			}
			elseif ($new !== $confirm)
			{
				$data['error'] = 'New passwords do not match.';
			}
			else
			{
				$username = $this->admin_user['username'];
				if ($this->admin_model->change_password($username, $current, $new))
				{
					// A brand-new account should still finish the guided setup;
					// otherwise send the admin back to the Accounts page.
					if ($this->_setup_incomplete())
					{
						$this->session->set_flashdata('msg', 'Password saved! Continue with the guided setup.');
						redirect('admin/setup');
					}
					$this->flash('Password changed successfully.', 'ok');
					redirect('admin/accounts');
				}
				else
				{
					$data['error'] = 'Current password is incorrect.';
				}
			}
		}

		$this->render('admin/change_password', $data);
	}
}
