<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Order_model extends CI_Model
{
	public function create(array $data)
	{
		$data['created_at'] = date('Y-m-d H:i:s');
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->insert('orders', $data);
		return $this->db->insert_id();
	}

	public function get($id)
	{
		return $this->db->get_where('orders', array('id' => (int)$id), 1)->row_array();
	}

	public function list_all($status = '', $limit = 100, $offset = 0)
	{
		$this->db->from('orders');
		if ($status !== '')
		{
			$this->db->where('status', $status);
		}
		$this->db->order_by('id', 'DESC');
		$this->db->limit((int)$limit, (int)$offset);
		return $this->db->get()->result_array();
	}

	public function update_status($id, $status)
	{
		$this->db->where('id', (int)$id);
		return $this->db->update('orders', array(
			'status'     => $status,
			'updated_at' => date('Y-m-d H:i:s'),
		));
	}

	public function count_by_status()
	{
		$rows = $this->db->select('status, COUNT(*) AS total')
			->group_by('status')
			->get('orders')
			->result_array();
		$out = array('placed' => 0, 'confirmed' => 0, 'preparing' => 0, 'ready' => 0, 'delivered' => 0, 'cancelled' => 0);
		foreach ($rows as $row)
		{
			if (isset($out[$row['status']]))
			{
				$out[$row['status']] = (int)$row['total'];
			}
		}
		return $out;
	}

	public function recent($limit = 5)
	{
		$this->db->order_by('id', 'DESC');
		$this->db->limit((int)$limit);
		return $this->db->get('orders')->result_array();
	}

	public function revenue_today()
	{
		$row = $this->db->select('COALESCE(SUM(total),0) AS total')
			->where('status !=', 'cancelled')
			->where('DATE(created_at)', date('Y-m-d'))
			->get('orders')
			->row_array();
		return $row ? (float)$row['total'] : 0.0;
	}

	/**
	 * Revenue (non-cancelled) per day for the last $days days.
	 * Returns array('labels' => array, 'values' => array).
	 */
	public function revenue_per_day($days = 7)
	{
		$rows = $this->db->select('DATE(created_at) AS day, COALESCE(SUM(total),0) AS total')
			->where('status !=', 'cancelled')
			->where('created_at >=', date('Y-m-d', strtotime('-' . ((int)$days - 1) . ' days')))
			->group_by('day')
			->order_by('day', 'ASC')
			->get('orders')
			->result_array();
		$map = array();
		foreach ($rows as $r)
		{
			$map[$r['day']] = (float)$r['total'];
		}
		$labels = array();
		$values = array();
		for ($i = (int)$days - 1; $i >= 0; $i--)
		{
			$d = date('Y-m-d', strtotime('-' . $i . ' days'));
			$labels[] = date('D', strtotime($d));
			$values[] = isset($map[$d]) ? $map[$d] : 0;
		}
		return array('labels' => $labels, 'values' => $values);
	}

	/**
	 * Order counts per day for the last $days days (for the dashboard chart).
	 * Returns array('labels' => array, 'values' => array).
	 */
	public function orders_per_day($days = 7)
	{
		$rows = $this->db->select('DATE(created_at) AS day, COUNT(*) AS total')
			->where('created_at >=', date('Y-m-d', strtotime('-' . ((int)$days - 1) . ' days')))
			->group_by('day')
			->order_by('day', 'ASC')
			->get('orders')
			->result_array();
		$map = array();
		foreach ($rows as $r)
		{
			$map[$r['day']] = (int)$r['total'];
		}
		$labels = array();
		$values = array();
		for ($i = (int)$days - 1; $i >= 0; $i--)
		{
			$d = date('Y-m-d', strtotime('-' . $i . ' days'));
			$labels[] = date('D', strtotime($d));
			$values[] = isset($map[$d]) ? $map[$d] : 0;
		}
		return array('labels' => $labels, 'values' => $values);
	}

	/* ---------------- Sales report (admin/reports) ---------------- */

	/**
	 * All orders inside an inclusive date range (newest first).
	 */
	public function in_range($from, $to)
	{
		$this->db->from('orders');
		if ($from !== '')
		{
			$this->db->where('created_at >=', $from . ' 00:00:00');
		}
		if ($to !== '')
		{
			$this->db->where('created_at <=', $to . ' 23:59:59');
		}
		$this->db->order_by('created_at', 'DESC');
		return $this->db->get()->result_array();
	}

	/**
	 * Sales summary for a date range (cancelled orders excluded from money).
	 */
	public function sales_summary($from, $to)
	{
		$this->db->select('COUNT(*) AS orders,'
			. ' COALESCE(SUM(CASE WHEN status != "cancelled" THEN total ELSE 0 END),0) AS revenue,'
			. ' COALESCE(AVG(CASE WHEN status != "cancelled" THEN total END),0) AS avg_order');
		if ($from !== '')
		{
			$this->db->where('created_at >=', $from . ' 00:00:00');
		}
		if ($to !== '')
		{
			$this->db->where('created_at <=', $to . ' 23:59:59');
		}
		$row = $this->db->get('orders')->row_array();
		return array(
			'orders'    => (int)$row['orders'],
			'revenue'   => (float)$row['revenue'],
			'avg_order' => (float)$row['avg_order'],
		);
	}

	/**
	 * Top-selling items for a range, aggregated from items_json.
	 * Returns name => array('qty' => int, 'revenue' => float) sorted by qty.
	 */
	public function top_items($from, $to, $limit = 10)
	{
		$agg = array();
		foreach ($this->in_range($from, $to) as $o)
		{
			if ($o['status'] === 'cancelled')
			{
				continue;
			}
			$items = json_decode($o['items_json'], TRUE);
			if ( ! is_array($items))
			{
				continue;
			}
			foreach ($items as $i)
			{
				$name = isset($i['name']) ? (string)$i['name'] : 'Item';
				$qty = isset($i['quantity']) ? (int)$i['quantity'] : 0;
				$price = isset($i['price']) ? (float)$i['price'] : 0.0;
				if ( ! isset($agg[$name]))
				{
					$agg[$name] = array('qty' => 0, 'revenue' => 0.0);
				}
				$agg[$name]['qty'] += $qty;
				$agg[$name]['revenue'] += $price * $qty;
			}
		}
		uasort($agg, function ($a, $b) { return $b['qty'] - $a['qty']; });
		return array_slice($agg, 0, (int)$limit, TRUE);
	}

	/**
	 * Per-day orders + revenue between two dates (for the report chart).
	 * Returns array('labels' => array, 'orders' => array, 'revenue' => array).
	 */
	public function revenue_by_day($from, $to)
	{
		$rows = $this->db->select('DATE(created_at) AS day, COUNT(*) AS orders,'
			. ' COALESCE(SUM(CASE WHEN status != "cancelled" THEN total ELSE 0 END),0) AS revenue')
			->where('created_at >=', $from . ' 00:00:00')
			->where('created_at <=', $to . ' 23:59:59')
			->group_by('day')
			->order_by('day', 'ASC')
			->get('orders')
			->result_array();
		$map = array();
		foreach ($rows as $r)
		{
			$map[$r['day']] = array('orders' => (int)$r['orders'], 'revenue' => (float)$r['revenue']);
		}
		$labels = array();
		$orders = array();
		$revenue = array();
		$d = new DateTime($from);
		$end = new DateTime($to);
		$end->modify('+1 day');
		$step = new DateInterval('P1D');
		while ($d < $end)
		{
			$key = $d->format('Y-m-d');
			$labels[] = $d->format('M j');
			$orders[] = isset($map[$key]) ? $map[$key]['orders'] : 0;
			$revenue[] = isset($map[$key]) ? $map[$key]['revenue'] : 0;
			$d->add($step);
		}
		return array('labels' => $labels, 'orders' => $orders, 'revenue' => $revenue);
	}
}
