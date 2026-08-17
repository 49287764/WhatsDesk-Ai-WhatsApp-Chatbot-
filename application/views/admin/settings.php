<?php
/**
 * Small "?" help button that opens a Bootstrap popover.
 * Explains what a field is, whether it's required and where to get it.
 */
function fld_help($title, $body)
{
	$safe_title = htmlspecialchars($title, ENT_QUOTES);
	$safe_body  = htmlspecialchars($body, ENT_QUOTES);
	return '<button type="button" class="help-dot" tabindex="0" aria-label="Help: ' . $safe_title . '" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-html="true" data-bs-title="' . $safe_title . '" data-bs-content="' . $safe_body . '"><i class="bi bi-question-circle"></i></button>';
}
?>
<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Settings</h1>
    <div class="page-sub">WhatsApp connection, AI provider and your business's facts.</div>
  </div>
  <div>
    <a href="<?= site_url('admin/setup') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-clipboard2-pulse me-1"></i> Run setup check</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="text-secondary small mb-1">Webhook URL (enter in Meta dashboard)</div>
        <div class="input-group">
          <input type="text" class="form-control mono" value="<?= html_escape(site_url('whatsapp/webhook')) ?>" readonly>
          <button class="btn btn-outline-secondary btn-copy" type="button" data-copy="<?= html_escape(site_url('whatsapp/webhook')) ?>"><i class="bi bi-clipboard"></i></button>
        </div>
      </div>
      <div class="col-md-6">
        <div class="text-secondary small mb-1">Cron worker URL (needs the cron key below)</div>
        <div class="input-group">
          <input type="text" class="form-control mono" value="<?= html_escape(site_url('cron/run?key=' . ($settings['cron_key'] ?? '…'))) ?>" readonly>
          <button class="btn btn-outline-secondary btn-copy" type="button" data-copy="<?= html_escape(site_url('cron/run?key=' . ($settings['cron_key'] ?? '…'))) ?>"><i class="bi bi-clipboard"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== Connection status hub ===== -->
<div class="card mb-3" id="conn-hub">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <strong><i class="bi bi-activity me-1"></i> Connection status</strong>
    <span class="badge text-bg-secondary" style="font-size:.68rem;">live</span>
  </div>
  <div class="card-body">
    <div class="row g-2">
      <?php foreach ($conn as $key => $c): ?>
        <div class="col-sm-6 col-lg" data-reveal>
          <a href="<?= html_escape($c['url']) ?>" class="conn-chip <?= $c['ok'] ? 'ok' : 'warn' ?> d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="conn-dot"></span>
              <span class="conn-icon"><i class="bi <?= $c['icon'] ?>"></i></span>
              <strong class="conn-label"><?= html_escape($c['label']) ?></strong>
            </div>
            <div class="conn-detail mono"><?= html_escape($c['detail']) ?></div>
            <div class="conn-hint"><?= html_escape($c['hint']) ?></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ( ! empty($token_warn)): ?>
  <div class="alert alert-<?= $token_warn_type ?> d-flex align-items-start gap-2 mb-3" role="alert" style="border-left:4px solid;">
    <i class="bi bi-<?= ($token_warn_type === 'danger') ? 'exclamation-triangle-fill' : (($token_warn_type === 'warning') ? 'exclamation-circle-fill' : 'info-circle-fill') ?> fs-4 flex-shrink-0 mt-1"></i>
    <div>
      <strong><?= ($token_warn_type === 'danger') ? 'Token likely expired' : (($token_warn_type === 'warning') ? 'Token may expire soon' : 'Token not yet verified') ?></strong><br>
      <?= $token_warn ?>
      <?php if ($token_warn_type !== 'info'): ?>
        <br><small class="text-muted">System User tokens (expiry: Never) do not expire — switch to one to avoid this.</small>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<?= form_open('admin/settings/save') ?>

  <div class="card mb-3" id="sec-business">
    <div class="card-header bg-white"><strong><i class="bi bi-shop me-1"></i> Business information</strong></div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">Business name <?= fld_help('Business name', '<b>Recommended.</b> The bot greets customers with it (“Welcome to X!”) and it appears on your site. Auto-filled when you upload a business document.') ?></label>
        <input type="text" name="business_name" class="form-control" value="<?= html_escape($settings['business_name'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone (display) <?= fld_help('Phone (display)', '<b>Recommended.</b> Shown to customers when they ask for contact details, and used for the WhatsApp button on your site.') ?></label>
        <input type="text" name="business_phone" class="form-control" value="<?= html_escape($settings['business_phone'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Address <?= fld_help('Address', '<b>Recommended.</b> The bot answers “where are you?” with this. Auto-filled from your business document.') ?></label>
        <input type="text" name="business_address" class="form-control" value="<?= html_escape($settings['business_address'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Opening hours <?= fld_help('Opening hours', '<b>Recommended.</b> Used for “are you open?” answers. Auto-filled from your business document.') ?></label>
        <input type="text" name="business_hours" class="form-control" value="<?= html_escape($settings['business_hours'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Currency symbol <span class="text-secondary fw-normal">(shown before prices)</span></label>
        <input type="text" name="currency_symbol" class="form-control" value="<?= html_escape($settings['currency_symbol'] ?? '$') ?>" placeholder="$" maxlength="8">
        <div class="form-text">e.g. <code>$</code>, <code>Rs.</code>, <code>€</code> — used in the menu, cart, orders and reports.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Delivery / service info <?= fld_help('Delivery / service info', '<b>Recommended.</b> Delivery areas, charges, policy — the bot uses this for delivery questions. Auto-filled from your business document.') ?></label>
        <input type="text" name="delivery_info" class="form-control" value="<?= html_escape($settings['delivery_info'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="card mb-3" id="sec-whatsapp">
    <div class="card-header bg-white"><strong><i class="bi bi-whatsapp me-1"></i> WhatsApp Cloud API</strong></div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">Access token (system user) <?= fld_help('Access token', '<b>Required.</b> Lets the app send/receive WhatsApp messages on your number. Get it from Meta: business.facebook.com → Users → System users → your user → Generate token (see the permanent-token guide below). Temporary tokens expire — use a <b>Never</b> expiry one.') ?></label>
        <div class="input-group">
          <input type="password" name="wa_token" class="form-control" value="<?= html_escape($settings['wa_token'] ?? '') ?>" autocomplete="off">
          <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=wa_token]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone number ID <?= fld_help('Phone number ID', '<b>Required.</b> The unique ID of your WhatsApp number in Meta. Get it: Meta → WhatsApp → API Setup → “Phone number ID”. It starts with a number, e.g. 1302390639617328.') ?></label>
        <input type="text" name="wa_phone_number_id" class="form-control" value="<?= html_escape($settings['wa_phone_number_id'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">App secret <?= fld_help('App secret', '<b>Required.</b> Used to verify that webhook messages really come from Meta (HMAC signature). Get it: Meta → App settings → Basic → App secret → Show.') ?></label>
        <div class="input-group">
          <input type="password" name="wa_app_secret" class="form-control" value="<?= html_escape($settings['wa_app_secret'] ?? '') ?>" autocomplete="off">
          <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=wa_app_secret]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Webhook verify token <?= fld_help('Verify token', '<b>Required.</b> A secret string <i>you</i> invent (e.g. mybusiness-9f3k2). It proves to Meta that the webhook belongs to you — the same value goes in Meta → WhatsApp → Configuration → Verify token.') ?></label>
        <input type="text" name="wa_verify_token" class="form-control" value="<?= html_escape($settings['wa_verify_token'] ?? '') ?>">
        <div class="form-text">Any secret string you choose. Must match the one you enter in the Meta dashboard.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Graph API version <?= fld_help('Graph API version', '<b>Optional.</b> Meta API version used for requests. Leave the default — only change it if Meta deprecates your version (they announce it well in advance).') ?></label>
        <input type="text" name="wa_graph_version" class="form-control" value="<?= html_escape($settings['wa_graph_version'] ?? 'v25.0') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Owner WhatsApp number (notifications) <?= fld_help('Owner number', '<b>Recommended.</b> Where new-order and “customer wants a human” alerts are sent. Format: country code, no +, no spaces — e.g. 923001234567. Must have messaged the bot once to open a reply window.') ?></label>
        <input type="text" name="owner_wa_id" class="form-control" value="<?= html_escape($settings['owner_wa_id'] ?? '') ?>" placeholder="e.g. 15551234567">
        <div class="form-text">Country code, no +, no spaces. New-order and human-handoff alerts go here.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Order status template (optional) <?= fld_help('Order status template', '<b>Optional.</b> Only needed if you want automatic status messages <i>after</i> the 24-hour reply window closes. It is an approved Meta template name with two parameters: order number and status. Leave empty if you reply manually.') ?></label>
        <input type="text" name="wa_order_template" class="form-control" value="<?= html_escape($settings['wa_order_template'] ?? '') ?>" placeholder="e.g. order_status_update">
        <div class="form-text">Approved Meta template name with two body parameters (order number, status). Enables the template button on order pages — works outside the 24h window.</div>
      </div>
      <div class="col-12">
        <div class="form-text"><i class="bi bi-info-circle me-1"></i> After saving, use the <strong>Send test message</strong> button below the form to check the connection.</div>
      </div>
      <div class="col-12">
        <details class="border rounded p-3" style="background:#faf9f7;">
          <summary class="fw-semibold small"><i class="bi bi-key me-1"></i> How to create a permanent token (never expires)</summary>
          <div class="small text-secondary mt-2 lh-lg">
            Tokens generated with the “Generate access token” button in Meta expire quickly. A <strong>System User token</strong> lasts forever — do this once:
            <ol class="mb-2 ps-3">
              <li>Open <strong>business.facebook.com/settings/system-users</strong> (Business settings → Users → System users).</li>
              <li><strong>Add</strong> → name it <code>WhatsDesk</code> → role <strong>Admin</strong> → <strong>Create</strong>.</li>
              <li>Click <strong>WhatsDesk</strong> → <strong>Add assets</strong> → <strong>Apps</strong> → select your WhatsApp app → toggle <strong>Full control</strong> → <strong>Save changes</strong>.</li>
              <li><strong>Generate new token</strong> → select your app → expiry <strong>Never</strong> → permissions <code>whatsapp_business_messaging</code> + <code>whatsapp_business_management</code> → <strong>Generate token</strong> → copy it.</li>
              <li>Paste it in the <strong>Access token</strong> field above, click <strong>Save</strong>, then <strong>Check WhatsApp connection</strong>.</li>
            </ol>
            If your current token stops working (connection chip turns red), it expired — just paste a fresh one here.
          </div>
        </details>
      </div>
    </div>
  </div>

  <div class="card mb-3" id="sec-ai">
    <div class="card-header bg-white"><strong><i class="bi bi-stars me-1"></i> AI (OpenAI-compatible)</strong></div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">Provider <?= fld_help('AI provider', '<b>Required for AI mode.</b> Choose where the AI brain comes from — this pre-fills the model and base URL. <b>Groq</b> has a free tier (no card). <b>OpenAI</b> and <b>DeepSeek</b> are paid per use. The bot also works fully without AI (offline answers).') ?></label>
        <?= form_dropdown('ai_provider', array('openai' => 'OpenAI', 'groq' => 'Groq (free tier)', 'deepseek' => 'DeepSeek', 'custom' => 'Custom (any OpenAI-compatible)'), $settings['ai_provider'] ?? 'openai', 'class="form-select" id="aiProvider"') ?>
        <div class="form-text">Choosing a provider pre-fills the model and base URL. You can still edit them.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Model <?= fld_help('AI model', '<b>Required for AI mode.</b> Which model answers your customers. It must exist on the chosen provider: OpenAI → gpt-4o-mini, Groq → openai/gpt-oss-20b, DeepSeek → deepseek-chat. Wrong name = AI fails (bot still answers offline).') ?></label>
        <input type="text" name="ai_model" id="aiModel" class="form-control" value="<?= html_escape($settings['ai_model'] ?? 'gpt-4o-mini') ?>" placeholder="gpt-4o-mini / deepseek-chat">
      </div>
      <div class="col-md-6">
        <label class="form-label">API key <?= fld_help('AI API key', '<b>Required for AI mode</b> (bot works without it, but answers are less conversational). Get it from your provider: OpenAI → platform.openai.com, Groq → console.groq.com (free), DeepSeek → platform.deepseek.com. Click “Test AI connection” below to verify.') ?></label>
        <div class="input-group">
          <input type="password" name="ai_api_key" class="form-control" value="<?= html_escape($settings['ai_api_key'] ?? '') ?>" autocomplete="off">
          <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=ai_api_key]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">API base URL <?= fld_help('API base URL', '<b>Required.</b> The provider\'s endpoint. Auto-filled by the Provider dropdown: OpenAI → https://api.openai.com/v1, Groq → https://api.groq.com/openai/v1, DeepSeek → https://api.deepseek.com/v1.') ?></label>
        <input type="text" name="ai_base_url" id="aiBaseUrl" class="form-control" value="<?= html_escape($settings['ai_base_url'] ?? 'https://api.openai.com/v1') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Temperature <?= fld_help('Temperature', '<b>Optional.</b> 0 = always the same factual answer, 1 = more creative/varied. 0.3 is a good default for business answers.') ?></label>
        <input type="number" step="0.1" min="0" max="2" name="ai_temperature" class="form-control" value="<?= html_escape($settings['ai_temperature'] ?? '0.3') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Max tokens per reply <?= fld_help('Max tokens', '<b>Optional.</b> Upper limit on reply length (~750 words). 800 covers the full menu. Lower it if replies feel too long or to save tokens.') ?></label>
        <input type="number" min="64" name="ai_max_tokens" class="form-control" value="<?= html_escape($settings['ai_max_tokens'] ?? '800') ?>">
      </div>
    </div>
  </div>

  <div class="card mb-3" id="sec-behavior">
    <div class="card-header bg-white"><strong><i class="bi bi-chat-dots me-1"></i> Bot behavior</strong></div>
    <div class="card-body row g-3">
      <div class="col-12">
        <label class="form-label">Greeting <?= fld_help('Greeting', '<b>Optional.</b> The bot\'s opening line when a customer starts a chat. Leave empty to use a smart default built from your business info.') ?></label>
        <textarea name="greeting" rows="2" class="form-control"><?= html_escape($settings['greeting'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Fallback message (AI unavailable) <?= fld_help('Fallback message', '<b>Optional.</b> Shown if the AI provider is down or unconfigured and the built-in brain has no match. A friendly “sorry, we\'ll be right back” works well.') ?></label>
        <textarea name="fallback_msg" rows="2" class="form-control"><?= html_escape($settings['fallback_msg'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Collect customer name &amp; address at checkout <?= fld_help('Collect details', '<b>Recommended.</b> For delivery orders the bot asks for the customer\'s name and address before confirming. Turn off for pickup-only businesses.') ?></label>
        <?= form_dropdown('collect_customer_details', array('1' => 'Yes', '0' => 'No'), $settings['collect_customer_details'] ?? '1', 'class="form-select"') ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">Reply instantly in the webhook (recommended) <?= fld_help('Instant replies', '<b>Recommended.</b> The webhook answers within seconds. The cron worker then only handles anything that fails — set it up as a safety net either way.') ?></label>
        <?= form_dropdown('wa_process_inline', array('1' => 'Yes — replies in a few seconds', '0' => 'No — cron only (up to 1 min delay)'), $settings['wa_process_inline'] ?? '1', 'class="form-select"') ?>
        <div class="form-text">With “Yes”, the webhook answers immediately and the cron worker stays as a safety net for anything that fails.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Auto-notify customers on status change <?= fld_help('Auto-notify', '<b>Optional.</b> When you move an order (Placed → Ready → Delivered), the customer is messaged automatically. Requires the “Status change message” below.') ?></label>
        <?= form_dropdown('wa_notify_status', array('0' => 'No — I message customers manually', '1' => 'Yes — send a message automatically'), $settings['wa_notify_status'] ?? '0', 'class="form-select"') ?>
        <div class="form-text">When you move an order (e.g. Placed → Ready), the customer is messaged automatically. Free-form text inside the 24h window; the approved template outside it.</div>
      </div>
      <div class="col-12">
        <label class="form-label">Status change message <?= fld_help('Status message', '<b>Only if auto-notify is on.</b> The text customers receive. Placeholders: {order_id}, {status}, {business_name} are replaced automatically.') ?></label>
        <input type="text" name="wa_status_message" class="form-control" value="<?= html_escape($settings['wa_status_message'] ?? 'Your order #{order_id} is now {status}.') ?>" placeholder="Your order #{order_id} is now {status}.">
        <div class="form-text">Placeholders: <code>{order_id}</code>, <code>{status}</code>, <code>{business_name}</code>.</div>
      </div>
    </div>
  </div>

  <div class="card mb-3" id="sec-security">
    <div class="card-header bg-white"><strong><i class="bi bi-shield-lock me-1"></i> Security</strong></div>
    <div class="card-body">
      <label class="form-label">Cron secret key <?= fld_help('Cron secret key', '<b>Required.</b> A secret password for the cron URL so strangers can\'t trigger the worker. Any random string works (or press the dice button). It is already set — only change it if you want to rotate it; then update the cron URL above.') ?></label>
      <div class="input-group" style="max-width: 440px;">
        <input type="password" name="cron_key" class="form-control" value="<?= html_escape($settings['cron_key'] ?? '') ?>" autocomplete="off">
        <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=cron_key]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
        <button class="btn btn-outline-secondary" type="button" id="cronGen" title="Generate a random key"><i class="bi bi-dice-5"></i></button>
      </div>
      <div class="form-text">Required to run the cron worker over HTTP: <code>&lt;your-domain&gt;/index.php/cron/run?key=…</code></div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save settings</button>
<?= form_close() ?>  <div class="card mt-3" id="sec-test">
  <div class="card-header bg-white"><strong><i class="bi bi-send me-1"></i> Test your connections</strong></div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <?= form_open('admin/settings/test_ai') ?>
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-stars me-1"></i> Test AI connection</button>
          <?= form_close() ?>
          <div class="text-secondary small" style="max-width:26rem;">Sends one quick prompt to your AI provider and shows its reply — verifies the key, model and base URL without using WhatsApp.</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <?= form_open('admin/settings/test_whatsapp') ?>
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-plug me-1"></i> Check WhatsApp connection</button>
          <?= form_close() ?>
          <div class="text-secondary small" style="max-width:26rem;">Live-checks the token + phone number ID against the Graph API — no message is sent, works any time.</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <?= form_open('admin/settings/test_message', array('onsubmit' => 'return confirm("Send a test WhatsApp message to the owner number?");')) ?>
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-send me-1"></i> Send WhatsApp test message</button>
          <?= form_close() ?>
          <div class="text-secondary small" style="max-width:26rem;">Sends a WhatsApp to your owner number to verify end-to-end delivery. The owner number must have messaged the bot once so WhatsApp opens a reply window.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Field help popovers (the small “?” buttons). Bootstrap loads at the end
// of the page (footer), so wait for DOMContentLoaded + guard for it.
document.addEventListener('DOMContentLoaded', function () {
  if (typeof bootstrap === 'undefined') return;
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
    new bootstrap.Popover(el, { html: true, placement: 'top', trigger: 'focus' });
  });
});

// Quick “generate” button for the cron key (dice).
(function () {
  var btn = document.getElementById('cronGen');
  var field = document.querySelector('[name=cron_key]');
  if (!btn || !field) return;
  btn.addEventListener('click', function () {
    var chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    var out = 'cron_';
    for (var i = 0; i < 16; i++) out += chars[Math.floor(Math.random() * chars.length)];
    field.value = out;
  });
})();

// Auto-fill model + base URL when the provider changes (still editable).
(function () {
  var sel = document.getElementById('aiProvider');
  var model = document.getElementById('aiModel');
  var url = document.getElementById('aiBaseUrl');
  if (!sel || !model || !url) return;
  var presets = {
    openai:   { model: 'gpt-4o-mini',                     url: 'https://api.openai.com/v1' },
    groq:     { model: 'openai/gpt-oss-20b',              url: 'https://api.groq.com/openai/v1' },
    deepseek: { model: 'deepseek-chat',                   url: 'https://api.deepseek.com/v1' },
    custom:   { model: model.value,                       url: url.value }
  };
  // Keep the currently-selected provider in sync on load (custom keeps its values).
  if (sel.value && sel.value !== 'custom') {
    var p = presets[sel.value];
    if (p) { model.value = p.model; url.value = p.url; }
  }
  sel.addEventListener('change', function () {
    var p = presets[sel.value];
    if (!p) return;
    if (sel.value !== 'custom') {
      model.value = p.model;
      url.value = p.url;
    }
  });
})();
</script>
