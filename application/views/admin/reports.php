<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Sales report</h1>
    <div class="page-sub">Orders, revenue and best sellers for any date range — print it or export it.</div>
  </div>
  <div class="d-flex gap-2 flex-wrap no-print">
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
    <a href="<?= site_url('admin/reports/export_csv?from=' . html_escape($from) . '&to=' . html_escape($to)) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-filetype-csv me-1"></i> Export CSV</a>
  </div>
</div>

<!-- Date range -->
<div class="card mb-4 no-print">
  <div class="card-body">
    <?= form_open('admin/reports', array('method' => 'get', 'class' => 'row g-2 align-items-end')) ?>
      <div class="col-auto">
        <label class="form-label mb-1">From</label>
        <input type="date" name="from" class="form-control" value="<?= html_escape($from) ?>">
      </div>
      <div class="col-auto">
        <label class="form-label mb-1">To</label>
        <input type="date" name="to" class="form-control" value="<?= html_escape($to) ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Show</button>
      </div>
      <div class="col-auto">
        <a href="<?= site_url('admin/reports') ?>" class="btn btn-outline-secondary">Last 7 days</a>
        <a href="<?= site_url('admin/reports?from=' . date('Y-m-01') . '&to=' . date('Y-m-d')) ?>" class="btn btn-outline-secondary">This month</a>
        <a href="<?= site_url('admin/reports?from=' . date('Y-01-01') . '&to=' . date('Y-m-d')) ?>" class="btn btn-outline-secondary">This year</a>
      </div>
    <?= form_close() ?>
  </div>
</div>

<!-- Summary -->
<div class="row g-2 mb-4">
  <div class="col-md-4" data-reveal>
    <div class="summary-chip">
      <div class="d-flex align-items-center gap-2"><i class="bi bi-receipt text-warning"></i><span class="lbl">Orders placed</span></div>
      <div class="num"><?= (int)$summary['orders'] ?></div>
    </div>
  </div>
  <div class="col-md-4" data-reveal>
    <div class="summary-chip">
      <div class="d-flex align-items-center gap-2"><i class="bi bi-currency-dollar text-success"></i><span class="lbl">Revenue (excl. cancelled)</span></div>
      <div class="num"><?= html_escape($cur) ?><?= money_fmt($summary['revenue']) ?></div>
    </div>
  </div>
  <div class="col-md-4" data-reveal>
    <div class="summary-chip">
      <div class="d-flex align-items-center gap-2"><i class="bi bi-cart3 text-primary"></i><span class="lbl">Avg order value</span></div>
      <div class="num"><?= html_escape($cur) ?><?= money_fmt($summary['avg_order']) ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7" data-reveal>
    <div class="card h-100">
      <div class="card-header bg-white"><strong>Revenue per day</strong></div>
      <div class="card-body">
        <canvas id="reportChart" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-5" data-reveal>
    <div class="card h-100">
      <div class="card-header bg-white"><strong>Top items</strong></div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Revenue</th></tr></thead>
          <tbody>
            <?php if ( ! $top): ?>
              <tr><td colspan="3"><div class="text-secondary small py-3 text-center">No sales in this range.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($top as $name => $t): ?>
              <tr>
                <td><?= html_escape($name) ?></td>
                <td class="text-end fw-semibold"><?= (int)$t['qty'] ?></td>
                <td class="text-end"><?= html_escape($cur) ?><?= money_fmt($t['revenue']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Orders in range -->
<div class="card">
  <div class="card-header bg-white"><strong>Orders in range (<?= count($orders) ?>)</strong></div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr><th>#</th><th>Placed</th><th>Customer</th><th>Items</th><th class="text-end">Total</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if ( ! $orders): ?>
          <tr><td colspan="6">
            <div class="empty-state">
              <div class="empty-ico"><i class="bi bi-graph-up"></i></div>
              <h4>No orders in this range</h4>
              <p>Pick a different date range, or wait for customers to order on WhatsApp.</p>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $order): ?>
          <?php $items = json_decode($order['items_json'], TRUE) ?: array(); ?>
          <tr>
            <td><a class="fw-semibold" href="<?= site_url('admin/orders/view/' . $order['id']) ?>">#<?= (int)$order['id'] ?></a></td>
            <td class="small"><?= html_escape(date('M j, Y H:i', strtotime($order['created_at']))) ?></td>
            <td><?= html_escape($order['customer_name'] ?: $order['wa_id']) ?></td>
            <td>
              <?php foreach ($items as $i): ?>
                <div class="small"><?= (int)$i['quantity'] ?>x <?= html_escape($i['name']) ?></div>
              <?php endforeach; ?>
            </td>
            <td class="text-end fw-semibold"><?= html_escape($cur) ?><?= money_fmt($order['total']) ?></td>
            <td><span class="status-pill <?= html_escape($order['status']) ?>"><?= html_escape($order['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function () {
  var el = document.getElementById('reportChart');
  if (!el || typeof Chart === 'undefined') return;
  var labels = <?= json_encode($chart['labels']) ?>;
  var revenue = <?= json_encode($chart['revenue']) ?>;
  var orders = <?= json_encode($chart['orders']) ?>;
  var grid = 'rgba(22,19,14,.08)';
  var tick = '#78716c';
  new Chart(el, {
    data: {
      labels: labels,
      datasets: [
        {
          type: 'bar',
          label: 'Orders',
          data: orders,
          backgroundColor: 'rgba(245,158,11,.45)',
          borderColor: '#f59e0b',
          borderWidth: 1,
          borderRadius: 6,
          order: 2
        },
        {
          type: 'line',
          label: 'Revenue (<?= html_escape($cur) ?>)',
          data: revenue,
          borderColor: '#16a34a',
          backgroundColor: 'rgba(22,163,74,.12)',
          fill: true,
          tension: .4,
          pointRadius: 3,
          pointBackgroundColor: '#16a34a',
          order: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { labels: { color: tick, boxWidth: 10 } },
        tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + (c.dataset.type === 'line' ? <?= json_encode($cur) ?> : '') + c.parsed.y; } } }
      },
      scales: {
        x: { grid: { color: grid }, ticks: { color: tick, maxTicksLimit: 12 } },
        y: { grid: { color: grid }, ticks: { color: tick } }
      }
    }
  });
})();
</script>

<style>
@media print {
  body * { visibility: hidden; }
  .content, .content * { visibility: visible; }
  .content { position: absolute; inset: 0; max-width: none; padding: 0; }
  .no-print { display: none !important; }
}
</style>
