<?php $item = $item ?: array(); ?>
<div class="page-head mb-4">
  <div>
    <h1 class="page-title"><?= $item ? 'Edit item' : 'Add item' ?></h1>
    <div class="page-sub"><?= $item ? 'Update the details below — changes are live for customers immediately.' : 'New items appear in the WhatsApp catalog as soon as you save.' ?></div>
  </div>
  <a href="<?= site_url('admin/menu') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Menu</a>
</div>

<div class="card p-4" style="max-width: 640px;">
  <?= form_open($item ? 'admin/menu/item_form/' . $item['id'] : 'admin/menu/item_form') ?>
    <div class="mb-3">
      <label class="form-label">Name *</label>
      <input type="text" name="name" class="form-control" required
             value="<?= html_escape(isset($item['name']) ? $item['name'] : '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Category</label>
      <?php
        $options = array('' => '— No category —');
        foreach ($categories as $c) { $options[$c['id']] = $c['name']; }
        echo form_dropdown('category_id', $options, isset($item['category_id']) ? $item['category_id'] : '', 'class="form-select"');
      ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Price * <span class="text-secondary fw-normal">(<?= html_escape($cur) ?>)</span></label>
      <input type="number" step="0.01" min="0" name="price" class="form-control" required
             value="<?= isset($item['price']) ? number_format((float)$item['price'], 2, '.', '') : '' ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" rows="3" class="form-control"><?= html_escape(isset($item['description']) ? $item['description'] : '') ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Sort order</label>
      <input type="number" name="sort_order" class="form-control" value="<?= isset($item['sort_order']) ? (int)$item['sort_order'] : 0 ?>">
    </div>
    <div class="form-check mb-4">
      <input type="checkbox" name="available" value="1" class="form-check-input" id="available"
             <?= ( ! isset($item['available']) || $item['available']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="available">Available to customers</label>
    </div>
    <button type="submit" class="btn btn-primary">Save item</button>
  <?= form_close() ?>
</div>
