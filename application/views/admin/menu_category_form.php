<?php $category = $category ?: array(); ?>
<div class="page-head mb-4">
  <div>
    <h1 class="page-title"><?= $category ? 'Edit category' : 'Add category' ?></h1>
    <div class="page-sub">Groups like Products, Services — or whatever sections make sense for you.</div>
  </div>
  <a href="<?= site_url('admin/menu') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Menu</a>
</div>

<div class="card p-4" style="max-width: 480px;">
  <?= form_open($category ? 'admin/menu/category_form/' . $category['id'] : 'admin/menu/category_form') ?>
    <div class="mb-3">
      <label class="form-label">Name *</label>
      <input type="text" name="name" class="form-control" required
             value="<?= html_escape(isset($category['name']) ? $category['name'] : '') ?>">
    </div>
    <div class="mb-4">
      <label class="form-label">Sort order</label>
      <input type="number" name="sort_order" class="form-control" value="<?= isset($category['sort_order']) ? (int)$category['sort_order'] : 0 ?>">
    </div>
    <button type="submit" class="btn btn-primary">Save category</button>
  <?= form_close() ?>
</div>
