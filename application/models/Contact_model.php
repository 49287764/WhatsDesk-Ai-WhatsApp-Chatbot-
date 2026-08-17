<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contact_model extends CI_Model
{
	public function save(array $data)
	{
		$data['created_at'] = date('Y-m-d H:i:s');
		return $this->db->insert('contact_messages', $data);
	}

	public function list_all($limit = 100)
	{
		$this->db->order_by('id', 'DESC');
		$this->db->limit((int)$limit);
		return $this->db->get('contact_messages')->result_array();
	}

	public function get($id)
	{
		return $this->db->get_where('contact_messages', array('id' => (int)$id), 1)->row_array();
	}

	public function delete($id)
	{
		$this->db->where('id', (int)$id);
		return $this->db->delete('contact_messages');
	}

	public function count_all()
	{
		return (int)$this->db->count_all('contact_messages');
	}
}
