<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_admin();
	}

	/* ---------------- Items ---------------- */

	public function index()
	{
		$data = array(
			'page_title' => 'Catalog',
			'items'      => $this->menu_model->get_items(),
			'categories' => $this->menu_model->get_categories(),
			'cur'        => $this->settings_model->get('currency_symbol', '$'),
		);
		$this->render('admin/menu', $data);
	}

	public function item_form($id = 0)
	{
		$data = array(
			'page_title' => $id ? 'Edit Item' : 'Add Item',
			'item'       => $id ? $this->menu_model->get_item($id) : NULL,
			'categories' => $this->menu_model->get_categories(),
			'cur'        => $this->settings_model->get('currency_symbol', '$'),
		);
		if ( ! $data['item'] && $id)
		{
			redirect('admin/menu');
		}

		if ($this->input->method(TRUE) === 'POST')
		{
			$save = array(
				'category_id' => (int)$this->input->post('category_id') ?: NULL,
				'name'        => trim((string)$this->input->post('name', TRUE)),
				'description' => trim((string)$this->input->post('description', TRUE)),
				'price'       => (float)$this->input->post('price'),
				'available'   => $this->input->post('available') ? 1 : 0,
				'sort_order'  => (int)$this->input->post('sort_order'),
			);
			if ($save['name'] === '' || $save['price'] <= 0)
			{
				$this->flash('Name and a valid price are required.', 'err');
				redirect('admin/menu/item_form/' . (int)$id);
			}
			$this->menu_model->save_item($save, $id ? (int)$id : NULL);
			$this->flash('Item saved.', 'ok');
			redirect('admin/menu');
		}

		$this->render('admin/menu_item_form', $data);
	}

	public function delete_item($id)
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$this->menu_model->delete_item($id);
			$this->flash('Item deleted.', 'ok');
		}
		redirect('admin/menu');
	}

	public function toggle_item($id)
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$item = $this->menu_model->get_item($id);
			if ($item)
			{
				$this->menu_model->save_item(array('available' => $item['available'] ? 0 : 1), $id);
			}
		}
		redirect('admin/menu');
	}

	/* ---------------- Categories ---------------- */

	public function category_form($id = 0)
	{
		$data = array(
			'page_title' => $id ? 'Edit Category' : 'Add Category',
			'category'   => $id ? $this->menu_model->get_category($id) : NULL,
		);

		if ($this->input->method(TRUE) === 'POST')
		{
			$name = trim((string)$this->input->post('name', TRUE));
			if ($name === '')
			{
				$this->flash('Category name is required.', 'err');
				redirect('admin/menu/category_form/' . (int)$id);
			}
			$this->menu_model->save_category(array(
				'name'       => $name,
				'sort_order' => (int)$this->input->post('sort_order'),
			), $id ? (int)$id : NULL);
			$this->flash('Category saved.', 'ok');
			redirect('admin/menu');
		}

		$this->render('admin/menu_category_form', $data);
	}

	public function delete_category($id)
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$this->menu_model->delete_category($id);
			$this->flash('Category deleted (its items are now uncategorized).', 'ok');
		}
		redirect('admin/menu');
	}
}
