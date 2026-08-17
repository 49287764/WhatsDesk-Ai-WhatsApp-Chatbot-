<?php
  $items = $items ?: array();
  $status_options = array('placed', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled');
?>
<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Order <span class="mono">#<?= (int)$order['id'] ?></span></h1>
    <div class="page-sub">Placed <?= html_escape(date('M j, Y \a\t H:i', strtotime($order['created_at']))) ?></div>
  </div>
  <a href="<?= site_url('admin/orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> All orders</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header bg-white"><strong>Items</strong></div>
      <table class="table mb-0">
        <tbody>
          <?php foreach ($items as $i): ?>
            <tr>
              <td><?= html_escape($i['name']) ?></td>
              <td class="text-end"><?= (int)$i['quantity'] ?>x</td>
              <td class="text-end"><?= html_escape($cur) ?><?= money_fmt($i['price']) ?></td>
              <td class="text-end"><?= html_escape($cur) ?><?= money_fmt($i['price'] * (int)$i['quantity']) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="table-light fw-bold">
            <td colspan="3">Total</td>
            <td class="text-end"><?= html_escape($cur) ?><?= money_fmt($order['total']) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card mb-3">
      <div class="card-header bg-white"><strong>Customer</strong></div>
      <div class="card-body">
        <div><strong>Name:</strong> <?= html_escape($order['customer_name'] ?: '—') ?></div>
        <div><strong>WhatsApp:</strong> <?= html_escape($order['wa_id']) ?></div>
        <div><strong>Address:</strong> <?= html_escape($order['customer_address'] ?: '—') ?></div>
        <div><strong>Placed:</strong> <?= html_escape($order['created_at']) ?></div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header bg-white"><strong>Status</strong></div>
      <div class="card-body">
        <?php
          $badge = array('placed' => 'text-bg-warning', 'confirmed' => 'text-bg-info', 'preparing' => 'text-bg-primary', 'ready' => 'text-bg-success', 'delivered' => 'text-bg-success', 'cancelled' => 'text-bg-danger');
          $bcls = isset($badge[$order['status']]) ? $badge[$order['status']] : 'text-bg-secondary';
          $pill_icons = array('placed' => 'bi-hourglass-split', 'confirmed' => 'bi-check2-circle', 'preparing' => 'bi-fire', 'ready' => 'bi-bag-check', 'delivered' => 'bi-check2-all', 'cancelled' => 'bi-x-circle');
        ?>

        <?php if ($order['status'] === 'cancelled'): ?>
          <div class="alert alert-danger small mb-3">
            <i class="bi bi-x-circle-fill me-1"></i> This order was <strong>cancelled</strong> and is no longer active.
          </div>
        <?php else: ?>
          <?php
            $pipeline = array('placed', 'confirmed', 'preparing', 'ready', 'delivered');
            $cur = array_search($order['status'], $pipeline, TRUE);
            if ($cur === FALSE) { $cur = -1; }
            $pct = $cur >= 0 ? (int)round((($cur + 1) / count($pipeline)) * 100) : 0;
          ?>
          <div class="step-track mb-3" data-step-pct="<?= $pct ?>">
            <div class="step-line" style="width:0%;"></div>
            <?php foreach ($pipeline as $i => $st): ?>
              <div class="step-node <?= $i < $cur ? 'done' : ($i === $cur ? 'current' : '') ?>">
                <div class="step-bubble">
                  <?php if ($i < $cur): ?><i class="bi bi-check-lg"></i><?php else: ?><i class="bi <?= $pill_icons[$st] ?>"></i><?php endif; ?>
                </div>
                <div class="step-name"><?= html_escape($st) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="mb-3">
          Current: <span class="badge <?= $bcls ?>"><?= html_escape($order['status']) ?></span>
        </div>
        <?= form_open('admin/orders/update_status/' . $order['id']) ?>
          <?= form_dropdown('status', array_combine($status_options, array_map('ucfirst', $status_options)), $order['status'], 'class="form-select mb-2"') ?>
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i> Update status</button>
        <?= form_close() ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-white"><strong>Notify customer</strong></div>
      <div class="card-body">
        <div class="text-secondary small mb-2">
          Free-form message — only works inside the 24h window (customer messaged recently).
        </div>
        <?= form_open('admin/orders/notify/' . $order['id']) ?>
          <textarea name="message" rows="3" class="form-control mb-2" placeholder="e.g. Your order is on its way! 🛵">Your order is <?= html_escape($order['status']) ?>. Thank you for ordering with us! 🍽️</textarea>
          <button type="submit" class="btn btn-outline-primary w-100">Send free-form message</button>
        <?= form_close() ?>

        <?php if ($order_template): ?>
          <hr>
          <div class="text-secondary small mb-2">
            Approved template <code class="mono"><?= html_escape($order_template) ?></code> — works <strong>outside</strong> the 24h window.
            Template body must have two parameters: order number and status.
          </div>
          <?= form_open('admin/orders/send_template/' . $order['id']) ?>
            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-send me-1"></i> Send template message</button>
          <?= form_close() ?>
        <?php else: ?>
          <div class="text-secondary small mt-2">
            Tip: set an approved template name in <a href="<?= site_url('admin/settings') ?>">Settings</a> to reach customers outside the 24h window.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
// Animate the status progress line + celebrate delivery with confetti.
(function () {
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var track = document.querySelector('.step-track');
  if (track && !reduce) {
    var pct = parseInt(track.dataset.stepPct || '0', 10);
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        track.querySelector('.step-line').style.width = pct + '%';
      });
    });
  }

  // Confetti when the owner just marked this order delivered.
  var flash = document.body && document.body.dataset.flash;
  if (flash && flash.toLowerCase().indexOf('delivered') !== -1 && !reduce) {
    var colors = ['#f59e0b', '#f97316', '#16a34a', '#7c3aed', '#0d9488', '#dc2626'];
    for (var i = 0; i < 42; i++) {
      var p = document.createElement('span');
      p.className = 'confetti-piece';
      p.style.left = (Math.random() * 100) + 'vw';
      p.style.top = '-20px';
      p.style.background = colors[i % colors.length];
      p.style.setProperty('--cf-x', (Math.random() * 180 - 90) + 'px');
      p.style.setProperty('--cf-y', (Math.random() * 70 + 60) + 'vh');
      p.style.setProperty('--cf-r', (Math.random() * 720 - 360) + 'deg');
      p.style.setProperty('--cf-dur', (Math.random() * 0.8 + 0.7) + 's');
      document.body.appendChild(p);
      setTimeout(function (el) { el.remove(); }, 1800);
    }
  }
})();
</script>
