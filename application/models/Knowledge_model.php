<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Knowledge_model extends CI_Model
{
	public function list_all($active_only = FALSE)
	{
		if ($active_only)
		{
			$this->db->where('active', 1);
		}
		$this->db->order_by('id', 'ASC');
		return $this->db->get('knowledge')->result_array();
	}

	public function get($id)
	{
		return $this->db->get_where('knowledge', array('id' => (int)$id), 1)->row_array();
	}

	public function save($data, $id = NULL)
	{
		if ($id)
		{
			$this->db->where('id', (int)$id);
			return $this->db->update('knowledge', $data);
		}
		return $this->db->insert('knowledge', $data);
	}

	public function delete($id)
	{
		$this->db->where('id', (int)$id);
		return $this->db->delete('knowledge');
	}

	/**
	 * Simple keyword search over the knowledge base (used by the bot tool).
	 * Returns up to $limit active entries ordered by relevance.
	 */
	public function search($query, $limit = 3)
	{
		$query = trim((string)$query);
		if ($query === '')
		{
			return array();
		}
		$all = $this->list_all(TRUE);
		$terms = preg_split('/[\s,.;!?]+/', mb_strtolower($query));
		$terms = array_filter($terms, function ($t) { return mb_strlen($t) >= 3; });

		$scored = array();
		foreach ($all as $row)
		{
			$score = 0;
			$question = mb_strtolower($row['question']);
			$keywords = mb_strtolower((string)$row['keywords']);
			$answer = mb_strtolower($row['answer']);
			foreach ($terms as $term)
			{
				if ($keywords !== '' && strpos($keywords, $term) !== FALSE)
				{
					$score += 3;
				}
				if (strpos($question, $term) !== FALSE)
				{
					$score += 2;
				}
				if (strpos($answer, $term) !== FALSE)
				{
					$score += 1;
				}
			}
			if ($score > 0)
			{
				$scored[] = array('row' => $row, 'score' => $score);
			}
		}
		usort($scored, function ($a, $b) { return $b['score'] - $a['score']; });
		$out = array();
		foreach (array_slice($scored, 0, $limit) as $hit)
		{
			$out[] = $hit['row'];
		}
		return $out;
	}
}
