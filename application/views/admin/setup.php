<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Setup guide</h1>
    <div class="page-sub">Follow these steps in order — your bot goes live once they're all done.</div>
  </div>
  <div>
    <a href="<?= site_url('admin/setup') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
  </div>
</div>

<?php
  $pct = $steps_total ? (int)round(($steps_done / $steps_total) * 100) : 0;
?>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <strong><i class="bi bi-rocket-takeoff me-1"></i> <?= $steps_done ?> of <?= $steps_total ?> steps done</strong>
      <span class="badge <?= $steps_done === $steps_total ? 'text-bg-success' : 'text-bg-warning' ?>"><?= $pct ?>%</span>
    </div>
    <div class="progress" style="height:10px;">
      <div class="progress-bar" style="width:<?= $pct ?>%; background:linear-gradient(90deg,#f59e0b,#f97316);"></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($steps as $st): ?>
    <div class="col-md-6 col-xl-4" data-reveal>
      <div class="card h-100 <?= $st['done'] ? 'border-success' : '' ?>" style="border-left-width:3px;">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="step-num-admin <?= $st['done'] ? 'done' : '' ?>">
              <?php if ($st['done']): ?><i class="bi bi-check-lg"></i><?php else: ?><?= (int)$st['num'] ?><?php endif; ?>
            </span>
            <strong><?= html_escape($st['title']) ?></strong>
          </div>
          <p class="text-secondary small mb-3"><?= html_escape($st['desc']) ?></p>

          <?php if ($st['key'] === 'whatsapp' || $st['key'] === 'ai'): ?>
            <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#help-<?= $st['key'] ?>" aria-expanded="false">
              <i class="bi bi-question-circle me-1"></i> Where do I get these?
            </button>
            <div class="collapse" id="help-<?= $st['key'] ?>">
              <?php if ($st['key'] === 'whatsapp'): ?>
                <div class="bg-light border rounded p-3 small text-secondary mb-3" style="line-height:1.7;">
                  <strong class="text-body">Get the 4 WhatsApp values (≈30 min, free):</strong><br>
                  1. Go to <a href="https://developers.facebook.com" target="_blank" rel="noopener">developers.facebook.com</a> → My Apps → <strong>Create App</strong> → type <strong>Business</strong>.<br>
                  2. Use case: <strong>"Connect with customers through WhatsApp"</strong>.<br>
                  3. In <strong>API Setup</strong> add a phone number (use the free <strong>test number</strong> while learning) and copy the <strong>Phone number ID</strong>.<br>
                  4. Create a permanent token: <strong>Business Settings → System users → Add</strong> → assign your app + WhatsApp (Full control) → <strong>Generate token</strong> with permissions <code>business_management</code>, <code>whatsapp_business_messaging</code>, <code>whatsapp_business_management</code>. It starts with <code>EAAG…</code>.<br>
                  5. <strong>App settings → Basic</strong>: copy the <strong>App secret</strong>.<br>
                  6. Make up any random <strong>verify token</strong> (e.g. <code>whatsdesk-abc123</code>) — you'll type it into Meta again in step 7.
                </div>
              <?php else: ?>
                <div class="bg-light border rounded p-3 small text-secondary mb-3" style="line-height:1.7;">
                  <strong class="text-body">Get your AI key (2 min):</strong><br>
                  1. <strong>Free option — Groq:</strong> <a href="https://console.groq.com" target="_blank" rel="noopener">console.groq.com</a> → <strong>API Keys → Create</strong> (no card, generous daily free limit). Then in Settings choose provider <code>groq</code> — model and base URL fill in automatically.<br>
                  2. OpenAI: <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com/api-keys</a> → <strong>Create new secret key</strong> (starts with <code>sk-…</code>). Add a little credit — a small business typically spends a few dollars a month.<br>
                  3. Cheap alternative — DeepSeek: <a href="https://platform.deepseek.com" target="_blank" rel="noopener">platform.deepseek.com</a> → API keys. Then set provider <code>deepseek</code>, model <code>deepseek-chat</code>, base URL <code>https://api.deepseek.com/v1</code>.<br>
                  4. Paste the key in Settings → AI and press <strong>Test AI connection</strong>.
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <a href="<?= html_escape($st['url']) ?>" class="btn btn-sm <?= $st['done'] ? 'btn-outline-success' : 'btn-primary' ?>">
            <?= html_escape($st['cta']) ?>
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<style>
  .step-num-admin{
    width:30px; height:30px; border-radius:50%; flex:0 0 auto; display:grid; place-items:center;
    background:var(--ink); color:var(--accent); font-family:var(--font-display); font-weight:600; font-size:.95rem;
  }
  .step-num-admin.done{ background:var(--ok); color:#fff; }
</style>

<!-- ============ Go live ============ -->
<div class="card mb-4" id="go-live">
  <div class="card-header bg-white"><strong><i class="bi bi-send me-1"></i> Step 7 — Go live</strong></div>
  <div class="card-body">
    <div class="alert alert-warning small">
      <i class="bi bi-info-circle me-1"></i>
      <strong>Working on localhost?</strong> Meta requires a <strong>public HTTPS address</strong> for the webhook.
      Install <a href="https://ngrok.com" target="_blank" rel="noopener">ngrok</a>, run <code class="mono">ngrok http 80</code>, and use the <code class="mono">https://….ngrok-free.app</code> address it shows instead of <code class="mono">localhost</code> below.
    </div>

    <h6 class="fw-bold mt-3">A. Point Meta's webhook at your site</h6>
    <p class="text-secondary small mb-2">In the Meta app → <strong>WhatsApp → Configuration</strong>:</p>
    <div class="input-group mb-2">
      <span class="input-group-text">Callback URL</span>
      <input type="text" class="form-control mono copy-target" value="<?= html_escape($webhook_url) ?>" readonly>
      <button class="btn btn-outline-secondary btn-copy" type="button" data-copy="<?= html_escape($webhook_url) ?>"><i class="bi bi-clipboard me-1"></i>Copy</button>
    </div>
    <p class="text-secondary small mb-3">
      Verify token: the random string from step 3 (it must match the one in Settings → WhatsApp).
      Then under <strong>Webhook fields</strong>, subscribe to <strong>messages</strong> and save.
    </p>

    <h6 class="fw-bold mt-3">B. Start the background worker (cron) — optional</h6>
    <p class="text-secondary small mb-2"><i class="bi bi-info-circle me-1"></i>Your bot already replies <strong>instantly through the webhook</strong> — cron is just a safety net that catches a reply if the webhook ever misses one. Skip this and your bot still works. If you want it, run this every minute on your host/server — or use a free service like <a href="https://cron-job.org" target="_blank" rel="noopener">cron-job.org</a> with the URL:</p>
    <div class="input-group mb-3">
      <span class="input-group-text">Cron URL</span>
      <input type="text" class="form-control mono copy-target" value="<?= html_escape($cron_url) ?>" readonly>
      <button class="btn btn-outline-secondary btn-copy" type="button" data-copy="<?= html_escape($cron_url) ?>"><i class="bi bi-clipboard me-1"></i>Copy</button>
    </div>
    <div class="bg-dark text-light rounded p-3 mono small mb-3" style="overflow-x:auto;">
      * * * * * php /path/to/project/index.php cron run &gt;&gt; /path/to/project/application/logs/cron.log 2&gt;&amp;1
    </div>
    <p class="text-secondary small mb-0">
      The green "Bot worker: live" pill in the top bar confirms it's running. The webhook also replies instantly on its own, so cron is just the safety net.
    </p>
  </div>
</div>

<!-- ============ Full health check (advanced) ============ -->
<div class="card">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <strong><i class="bi bi-clipboard2-pulse me-1"></i> Full health check</strong>
    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#healthCheck" aria-expanded="false">
      Show / hide
    </button>
  </div>
  <div class="collapse" id="healthCheck">
    <div class="card-body">
      <?php if ($ready): ?>
        <div class="alert alert-success py-2 small">
          <i class="bi bi-check-circle-fill me-1"></i>
          <strong>No blocking issues.</strong>
          <?= $warns > 0 ? $warns . ' minor warning' . ($warns === 1 ? '' : 's') . ' — review them when you have a minute.' : 'Everything looks configured correctly.' ?>
        </div>
      <?php else: ?>
        <div class="alert alert-danger py-2 small">
          <i class="bi bi-x-circle-fill me-1"></i>
          <strong><?= $fails ?> issue<?= $fails === 1 ? '' : 's' ?> need attention.</strong>
          Fix them in the order below, then refresh this page.
        </div>
      <?php endif; ?>

      <?php foreach ($groups as $group_name => $items): ?>
        <h6 class="fw-bold mt-3 mb-2"><?= html_escape($group_name) ?></h6>
        <table class="table table-sm table-hover align-middle">
          <tbody>
            <?php foreach ($items as $it): ?>
              <?php
                $icon = array('ok' => 'bi-check-circle-fill text-success', 'warn' => 'bi-exclamation-triangle-fill text-warning', 'fail' => 'bi-x-circle-fill text-danger');
              ?>
              <tr>
                <td style="width:36px;"><i class="bi <?= $icon[$it['status']] ?>"></i></td>
                <td class="fw-semibold" style="width:30%;"><?= html_escape($it['label']) ?></td>
                <td class="mono small text-secondary" style="width:24%;"><?= html_escape($it['detail']) ?></td>
                <td class="small text-secondary"><?= html_escape($it['hint']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endforeach; ?>
    </div>
  </div>
</div>


