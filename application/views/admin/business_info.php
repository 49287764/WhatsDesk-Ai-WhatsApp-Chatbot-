<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Business info</h1>
    <div class="page-sub">One document that teaches the bot everything about your business.</div>
  </div>
</div>

<?php if ($document !== ''): ?>
  <div class="alert alert-success d-flex align-items-start gap-2 mb-4" role="alert" style="border-left:4px solid var(--ok);">
    <i class="bi bi-check-circle-fill mt-1"></i>
    <div>
      <strong>Your document is saved</strong> — the bot is already answering customers from it
      (<?= number_format($doc_chars) ?> characters).
      Change it below and click <strong>Save business info</strong> again to update it.
    </div>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header bg-white"><strong><i class="bi bi-journal-text me-1"></i> Your business document</strong></div>
      <div class="card-body">
        <p class="text-secondary small mb-3">
          Paste everything a customer might ask about — or upload a <code>.txt</code> / <code>.md</code> / <code>.docx</code>-exported file.
          The bot reads this document and answers WhatsApp questions from it (never invents anything).
        </p>

        <div class="d-flex gap-2 flex-wrap mb-3">
          <label class="btn btn-outline-primary btn-sm mb-0">
            <i class="bi bi-upload me-1"></i> Upload a file
            <input type="file" id="docFile" accept=".txt,.md,.text" class="d-none">
          </label>
          <span class="align-self-center text-secondary small" id="fileStatus">No file chosen</span>
        </div>

        <?= form_open('admin/business_info/save') ?>
          <textarea name="document" id="docText" rows="16" class="form-control mono" style="font-size:.85rem;"
            placeholder="Example — write it in your own words:

We are Sweet Treats Bakery, located at 12 Market Street, Springfield.
We are open Monday to Saturday 8am to 8pm, closed Sundays.

OUR PRODUCTS
- Birthday cakes from $25 (chocolate, vanilla, red velvet)
- Cupcakes $3.50 each, boxes of 6 for $18
- Custom wedding cakes — starting at $120, order 2 weeks ahead

DELIVERY & PAYMENT
We deliver within Springfield for $5, free over $40.
We accept cash on delivery, card in store, and bank transfer.

BOOKINGS & POLICIES
Custom orders need 48 hours notice...
(and so on — the more detail, the better the bot answers)"><?= html_escape($document) ?></textarea>
          <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
            <span class="text-secondary small"><span id="docCount"><?= (int)$doc_chars ?></span> characters · <span id="docWords"><?= (int)$doc_words ?></span> words</span>
            <button type="submit" class="btn btn-primary" id="saveBtn"><i class="bi bi-check-lg me-1"></i> Save business info</button>
          </div>
        <?= form_close() ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-building me-1"></i> Business details</strong>
        <?= form_open('admin/business_info/autofill', array('class' => 'm-0')) ?>
          <button type="submit" class="btn btn-sm btn-outline-primary" title="Pull name, hours, address, phone and delivery info out of your document">
            <i class="bi bi-magic me-1"></i> Auto-fill
          </button>
        <?= form_close() ?>
      </div>
      <div class="card-body pt-2">
        <p class="text-secondary small mb-2">These are the quick facts the bot answers instantly (menu, hours, delivery…). If any still show the demo default, hit <strong>Auto-fill</strong> — or edit them in <a href="<?= site_url('admin/settings') ?>#sec-business">Settings</a>.</p>
        <ul class="list-unstyled mb-0" style="line-height:1.9;">
          <?php foreach ($facts as $f): ?>
            <li class="d-flex align-items-start gap-2">
              <i class="bi <?= $f['ok'] ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-warning' ?> mt-1"></i>
              <span class="small">
                <strong><?= html_escape($f['label']) ?>:</strong>
                <?php if ($f['ok']): ?>
                  <span class="text-secondary"><?= html_escape($f['value']) ?></span>
                <?php else: ?>
                  <span class="text-warning-emphasis"><?= html_escape($f['value']) ?> — demo default</span>
                <?php endif; ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header bg-white"><strong><i class="bi bi-lightbulb me-1"></i> What to include</strong></div>
      <div class="card-body small text-secondary">
        <ul class="mb-2 ps-3" style="line-height:1.8;">
          <li>What you sell / your services and <strong>prices</strong></li>
          <li>Opening hours &amp; location</li>
          <li>Delivery, shipping &amp; payment rules</li>
          <li>Booking / ordering steps</li>
          <li>Common FAQs (refunds, allergens, warranties…)</li>
          <li>Contact details &amp; social links</li>
        </ul>
        <p class="mb-0"><i class="bi bi-info-circle me-1"></i> The bot always quotes <strong>your</strong> document — it won't make things up. Products in your catalog can still be ordered in chat.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-white"><strong><i class="bi bi-eye me-1"></i> How the bot sees it</strong></div>
      <div class="card-body small">
        <p class="text-secondary mb-2">This is the beginning of what gets sent with every conversation:</p>
        <div class="bg-light border rounded p-3 mono" style="font-size:.75rem; max-height:240px; overflow:auto; white-space:pre-wrap; color:#44403c;" id="botPreview"></div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var text = document.getElementById('docText');
  var count = document.getElementById('docCount');
  var words = document.getElementById('docWords');
  var preview = document.getElementById('botPreview');
  var fileInput = document.getElementById('docFile');
  var fileStatus = document.getElementById('fileStatus');
  var saveBtn = document.getElementById('saveBtn');

  function refresh() {
    var t = text.value;
    count.textContent = t.length;
    words.textContent = t.trim() === '' ? 0 : t.trim().split(/\s+/).length;
    preview.textContent = 'YOUR BUSINESS DOCUMENT (authoritative — answer from this first):\n\n' +
      (t.trim() === '' ? '(empty — add your business details above)' : t.slice(0, 500)) +
      (t.length > 500 ? '\n…' : '');
  }

  text.addEventListener('input', refresh);

  fileInput.addEventListener('change', function () {
    var f = fileInput.files[0];
    if (!f) { fileStatus.textContent = 'No file chosen'; return; }
    if (f.size > 1.5 * 1024 * 1024) {
      fileStatus.textContent = 'File too large (max 1.5 MB) — paste the text instead.';
      return;
    }
    var reader = new FileReader();
    reader.onload = function (e) {
      text.value = e.target.result;
      fileStatus.textContent = 'Loaded "' + f.name + '" — review it below, then click Save.';
      refresh();
      // Make the next step obvious: scroll to the save button and highlight it.
      saveBtn.classList.add('btn-warning');
      saveBtn.classList.remove('btn-primary');
      setTimeout(function () {
        saveBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 60);
      setTimeout(function () {
        saveBtn.classList.remove('btn-warning');
        saveBtn.classList.add('btn-primary');
      }, 3500);
    };
    reader.readAsText(f);
  });

  refresh();
})();
</script>
