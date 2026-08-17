<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller
 *
 * Base controller. Admin controllers extend this class and call
 * $this->require_admin() to protect their pages.
 */
class MY_Controller extends CI_Controller
{
	protected $admin_logged_in = FALSE;
	protected $admin_user = NULL;

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');

		$logged_in = $this->session->userdata('admin_logged_in');
		if ($logged_in)
		{
			$this->admin_logged_in = TRUE;
			$this->admin_user = $this->session->userdata('admin_user');
		}
	}

	/**
	 * Redirect to login if the admin is not authenticated.
	 */
	protected function require_admin()
	{
		if ( ! $this->admin_logged_in)
		{
			redirect('admin/auth/login');
		}
	}

	/**
	 * Escape a value for safe HTML output (wraps html_escape()).
	 */
	protected function e($value)
	{
		return html_escape($value);
	}

	/**
	 * Load an admin view with the shared layout.
	 */
	protected function render($view, $data = array())
	{
		$data['page_title'] = isset($data['page_title']) ? $data['page_title'] : 'Dashboard';
		$data['page_sub'] = isset($data['page_sub']) ? $data['page_sub'] : '';
		$this->load->view('admin/header', $data);
		$this->load->view($view, $data);
		$this->load->view('admin/footer', $data);
	}

	/**
	 * Set a flash message with a type so the UI can render it as a
	 * success (ok), error (err) or info toast/alert.
	 */
	protected function flash($message, $type = 'ok')
	{
		$this->session->set_flashdata('msg', $message);
		$this->session->set_flashdata('msg_type', in_array($type, array('ok', 'err', 'info'), TRUE) ? $type : 'info');
	}
}
