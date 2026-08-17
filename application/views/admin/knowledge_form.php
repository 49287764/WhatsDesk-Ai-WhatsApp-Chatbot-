<?php $entry = $entry ?: array(); ?>
<div class="page-head mb-4">
  <div>
    <h1 class="page-title"><?= $entry ? 'Edit entry' : 'Add entry' ?></h1>
    <div class="page-sub">The bot searches these when a question matches.</div>
  </div>
  <a href="<?= site_url('admin/knowledge') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Knowledge base</a>
</div>

<div class="card p-4" style="max-width: 640px;">
  <?= form_open($entry ? 'admin/knowledge/form/' . $entry['id'] : 'admin/knowledge/form') ?>
    <div class="mb-3">
      <label class="form-label">Question *</label>
      <input type="text" name="question" class="form-control" required
             value="<?= html_escape(isset($entry['question']) ? $entry['question'] : '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Keywords</label>
      <input type="text" name="keywords" class="form-control"
             value="<?= html_escape(isset($entry['keywords']) ? $entry['keywords'] : '') ?>"
             placeholder="comma-separated, e.g. hours, open, close, timing">
      <div class="form-text">Used to match customer questions to this answer.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Answer *</label>
      <textarea name="answer" rows="4" class="form-control" required><?= html_escape(isset($entry['answer']) ? $entry['answer'] : '') ?></textarea>
    </div>
    <div class="form-check mb-4">
      <input type="checkbox" name="active" value="1" class="form-check-input" id="active"
             <?= ( ! isset($entry['active']) || $entry['active']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="active">Active</label>
    </div>
    <button type="submit" class="btn btn-primary">Save entry</button>
  <?= form_close() ?>
</div>
