<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	public function index($status = '')
	{
		$status = (string)$status;
		$allowed = array('placed', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled');
		if ($status !== '' && ! in_array($status, $allowed, TRUE))
		{
			$status = '';
		}

		$data = array(
			'page_title' => 'Orders',
			'orders'     => $this->order_model->list_all($status, 100),
			'status'     => $status,
			'counts'     => $this->order_model->count_by_status(),
			'cur'        => $this->settings_model->get('currency_symbol', '$'),
		);
		$this->render('admin/orders', $data);
	}

	public function view($id)
	{
		$order = $this->order_model->get($id);
		if ( ! $order)
		{
			redirect('admin/orders');
		}

		$items = json_decode($order['items_json'], TRUE);
		$data = array(
			'page_title'     => 'Order #' . $id,
			'order'          => $order,
			'items'          => is_array($items) ? $items : array(),
			'order_template' => $this->settings_model->get('wa_order_template', ''),
			'cur'            => $this->settings_model->get('currency_symbol', '$'),
		);
		$this->render('admin/order_view', $data);
	}

	public function update_status($id)
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$status = (string)$this->input->post('status', TRUE);
			$allowed = array('placed', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled');
			if (in_array($status, $allowed, TRUE))
			{
				$this->order_model->update_status($id, $status);
				$this->flash('Order #' . $id . ' status updated to ' . $status . '.', 'ok');
				$this->_auto_notify($id, $status);
			}
		}
		redirect('admin/orders/view/' . (int)$id);
	}

	/**
	 * Same as update_status but returns to the orders list (used by the
	 * quick inline status control on the list page).
	 */
	public function quick_status($id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/orders');
		}

		$status = (string)$this->input->post('status', TRUE);
		$allowed = array('placed', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled');
		if (in_array($status, $allowed, TRUE))
		{
			$this->order_model->update_status($id, $status);
			$this->flash('Order #' . $id . ' moved to “' . $status . '”.', 'ok');
			$this->_auto_notify($id, $status);
		}

		// Return to the list, keeping the active filter.
		$back = (string)$this->input->post('back', TRUE);
		$back = in_array($back, $allowed, TRUE) ? $back : '';
		redirect('admin/orders' . ($back !== '' ? '/index/' . $back : ''));
	}

	/**
	 * Bulk status change from the orders list (checkboxes).
	 */
	public function bulk_status()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/orders');
		}

		$status = (string)$this->input->post('status', TRUE);
		$allowed = array('placed', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled');
		$ids = (array)$this->input->post('ids');
		$ids = array_filter(array_map('intval', $ids), function ($id) { return $id > 0; });

		if ( ! in_array($status, $allowed, TRUE) || ! $ids)
		{
			$this->flash('Select at least one order and a status.', 'err');
			redirect('admin/orders');
		}

		$updated = 0;
		foreach ($ids as $id)
		{
			$this->order_model->update_status($id, $status);
			$updated++;
			$this->_auto_notify($id, $status);
		}
		$this->flash($updated . ' order' . ($updated === 1 ? '' : 's') . ' moved to “' . $status . '”.', 'ok');
		redirect('admin/orders');
	}

	/**
	 * Optional: automatically message the customer when their order status
	 * changes (Settings → Bot behavior). Free-form inside the 24h window,
	 * approved template outside it. Silent if neither is possible.
	 */
	protected function _auto_notify($id, $status)
	{
		if ($this->settings_model->get('wa_notify_status', '0') !== '1')
		{
			return;
		}

		$order = $this->order_model->get($id);
		if ( ! $order)
		{
			return;
		}

		$template = $this->settings_model->get('wa_order_template', '');
		$text = $this->settings_model->get('wa_status_message', 'Your order #{order_id} is now {status}.');
		$text = str_replace(
			array('{order_id}', '{status}', '{business_name}'),
			array($order['id'], $status, $this->settings_model->get('business_name', 'our business')),
			$text
		);

		$this->load->library('whatsapp_api');
		if ( ! $this->whatsapp_api->is_configured())
		{
			return;
		}

		$customer = $this->customer_model->get($order['customer_id']);
		$window_open = $customer && ! empty($customer['last_seen_at'])
			&& (strtotime($customer['last_seen_at']) > (time() - 86400));

		$sent = FALSE;
		if ($window_open)
		{
			$sent = $this->whatsapp_api->send_text($order['wa_id'], $text);
		}
		elseif ($template !== '')
		{
			$sent = $this->whatsapp_api->send_template($order['wa_id'], $template, 'en_US', array(
				array('type' => 'body', 'parameters' => array(
					array('type' => 'text', 'text' => '#' . $order['id']),
					array('type' => 'text', 'text' => $status),
				)),
			));
		}

		if ($sent)
		{
			$conv = $this->conversation_model->get_by_wa_id($order['wa_id']);
			if ($conv)
			{
				$this->conversation_model->add_outbound($conv['id'], $order['wa_id'], $text, 'sent');
			}
		}
	}

	/**
	 * Send a free-form status update to the customer.
	 * Only works if the customer messaged within the last 24h (open window).
	 */
	public function notify($id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/orders/view/' . (int)$id);
		}

		$order = $this->order_model->get($id);
		if ( ! $order)
		{
			redirect('admin/orders');
		}

		$message = trim((string)$this->input->post('message', TRUE));
		if ($message === '')
		{
			$this->flash('Message is empty.', 'err');
			redirect('admin/orders/view/' . (int)$id);
		}

		$this->load->library('whatsapp_api');
		$customer = $this->customer_model->get($order['customer_id']);

		$window_open = FALSE;
		if ($customer && ! empty($customer['last_seen_at']))
		{
			$window_open = (strtotime($customer['last_seen_at']) > (time() - 86400));
		}

		if ( ! $window_open)
		{
			$this->flash('The 24h customer window is closed, so free-form messages are blocked by WhatsApp. Use an approved template or ask the customer to message first.', 'err');
			redirect('admin/orders/view/' . (int)$id);
		}

		$sent = $this->whatsapp_api->send_text($order['wa_id'], $message);
		if ($sent)
		{
			$conv = $this->conversation_model->get_by_wa_id($order['wa_id']);
			if ($conv)
			{
				$this->conversation_model->add_outbound($conv['id'], $order['wa_id'], $message, 'sent');
			}
			$this->flash('Message sent to the customer.', 'ok');
		}
		else
		{
			$this->flash('Message failed to send. Check the WhatsApp settings.', 'err');
		}
		redirect('admin/orders/view/' . (int)$id);
	}

	/**
	 * Send an approved template message (works even outside the 24h window).
	 * Requires a template approved in the Meta dashboard whose body has
	 * exactly two parameters: order number and status.
	 */
	public function send_template($id)
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			redirect('admin/orders/view/' . (int)$id);
		}

		$order = $this->order_model->get($id);
		if ( ! $order)
		{
			redirect('admin/orders');
		}

		$template = $this->settings_model->get('wa_order_template', '');
		if ($template === '')
		{
			$this->flash('No order template configured — set the template name in Settings first.', 'err');
			redirect('admin/orders/view/' . (int)$id);
		}

		$this->load->library('whatsapp_api');
		$components = array(
			array(
				'type'       => 'body',
				'parameters' => array(
					array('type' => 'text', 'text' => '#' . $order['id']),
					array('type' => 'text', 'text' => $order['status']),
				),
			),
		);
		$sent = $this->whatsapp_api->send_template($order['wa_id'], $template, 'en_US', $components);

		$this->flash($sent
			? 'Template message sent to the customer.'
			: 'Template send failed — check the template name, approval status and language.',
			$sent ? 'ok' : 'err');
		redirect('admin/orders/view/' . (int)$id);
	}
}
