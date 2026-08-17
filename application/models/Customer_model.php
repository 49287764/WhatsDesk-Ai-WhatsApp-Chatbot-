<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends CI_Model
{
	/**
	 * Get or create a customer by WhatsApp ID.
	 */
	public function get_or_create($wa_id, $name = NULL)
	{
		$row = $this->db->get_where('customers', array('wa_id' => $wa_id), 1)->row_array();
		if ($row)
		{
			if ($name && $name !== $row['name'])
			{
				$this->db->where('id', $row['id']);
				$this->db->update('customers', array('name' => $name));
			}
			return $row;
		}
		$this->db->insert('customers', array(
			'wa_id'  => $wa_id,
			'name'   => $name ?: NULL,
			'created_at' => date('Y-m-d H:i:s'),
		));
		return $this->db->get_where('customers', array('id' => $this->db->insert_id()), 1)->row_array();
	}

	public function touch($id)
	{
		$this->db->where('id', (int)$id);
		return $this->db->update('customers', array('last_seen_at' => date('Y-m-d H:i:s')));
	}

	public function get($id)
	{
		return $this->db->get_where('customers', array('id' => (int)$id), 1)->row_array();
	}

	public function count_all()
	{
		// Exclude landing-page demo guests.
		return (int)$this->db->not_like('wa_id', 'demo', 'after')->count_all_results('customers');
	}

	/**
	 * Remove landing-page demo guests (wa_id prefix "demo") that have no
	 * remaining conversations — called after conversation purge so demo
	 * data never accumulates in the customers table either.
	 */
	public function purge_demo_guests()
	{
		$rows = $this->db->like('wa_id', 'demo', 'after')->get('customers')->result_array();
		$removed = 0;
		foreach ($rows as $row)
		{
			$conv = $this->db->get_where('conversations', array('customer_id' => (int)$row['id']), 1)->row_array();
			if ( ! $conv)
			{
				$this->db->where('id', (int)$row['id']);
				$this->db->delete('customers');
				$removed++;
			}
		}
		return $removed;
	}
}
