<?php
  // Revenue of the orders currently on screen.
  $shown_total = 0;
  foreach ($orders as $o) { $shown_total += (float)$o['total']; }
  $pill_icons = array('placed' => 'bi-hourglass-split', 'confirmed' => 'bi-check2-circle', 'preparing' => 'bi-fire', 'ready' => 'bi-bag-check', 'delivered' => 'bi-check2-all', 'cancelled' => 'bi-x-circle');
?>
<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Orders</h1>
    <div class="page-sub">Everything your customers ordered through WhatsApp — update statuses right from here.</div>
  </div>
</div>

<!-- Summary strip -->
<div class="row g-2 mb-4">
  <div class="col-6 col-md-3" data-reveal>
    <div class="summary-chip">
      <div class="d-flex align-items-center gap-2"><i class="bi bi-receipt text-warning"></i><span class="lbl">Orders shown</span></div>
      <div class="num"><?= count($orders) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3" data-reveal>
    <div class="summary-chip">
      <div class="d-flex align-items-center gap-2"><i class="bi bi-currency-dollar text-success"></i><span class="lbl">Order value</span></div>
      <div class="num"><?= html_escape($cur) ?><?= money_fmt($shown_total) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3" data-reveal>
    <div class="summary-chip">
      <div class="d-flex align-items-center gap-2"><i class="bi bi-hourglass-split text-primary"></i><span class="lbl">Awaiting</span></div>
      <div class="num"><?= (int)($counts['placed'] ?? 0) + (int)($counts['confirmed'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3" data-reveal>
    <div class="summary-chip">
      <div class="d-flex align-items-center gap-2"><i class="bi bi-check2-all text-danger"></i><span class="lbl">Delivered</span></div>
      <div class="num"><?= (int)($counts['delivered'] ?? 0) ?></div>
    </div>
  </div>
</div>

<ul class="nav nav-pills gap-1 mb-4">
  <li class="nav-item">
    <a class="nav-link <?= $status === '' ? 'active' : '' ?>" href="<?= site_url('admin/orders') ?>">All (<?= array_sum($counts) ?>)</a>
  </li>
  <?php foreach ($counts as $key => $count): ?>
    <li class="nav-item">
      <a class="nav-link <?= $status === $key ? 'active' : '' ?>" href="<?= site_url('admin/orders/index/' . $key) ?>">
        <?= ucfirst($key) ?> (<?= $count ?>)
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<!-- Bulk action bar (appears when orders are selected) -->
<div class="bulk-bar card mb-3 d-none" id="bulkBar">
  <div class="card-body py-2 d-flex align-items-center gap-2 flex-wrap">
    <?= form_open('admin/orders/bulk_status', array('id' => 'bulkForm', 'class' => 'd-flex align-items-center gap-2 flex-wrap mb-0')) ?>
      <span class="small text-secondary" id="bulkCount">0 selected</span>
      <?= form_dropdown('status', array('placed' => 'Mark as placed', 'confirmed' => 'Mark as confirmed', 'preparing' => 'Mark as preparing', 'ready' => 'Mark as ready', 'delivered' => 'Mark as delivered', 'cancelled' => 'Mark as cancelled'), '', 'class="form-select form-select-sm" style="width:auto;" id="bulkStatus"') ?>
      <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat me-1"></i> Apply to selected</button>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkClear">Clear</button>
      <div class="form-text small mb-0">Auto-notify sends a message to each customer if enabled in Settings.</div>
    <?= form_close() ?>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:36px;"><input type="checkbox" class="form-check-input" id="selectAll" title="Select all on this page"></th>
          <th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Placed</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if ( ! $orders): ?>
          <tr><td colspan="8">
            <div class="empty-state">
              <div class="empty-ico"><i class="bi bi-receipt"></i></div>
              <h4>No orders here yet</h4>
              <p>Orders placed by customers on WhatsApp will show up here as soon as they happen — with quick status updates and one-click customer messages.</p>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $order): ?>
          <?php $items = json_decode($order['items_json'], TRUE) ?: array(); ?>
          <tr>
            <td><input type="checkbox" class="form-check-input order-check" value="<?= (int)$order['id'] ?>"></td>
            <td><a class="fw-semibold" href="<?= site_url('admin/orders/view/' . $order['id']) ?>">#<?= (int)$order['id'] ?></a></td>
            <td><?= html_escape($order['customer_name'] ?: $order['wa_id']) ?></td>
            <td>
              <?php foreach ($items as $i): ?>
                <div class="small"><?= (int)$i['quantity'] ?>x <?= html_escape($i['name']) ?></div>
              <?php endforeach; ?>
            </td>
            <td class="fw-semibold"><?= html_escape($cur) ?><?= money_fmt($order['total']) ?></td>
            <td>
              <span class="status-pill <?= html_escape($order['status']) ?>">
                <i class="bi <?= isset($pill_icons[$order['status']]) ? $pill_icons[$order['status']] : 'bi-tag' ?>"></i>
                <?= html_escape($order['status']) ?>
              </span>
            </td>
            <td class="small"><?= html_escape(date('M j, H:i', strtotime($order['created_at']))) ?></td>
            <td class="text-end">
              <div class="quick-status" data-order-id="<?= (int)$order['id'] ?>" data-status="<?= html_escape($order['status']) ?>">
                <?= form_open('admin/orders/quick_status/' . (int)$order['id'], array('class' => 'd-inline-flex align-items-center gap-1')) ?>
                  <?= form_dropdown('status', array('placed' => 'Placed', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'ready' => 'Ready', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'), $order['status'], 'class="form-select form-select-sm"') ?>
                  <button type="submit" class="btn btn-sm btn-outline-secondary" title="Update status"><i class="bi bi-arrow-repeat"></i></button>
                <?= form_close() ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
/* ---------- Bulk selection ---------- */
(function () {
  var bar = document.getElementById('bulkBar');
  var form = document.getElementById('bulkForm');
  var countEl = document.getElementById('bulkCount');
  var selectAll = document.getElementById('selectAll');
  var clearBtn = document.getElementById('bulkClear');
  if (!bar || !form || !selectAll) return;

  function selected() {
    return Array.prototype.slice.call(document.querySelectorAll('.order-check:checked')).map(function (cb) { return cb.value; });
  }
  function refresh() {
    var ids = selected();
    countEl.textContent = ids.length + ' selected';
    bar.classList.toggle('d-none', ids.length === 0);
  }
  document.addEventListener('change', function (e) {
    if (e.target.classList && e.target.classList.contains('order-check')) refresh();
  });
  selectAll.addEventListener('change', function () {
    document.querySelectorAll('.order-check').forEach(function (cb) { cb.checked = selectAll.checked; });
    refresh();
  });
  clearBtn.addEventListener('click', function () {
    document.querySelectorAll('.order-check').forEach(function (cb) { cb.checked = false; });
    selectAll.checked = false;
    refresh();
  });
  form.addEventListener('submit', function (e) {
    var ids = selected();
    if (!ids.length || !document.getElementById('bulkStatus').value) { e.preventDefault(); return; }
    ids.forEach(function (id) {
      var input = document.createElement('input');
      input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
      form.appendChild(input);
    });
  });
})();

// Colour each row's quick-status dropdown to match its current status.
(function () {
  var map = {
    placed:     { sp: '#b45309', soft: '#fef3c7' },
    confirmed:  { sp: '#0369a1', soft: '#e0f2fe' },
    preparing:  { sp: '#6d28d9', soft: '#ede9fe' },
    ready:      { sp: '#15803d', soft: '#dcfce7' },
    delivered:  { sp: '#16a34a', soft: '#dcfce7' },
    cancelled:  { sp: '#b91c1c', soft: '#fee2e2' }
  };
  document.querySelectorAll('.quick-status').forEach(function (row) {
    var c = map[row.dataset.status];
    if (c) { row.style.setProperty('--sp', c.sp); row.style.setProperty('--sp-soft', c.soft); }
  });
})();
</script>
