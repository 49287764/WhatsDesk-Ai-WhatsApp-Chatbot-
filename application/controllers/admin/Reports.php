<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	/**
	 * Normalise + validate the from/to date inputs (Y-m-d). Defaults to the
	 * last 7 days. Swaps them if inverted; clamps to 366 days.
	 */
	protected function _range()
	{
		$from = (string)$this->input->get('from');
		$to   = (string)$this->input->get('to');

		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from))
		{
			$from = date('Y-m-d', strtotime('-6 days'));
		}
		if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))
		{
			$to = date('Y-m-d');
		}
		if ($from > $to)
		{
			$tmp = $from; $from = $to; $to = $tmp;
		}
		// Never allow more than ~12 months in one query.
		if ((strtotime($to) - strtotime($from)) > 366 * 86400)
		{
			$from = date('Y-m-d', strtotime($to . ' -366 days'));
		}
		return array($from, $to);
	}

	public function index()
	{
		list($from, $to) = $this->_range();

		$data = array(
			'page_title'   => 'Sales report',
			'from'         => $from,
			'to'           => $to,
			'summary'      => $this->order_model->sales_summary($from, $to),
			'orders'       => $this->order_model->in_range($from, $to),
			'top'          => $this->order_model->top_items($from, $to, 10),
			'chart'        => $this->order_model->revenue_by_day($from, $to),
			'include_chart' => TRUE,
			'cur'          => $this->settings_model->get('currency_symbol', '$'),
		);
		$this->render('admin/reports', $data);
	}

	/**
	 * Download the current range as a CSV the owner can open in Excel/Sheets.
	 */
	public function export_csv()
	{
		list($from, $to) = $this->_range();
		$orders = $this->order_model->in_range($from, $to);

		$fh = fopen('php://temp', 'r+');
		fputcsv($fh, array('Order #', 'Placed', 'Customer', 'WhatsApp', 'Address', 'Items', 'Total', 'Status'));
		foreach ($orders as $o)
		{
			$items = json_decode($o['items_json'], TRUE);
			$itemstr = '';
			if (is_array($items))
			{
				foreach ($items as $i)
				{
					$itemstr .= (int)$i['quantity'] . 'x ' . $i['name'] . '; ';
				}
			}
			fputcsv($fh, array(
				'#' . $o['id'],
				$o['created_at'],
				$o['customer_name'],
				$o['wa_id'],
				$o['customer_address'],
				trim($itemstr),
				money_fmt((float)$o['total']),
				$o['status'],
			));
		}
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);

		$this->output
			->set_content_type('text/csv')
			->set_header('Content-Disposition: attachment; filename="orders-' . $from . '_to_' . $to . '.csv"')
			->set_header('Cache-Control: no-store')
			->set_output("\xEF\xBB\xBF" . $csv); // UTF-8 BOM so Excel opens it correctly
	}
}
