<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conversation_model extends CI_Model
{
	/* ---------------- Conversations ---------------- */

	public function get_by_wa_id($wa_id)
	{
		return $this->db->get_where('conversations', array('wa_id' => $wa_id), 1)->row_array();
	}

	public function get_by_id($id)
	{
		return $this->db->get_where('conversations', array('id' => (int)$id), 1)->row_array();
	}

	/**
	 * Get (or create) the conversation for a WhatsApp user.
	 */
	public function get_or_create($wa_id, $customer_id)
	{
		$conv = $this->get_by_wa_id($wa_id);
		if ($conv)
		{
			return $conv;
		}
		$this->db->insert('conversations', array(
			'customer_id' => (int)$customer_id,
			'wa_id'       => $wa_id,
			'state'       => 'idle',
			'state_data'  => NULL,
			'bot_active'  => 1,
		));
		return $this->get_by_id($this->db->insert_id());
	}

	/**
	 * Update the bot state machine (state + state_data JSON).
	 */
	public function update_state($id, $state, array $state_data = array())
	{
		$this->db->where('id', (int)$id);
		return $this->db->update('conversations', array(
			'state'      => $state,
			'state_data' => $state_data ? json_encode($state_data) : NULL,
		));
	}

	public function set_bot_active($id, $active)
	{
		$this->db->where('id', (int)$id);
		return $this->db->update('conversations', array('bot_active' => $active ? 1 : 0));
	}

	/**
	 * Conversations for the admin Chats page, with last message + unread count.
	 * Demo conversations (wa_id starting with "demo") are excluded.
	 */
	public function list_conversations()
	{
		return $this->db->query(
			"SELECT c.id, c.wa_id, c.state, c.bot_active, c.updated_at,
			        COALESCE(cust.name, c.wa_id) AS customer_name,
			        (SELECT body FROM messages m WHERE m.conversation_id = c.id
			          ORDER BY m.id DESC LIMIT 1) AS last_message,
			        (SELECT COUNT(*) FROM messages m
			          WHERE m.conversation_id = c.id AND m.direction = 'in' AND m.status = 'received') AS unread
			 FROM conversations c
			 LEFT JOIN customers cust ON cust.id = c.customer_id
			 WHERE c.wa_id NOT LIKE 'demo%'
			 ORDER BY c.updated_at DESC"
		)->result_array();
	}

	/* ---------------- Messages ---------------- */

	/**
	 * Store an inbound message. Returns message id, or NULL if duplicate.
	 */
	public function add_inbound($conversation_id, $wa_id, $body, $wa_message_id = NULL, $type = 'text')
	{
		if ($wa_message_id)
		{
			$exists = $this->db->get_where('messages', array('wa_message_id' => $wa_message_id), 1)->row_array();
			if ($exists)
			{
				return NULL; // duplicate delivery
			}
		}
		$this->db->insert('messages', array(
			'conversation_id' => (int)$conversation_id,
			'wa_id'           => $wa_id,
			'direction'       => 'in',
			'type'            => $type,
			'body'            => $body,
			'wa_message_id'   => $wa_message_id ?: NULL,
			'status'          => 'received',
		));
		return $this->db->insert_id();
	}

	/**
	 * Store an outbound reply.
	 */
	public function add_outbound($conversation_id, $wa_id, $body, $status = 'sent', $type = 'text')
	{
		$this->db->insert('messages', array(
			'conversation_id' => (int)$conversation_id,
			'wa_id'           => $wa_id,
			'direction'       => 'out',
			'type'            => $type,
			'body'            => $body,
			'status'          => $status,
		));
		return $this->db->insert_id();
	}

	public function mark_processed($message_id)
	{
		$this->db->where('id', (int)$message_id);
		return $this->db->update('messages', array(
			'status'       => 'processed',
			'processed_at' => date('Y-m-d H:i:s'),
		));
	}

	public function get_message($id)
	{
		return $this->db->get_where('messages', array('id' => (int)$id), 1)->row_array();
	}

	/**
	 * Pending inbound messages waiting for the bot.
	 * Includes 'received' messages plus stale 'processing' locks so a crashed
	 * worker is retried instead of lost. The stale threshold (5 min) is kept
	 * comfortably above the longest possible inline processing time (two AI
	 * attempts at 60s + retry pause) so the cron safety net can never pick
	 * up a message that is still being processed by the webhook — that race
	 * used to process the same message twice and double-add cart items.
	 */
	public function pending_messages($limit = 10)
	{
		$this->db->where('direction', 'in');
		$this->db->group_start();
		$this->db->where('status', 'received');
		$this->db->or_group_start();
		$this->db->where('status', 'processing');
		$this->db->where('processed_at <', date('Y-m-d H:i:s', time() - 300));
		$this->db->group_end();
		$this->db->group_end();
		$this->db->order_by('id', 'ASC');
		$this->db->limit((int)$limit);
		return $this->db->get('messages')->result_array();
	}

	/**
	 * Lock a message for processing (prevents double replies from the
	 * webhook inline path racing with the cron worker).
	 */
	public function lock_message($id)
	{
		$this->db->where('id', (int)$id);
		return $this->db->update('messages', array(
			'status'       => 'processing',
			'processed_at' => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Release a message back to the queue (used when inline processing fails).
	 */
	public function unlock_message($id)
	{
		$this->db->where('id', (int)$id);
		return $this->db->update('messages', array(
			'status'       => 'received',
			'processed_at' => NULL,
		));
	}

	/**
	 * Recent message history for the LLM (oldest first).
	 * Returns rows with keys: role (user/assistant), content.
	 */
	public function recent_messages($conversation_id, $limit = 12)
	{
		$this->db->where('conversation_id', (int)$conversation_id);
		$this->db->order_by('id', 'DESC');
		$this->db->limit((int)$limit);
		$rows = $this->db->get('messages')->result_array();

		$out = array();
		foreach (array_reverse($rows) as $row)
		{
			$out[] = array(
				'role'    => $row['direction'] === 'in' ? 'user' : 'assistant',
				'content' => $row['body'],
			);
		}
		return $out;
	}

	/**
	 * Full message thread for the admin Chats page.
	 */
	public function thread($conversation_id)
	{
		$this->db->where('conversation_id', (int)$conversation_id);
		$this->db->order_by('id', 'ASC');
		return $this->db->get('messages')->result_array();
	}

	/**
	 * Remove landing-page demo conversations (wa_id prefix "demo") that are
	 * older than $days, plus their messages. Prevents the public demo from
	 * growing the database forever. Returns the number of conversations purged.
	 */
	public function purge_demo_data($days = 30)
	{
		$cutoff = date('Y-m-d H:i:s', time() - ((int)$days * 86400));
		$this->db->like('wa_id', 'demo', 'after');
		$this->db->where('updated_at <', $cutoff);
		$rows = $this->db->get('conversations')->result_array();
		foreach ($rows as $row)
		{
			$this->db->where('conversation_id', (int)$row['id']);
			$this->db->delete('messages');
			$this->db->where('id', (int)$row['id']);
			$this->db->delete('conversations');
		}
		return count($rows);
	}
}
