<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Products &amp; services</h1>
    <div class="page-sub">What customers can see and order on WhatsApp. Prices come from here and are never invented by the bot.</div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= site_url('admin/menu/category_form') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-folder-plus me-1"></i> Category</a>
    <a href="<?= site_url('admin/menu/item_form') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Item</a>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr><th>Item</th><th>Category</th><th>Price</th><th>Available</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php if ( ! $items): ?>
          <tr><td colspan="5">
            <div class="empty-state">
              <div class="empty-ico"><i class="bi bi-bag"></i></div>
              <h4>No products or services yet</h4>
              <p>Add your first product or service — customers order these on WhatsApp. Prices here are never invented by the bot.</p>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <strong><?= html_escape($item['name']) ?></strong>
              <?php if ($item['description']): ?>
                <div class="text-secondary small"><?= html_escape(mb_strimwidth($item['description'], 0, 80, '…')) ?></div>
              <?php endif; ?>
            </td>
            <td><?= html_escape($item['category_name'] ?: '—') ?></td>
            <td><?= html_escape($cur) ?><?= money_fmt($item['price']) ?></td>
            <td>
              <?= form_open('admin/menu/toggle_item/' . $item['id'], array('class' => 'd-inline')) ?>
                <button type="submit" class="badge border-0 text-bg-<?= $item['available'] ? 'success' : 'secondary' ?>"
                        title="Click to toggle"><?= $item['available'] ? 'Available' : 'Hidden' ?></button>
              <?= form_close() ?>
            </td>
            <td class="text-end">
              <a href="<?= site_url('admin/menu/item_form/' . $item['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              <?= form_open('admin/menu/delete_item/' . $item['id'], array('class' => 'd-inline', 'onsubmit' => 'return confirm("Delete this item?");')) ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
              <?= form_close() ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<h2 class="h5 mt-4 mb-3">Categories</h2>
<div class="card">
  <table class="table mb-0">
    <thead class="table-light">
      <tr><th>Name</th><th>Sort order</th><th class="text-end">Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $category): ?>
        <tr>
          <td><?= html_escape($category['name']) ?></td>
          <td><?= (int)$category['sort_order'] ?></td>
          <td class="text-end">
            <a href="<?= site_url('admin/menu/category_form/' . $category['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <?= form_open('admin/menu/delete_category/' . $category['id'], array('class' => 'd-inline', 'onsubmit' => 'return confirm("Delete this category?");')) ?>
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            <?= form_close() ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
