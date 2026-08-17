<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu_model extends CI_Model
{
	/* ---------------- Categories ---------------- */

	public function get_categories()
	{
		return $this->db->order_by('sort_order', 'ASC')->get('menu_categories')->result_array();
	}

	public function get_category($id)
	{
		return $this->db->get_where('menu_categories', array('id' => (int)$id), 1)->row_array();
	}

	public function save_category($data, $id = NULL)
	{
		if ($id)
		{
			$this->db->where('id', (int)$id);
			return $this->db->update('menu_categories', $data);
		}
		return $this->db->insert('menu_categories', $data);
	}

	public function delete_category($id)
	{
		// Uncategorize every item that belonged to this category first
		// (the DB FK also does ON DELETE SET NULL — this makes the state
		// explicit and safe even if the FK is missing on an old install).
		$this->db->where('category_id', (int)$id);
		$this->db->update('menu_items', array('category_id' => NULL));
		$this->db->where('id', (int)$id);
		return $this->db->delete('menu_categories');
	}

	/* ---------------- Items ---------------- */

	public function get_items($available_only = FALSE, $category_id = NULL)
	{
		$this->db->select('menu_items.*, menu_categories.name AS category_name');
		$this->db->from('menu_items');
		$this->db->join('menu_categories', 'menu_categories.id = menu_items.category_id', 'LEFT');
		if ($available_only)
		{
			$this->db->where('menu_items.available', 1);
		}
		if ($category_id !== NULL)
		{
			$this->db->where('menu_items.category_id', (int)$category_id);
		}
		$this->db->order_by('menu_items.sort_order', 'ASC');
		$this->db->order_by('menu_items.name', 'ASC');
		return $this->db->get()->result_array();
	}

	public function get_item($id)
	{
		$this->db->select('menu_items.*, menu_categories.name AS category_name');
		$this->db->from('menu_items');
		$this->db->join('menu_categories', 'menu_categories.id = menu_items.category_id', 'LEFT');
		$this->db->where('menu_items.id', (int)$id);
		return $this->db->get()->row_array();
	}

	/**
	 * Find an available item by (fuzzy) name match.
	 */
	public function find_by_name($name)
	{
		$name = trim((string)$name);
		$rows = $this->db
			->where('available', 1)
			->like('name', $name, 'both')
			->order_by('name', 'ASC')
			->get('menu_items')
			->result_array();
		foreach ($rows as $row)
		{
			// Prefer an exact (case-insensitive) match.
			if (strcasecmp(trim($row['name']), $name) === 0)
			{
				return $row;
			}
		}
		return $rows ? $rows[0] : NULL;
	}

	public function save_item($data, $id = NULL)
	{
		if ($id)
		{
			$this->db->where('id', (int)$id);
			return $this->db->update('menu_items', $data);
		}
		return $this->db->insert('menu_items', $data);
	}

	public function delete_item($id)
	{
		$this->db->where('id', (int)$id);
		return $this->db->delete('menu_items');
	}
}
