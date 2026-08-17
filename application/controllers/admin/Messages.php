<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Messages extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	public function index()
	{
		$data = array(
			'page_title' => 'Messages',
			'page_sub'   => 'Contact form submissions from your website.',
			'messages'   => $this->contact_model->list_all(),
		);
		$this->render('admin/messages', $data);
	}

	public function delete($id)
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$this->contact_model->delete($id);
			$this->flash('Message deleted.', 'ok');
		}
		redirect('admin/messages');
	}
}
