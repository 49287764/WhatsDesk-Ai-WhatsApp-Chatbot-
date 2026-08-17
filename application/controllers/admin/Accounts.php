<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounts extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
		$this->load->model('admin_model');
	}

	public function index()
	{
		$data = array(
			'page_title' => 'Accounts',
			'accounts'   => $this->admin_model->list_all(),
			'error'      => '',
			'ok'         => '',
		);

		if ($this->input->method(TRUE) === 'POST')
		{
			$username = trim((string)$this->input->post('username', TRUE));
			$password = (string)$this->input->post('password');
			$confirm  = (string)$this->input->post('confirm_password');

			if ( ! preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username))
			{
				$data['error'] = 'Username must be 3–30 characters (letters, numbers, . _ -).';
			}
			elseif ($this->admin_model->get_by_username($username))
			{
				$data['error'] = 'That username is already taken.';
			}
			elseif (strlen($password) < 8)
			{
				$data['error'] = 'Password must be at least 8 characters.';
			}
			elseif ($password !== $confirm)
			{
				$data['error'] = 'Passwords do not match.';
			}
			else
			{
				$this->admin_model->create_user($username, $password);
				$this->flash('Staff account “' . $username . '” created.', 'ok');
				redirect('admin/accounts');
			}
		}

		$this->render('admin/accounts', $data);
	}

	public function delete($id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/accounts');
		}

		$id = (int)$id;
		$target = $this->admin_model->get_by_id($id);
		if ( ! $target)
		{
			$this->flash('Account not found.', 'err');
			redirect('admin/accounts');
		}

		$me = $this->admin_user;
		if (isset($me['id']) && (int)$me['id'] === $id)
		{
			$this->flash('You can\'t delete your own account.', 'err');
			redirect('admin/accounts');
		}

		if ($this->admin_model->is_last_account($id))
		{
			$this->flash('The last account can\'t be deleted.', 'err');
			redirect('admin/accounts');
		}

		$this->admin_model->delete_user($id);
		$this->flash('Account “' . html_escape($target['username']) . '” deleted.', 'ok');
		redirect('admin/accounts');
	}
}
