<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Knowledge extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	public function index()
	{
		$data = array(
			'page_title' => 'Knowledge Base',
			'entries'    => $this->knowledge_model->list_all(),
		);
		$this->render('admin/knowledge', $data);
	}

	public function form($id = 0)
	{
		$data = array(
			'page_title' => $id ? 'Edit Knowledge Entry' : 'Add Knowledge Entry',
			'entry'      => $id ? $this->knowledge_model->get($id) : NULL,
		);

		if ($this->input->method(TRUE) === 'POST')
		{
			$save = array(
				'question' => trim((string)$this->input->post('question', TRUE)),
				'keywords' => trim((string)$this->input->post('keywords', TRUE)),
				'answer'   => trim((string)$this->input->post('answer', TRUE)),
				'active'   => $this->input->post('active') ? 1 : 0,
			);
			if ($save['question'] === '' || $save['answer'] === '')
			{
				$this->flash('Question and answer are required.', 'err');
				redirect('admin/knowledge/form/' . (int)$id);
			}
			$this->knowledge_model->save($save, $id ? (int)$id : NULL);
			$this->flash('Knowledge entry saved.', 'ok');
			redirect('admin/knowledge');
		}

		$this->render('admin/knowledge_form', $data);
	}

	public function delete($id)
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$this->knowledge_model->delete($id);
			$this->flash('Knowledge entry deleted.', 'ok');
		}
		redirect('admin/knowledge');
	}
}
