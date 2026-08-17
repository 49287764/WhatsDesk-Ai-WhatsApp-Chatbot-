<div class="page-head mb-4">
  <div>
    <h1 class="page-title"><?= html_escape($greeting) ?>, <?= html_escape($admin_name) ?> 👋</h1>
    <div class="page-sub">Here's how your WhatsApp assistant is doing today.</div>
  </div>
  <div>
    <a href="<?= site_url('admin/dashboard?check=1') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i> Check connection now</a>
  </div>
</div>

<?php if ( ! $setup_complete): ?>
  <!-- First-run onboarding modal (dismissed permanently via localStorage) -->
  <div class="modal fade" id="welcomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:18px;overflow:hidden;">
        <div style="background:linear-gradient(120deg,#16130e,#2a2419);color:#fff;padding:1.6rem 1.6rem 1.3rem;">
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="brand-mark" style="width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,#f59e0b,#f97316);display:grid;place-items:center;font-size:1.1rem;"><i class="bi bi-stars"></i></div>
            <div>
              <div class="fw-bold" style="font-family:var(--font-display);font-size:1.2rem;">Welcome to WhatsDesk 👋</div>
              <div style="font-size:.8rem;color:#a8a29e;">Let's get your assistant live — it takes about 30 minutes.</div>
            </div>
          </div>
        </div>
        <div class="modal-body p-4">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="position-relative" style="width:44px;height:44px;">
              <svg viewBox="0 0 44 44" width="44" height="44">
                <circle cx="22" cy="22" r="19" fill="none" stroke="#efece5" stroke-width="4"/>
                <circle cx="22" cy="22" r="19" fill="none" stroke="#f59e0b" stroke-width="4" stroke-linecap="round"
                        stroke-dasharray="<?= (int)round((M_PI * 2 * 19) * ($launch_done / max($launch_total, 1))) ?> <?= (int)round(M_PI * 2 * 19) ?>"
                        transform="rotate(-90 22 22)"/>
              </svg>
              <span class="position-absolute top-50 start-50 translate-middle fw-bold" style="font-size:.72rem;"><?= $launch_done ?>/<?= $launch_total ?></span>
            </div>
            <div class="small text-secondary">
              <strong class="text-body"><?= $launch_done ?> of <?= $launch_total ?> steps done.</strong><br>
              Follow the checklist — each step opens the exact page with instructions.
            </div>
          </div>
          <div class="list-group list-group-flush mb-3" style="border:1px solid var(--line);border-radius:12px;overflow:hidden;">
            <?php foreach ($launch as $item): ?>
              <div class="list-group-item d-flex align-items-center gap-2 py-2">
                <i class="bi <?= $item['done'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' ?>"></i>
                <span class="small <?= $item['done'] ? 'text-secondary text-decoration-line-through' : '' ?>"><?= html_escape($item['label']) ?></span>
                <?php if ( ! $item['done']): ?><a href="<?= site_url($item['url']) ?>" class="ms-auto small fw-semibold">Start</a><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= site_url('admin/setup') ?>" class="btn btn-primary flex-fill"><i class="bi bi-rocket-takeoff me-1"></i> Open setup guide</a>
            <button type="button" class="btn btn-outline-secondary" id="welcomeClose">Later</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ( ! $setup_complete): ?>
  <div class="card mb-4" style="border-color:#fde68a; background:linear-gradient(120deg,#fff, #fffbeb);">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <div class="fw-bold mb-1" style="font-size:1.05rem;"><i class="bi bi-rocket-takeoff me-2 text-warning"></i>Your bot is <?= $launch_done ?>/<?= $launch_total ?> steps from live</div>
          <div class="text-secondary small">Follow the short setup guide — each step has the exact instructions you need.</div>
        </div>
        <a href="<?= site_url('admin/setup') ?>" class="btn btn-primary"><i class="bi bi-rocket-takeoff me-1"></i> Continue setup</a>
      </div>
      <div class="progress mt-3" style="height:8px;">
        <div class="progress-bar" style="width:<?= (int)round(($launch_done / max($launch_total, 1)) * 100) ?>%; background:linear-gradient(90deg,#f59e0b,#f97316);"></div>
      </div>
    </div>
  </div>
<?php else: ?>
  <?php if ( ! $wa_valid): ?>
    <div class="alert alert-danger d-flex flex-wrap align-items-center gap-2">
      <i class="bi bi-x-circle-fill fs-5"></i>
      <span class="flex-fill"><strong>WhatsApp access token is missing or expired.</strong> Meta is rejecting the token, so customers can't get replies. <?php if ($wa_check_msg !== ''): ?><span class="small text-danger">(<?= html_escape($wa_check_msg) ?>)</span><?php endif; ?></span>
      <a href="<?= site_url('admin/settings#sec-whatsapp') ?>" class="btn btn-sm btn-danger"><i class="bi bi-key me-1"></i> Fix token in Settings</a>
    </div>
  <?php elseif ( ! $worker_ok): ?>
    <div class="alert alert-info d-flex flex-wrap align-items-center gap-2">
      <i class="bi bi-info-circle-fill fs-5"></i>
      <span class="flex-fill"><strong>Everything is connected — instant replies are working.</strong> The cron worker is optional: it's just a safety net that catches a reply if the webhook ever misses one. Your bot works fine without it.</span>
      <a href="<?= site_url('admin/setup') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-book me-1"></i> See how to enable it</a>
    </div>
  <?php else: ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <span><strong>Your bot is live!</strong> WhatsApp connected and the cron worker is running — replies are flowing.</span>
    </div>
  <?php endif; ?>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat" style="--sc:#b45309; --sc-soft:#fef3c7; animation-delay:.05s;">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="lbl">New orders</span>
        <span class="ico"><i class="bi bi-bag-plus"></i></span>
      </div>
      <div class="num"><?= (int)$order_counts['placed'] ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat" style="--sc:#ea580c; --sc-soft:#ffedd5; animation-delay:.1s;">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="lbl">Preparing</span>
        <span class="ico"><i class="bi bi-fire"></i></span>
      </div>
      <div class="num"><?= (int)$order_counts['preparing'] ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat" style="--sc:#15803d; --sc-soft:#dcfce7; animation-delay:.15s;">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="lbl">Revenue today</span>
        <span class="ico"><i class="bi bi-currency-dollar"></i></span>
      </div>
      <div class="num"><?= html_escape($cur) ?><?= money_fmt($revenue_today) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat" style="--sc:#0369a1; --sc-soft:#e0f2fe; animation-delay:.2s;">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="lbl">Unread chats</span>
        <span class="ico"><i class="bi bi-chat-dots"></i></span>
      </div>
      <div class="num"><?= (int)$unread_chats ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat" style="--sc:#6d28d9; --sc-soft:#ede9fe; animation-delay:.25s;">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="lbl">Customers</span>
        <span class="ico"><i class="bi bi-people"></i></span>
      </div>
      <div class="num"><?= (int)$customers_total ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat" style="--sc:#0f766e; --sc-soft:#ccfbf1; animation-delay:.3s;">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="lbl">Delivered</span>
        <span class="ico"><i class="bi bi-check2-circle"></i></span>
      </div>
      <div class="num"><?= (int)$order_counts['delivered'] ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7" data-reveal>
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Orders — last 7 days</span>
        <a href="<?= site_url('admin/orders') ?>" class="btn btn-sm btn-outline-secondary">View all</a>
      </div>
      <div class="card-body">
        <div style="position:relative;height:260px;">
          <canvas id="ordersChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5" data-reveal>
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Launch checklist</span>
        <a href="<?= site_url('admin/setup') ?>" class="small">Full guide</a>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($launch as $item): ?>
          <li class="list-group-item d-flex align-items-center gap-2">
            <i class="bi <?= $item['done'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' ?>"></i>
            <?= html_escape($item['label']) ?>
            <?php if ( ! $item['done']): ?><a href="<?= site_url($item['url']) ?>" class="ms-auto small">Do it</a><?php endif; ?>
          </li>
        <?php endforeach; ?>
        <li class="list-group-item d-flex align-items-center gap-2">
          <i class="bi bi-globe2 text-secondary"></i>
          Webhook: <span class="mono text-secondary small"><?= html_escape(site_url('whatsapp/webhook')) ?></span>
        </li>
      </ul>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7" data-reveal>
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Revenue — last 7 days</span>
        <a href="<?= site_url('admin/orders') ?>" class="btn btn-sm btn-outline-secondary">View orders</a>
      </div>
      <div class="card-body">
        <div style="position:relative;height:240px;">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5" data-reveal>
    <div class="card h-100">
      <div class="card-header">
        <span>Orders by status</span>
      </div>
      <div class="card-body">
        <div style="position:relative;height:240px;">
          <canvas id="statusChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card" data-reveal>
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Recent orders</span>
    <a href="<?= site_url('admin/orders') ?>" class="btn btn-sm btn-outline-secondary">All orders</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Placed</th></tr>
      </thead>
      <tbody>
        <?php if ( ! $recent_orders): ?>
          <tr><td colspan="6">
            <div class="empty-state">
              <div class="empty-ico"><i class="bi bi-inbox"></i></div>
              <h4>No orders yet</h4>
              <p>Once a customer places an order on WhatsApp, it will appear here instantly.</p>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($recent_orders as $order): ?>
          <?php $item_count = count(json_decode($order['items_json'], TRUE) ?: array()); ?>
          <tr>
            <td><a href="<?= site_url('admin/orders/view/' . $order['id']) ?>" class="mono fw-semibold">#<?= (int)$order['id'] ?></a></td>
            <td><?= html_escape($order['customer_name'] ?: $order['wa_id']) ?></td>
            <td><?= $item_count ?> item(s)</td>
            <td class="fw-semibold"><?= html_escape($cur) ?><?= money_fmt($order['total']) ?></td>
            <td>
              <?php
                $badge = array(
                  'placed' => 'text-bg-warning', 'confirmed' => 'text-bg-info',
                  'preparing' => 'text-bg-primary', 'ready' => 'text-bg-success',
                  'delivered' => 'text-bg-success', 'cancelled' => 'text-bg-danger',
                );
                $cls = isset($badge[$order['status']]) ? $badge[$order['status']] : 'text-bg-secondary';
              ?>
              <span class="badge <?= $cls ?>"><?= html_escape(ucfirst($order['status'])) ?></span>
            </td>
            <td class="text-secondary small"><?= html_escape(date('M j, H:i', strtotime($order['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function () {
  // Show the welcome modal once per browser (dismissed stays dismissed)
  var modal = document.getElementById('welcomeModal');
  if (!modal || typeof bootstrap === 'undefined') return;
  var KEY = 'bizbot-welcome-dismissed';
  var dismissed = null;
  try { dismissed = localStorage.getItem(KEY); } catch (e) {}
  if (dismissed === '1') return;
  var inst = new bootstrap.Modal(modal, { backdrop: 'static' });
  inst.show();
  var close = document.getElementById('welcomeClose');
  if (close) close.addEventListener('click', function () {
    try { localStorage.setItem(KEY, '1'); } catch (e) {}
    inst.hide();
  });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var ctx = document.getElementById('ordersChart');
  if (!ctx || typeof Chart === 'undefined') return;
  var grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 260);
  grad.addColorStop(0, 'rgba(245,158,11,.28)');
  grad.addColorStop(1, 'rgba(245,158,11,.02)');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($chart['labels']) ?>,
      datasets: [{
        label: 'Orders',
        data: <?= json_encode($chart['values']) ?>,
        borderColor: '#d97706',
        backgroundColor: grad,
        fill: true,
        tension: .4,
        pointBackgroundColor: '#f59e0b',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2.5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 900, easing: 'easeOutQuart' },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#16130e',
          titleColor: '#fff',
          bodyColor: '#d6d3d1',
          padding: 10,
          cornerRadius: 10,
          displayColors: false
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0, color: '#78716c' }, grid: { color: '#f0ede6' } },
        x: { ticks: { color: '#78716c' }, grid: { display: false } }
      }
    }
  });

  // Revenue chart
  var revCtx = document.getElementById('revenueChart');
  if (revCtx && typeof Chart !== 'undefined') {
    var revGrad = revCtx.getContext('2d').createLinearGradient(0, 0, 0, 240);
    revGrad.addColorStop(0, 'rgba(22,163,74,.22)');
    revGrad.addColorStop(1, 'rgba(22,163,74,.02)');
    new Chart(revCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($revenue_chart['labels']) ?>,
        datasets: [{
          label: 'Revenue',
          data: <?= json_encode($revenue_chart['values']) ?>,
          borderColor: '#16a34a',
          backgroundColor: revGrad,
          fill: true,
          tension: .4,
          pointBackgroundColor: '#16a34a',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 3,
          borderWidth: 2.5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 900, easing: 'easeOutQuart' },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#16130e',
            padding: 10,
            cornerRadius: 10,
            displayColors: false,
            callbacks: { label: function (c) { return <?= json_encode($cur) ?> + Number(c.parsed.y).toFixed(2); } }
          }
        },
        scales: {
          y: { beginAtZero: true, ticks: { color: '#78716c', callback: function (v) { return <?= json_encode($cur) ?> + v; } }, grid: { color: '#f0ede6' } },
          x: { ticks: { color: '#78716c' }, grid: { display: false } }
        }
      }
    });
  }

  // Status donut
  var stCtx = document.getElementById('statusChart');
  if (stCtx && typeof Chart !== 'undefined') {
    new Chart(stCtx, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($status_labels) ?>,
        datasets: [{
          data: <?= json_encode($status_values) ?>,
          backgroundColor: ['#f59e0b', '#0284c7', '#8b5cf6', '#22c55e', '#16a34a', '#dc2626'],
          borderColor: '#ffffff',
          borderWidth: 2,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        animation: { animateRotate: true, duration: 900 },
        plugins: {
          legend: {
            position: 'right',
            labels: { boxWidth: 10, boxHeight: 10, padding: 12, color: '#78716c', font: { size: 11 } }
          },
          tooltip: { backgroundColor: '#16130e', padding: 10, cornerRadius: 10 }
        }
      }
    });
  }
});
</script>
