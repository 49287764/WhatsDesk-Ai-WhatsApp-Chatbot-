<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chats extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	public function index()
	{
		$data = array(
			'page_title' => 'Chats',
			'conversations' => $this->conversation_model->list_conversations(),
		);
		$this->render('admin/chats', $data);
	}

	public function view($id)
	{
		$conv = $this->conversation_model->get_by_id($id);
		if ( ! $conv)
		{
			redirect('admin/chats');
		}
		$data = array(
			'page_title' => 'Chat',
			'conv'       => $conv,
			'thread'     => $this->conversation_model->thread($id),
		);
		$this->render('admin/chat_view', $data);
	}

	/**
	 * Send a manual reply as the business. Taking over pauses the bot.
	 */
	public function reply($id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/chats/view/' . (int)$id);
		}

		$conv = $this->conversation_model->get_by_id($id);
		if ( ! $conv)
		{
			redirect('admin/chats');
		}

		$message = trim((string)$this->input->post('message', TRUE));
		if ($message === '')
		{
			$this->flash('Message is empty.', 'err');
			redirect('admin/chats/view/' . (int)$id);
		}

		$this->load->library('whatsapp_api');
		$sent = $this->whatsapp_api->send_text($conv['wa_id'], $message);
		$this->conversation_model->add_outbound($id, $conv['wa_id'], $message, $sent ? 'sent' : 'failed');

		if ($sent)
		{
			// An admin reply takes over the conversation; pause the bot.
			$this->conversation_model->set_bot_active($id, 0);
			$this->flash('Message sent. The bot is paused for this conversation.', 'ok');
		}
		else
		{
			$this->flash('Message failed to send. Check the WhatsApp settings.', 'err');
		}
		redirect('admin/chats/view/' . (int)$id);
	}

	/**
	 * Toggle the bot back on for a conversation (admin handover back).
	 */
	public function toggle_bot($id)
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$conv = $this->conversation_model->get_by_id($id);
			if ($conv)
			{
				$active = $conv['bot_active'] ? 0 : 1;
				$this->conversation_model->set_bot_active($id, $active);
				if ($active)
				{
					$this->conversation_model->update_state($id, 'idle');
				}
				$this->flash($active ? 'Bot re-enabled for this conversation.' : 'Bot paused for this conversation.', 'ok');
			}
		}
		redirect('admin/chats/view/' . (int)$id);
	}
}
