<div class="page-head mb-4">
  <div>
    <h1 class="page-title">FAQs</h1>
    <div class="page-sub">Quick answers for common questions — optional, since the bot also answers from your Business info document.</div>
  </div>
  <a href="<?= site_url('admin/knowledge/form') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add entry</a>
</div>

<div class="alert alert-light border small">
  Add the questions customers ask most often, and the bot answers them instantly.
  (Tip: you can put all of this in your <a href="<?= site_url('admin/business_info') ?>">Business info</a> document instead.)
  Answers support <code>{business_hours}</code>, <code>{business_address}</code> and <code>{delivery_info}</code> placeholders.
</div>

<div class="card">
  <table class="table table-hover mb-0">
    <thead class="table-light">
      <tr><th>Question</th><th>Keywords</th><th>Active</th><th class="text-end">Actions</th></tr>
    </thead>
    <tbody>
      <?php if ( ! $entries): ?>
        <tr><td colspan="4">
          <div class="empty-state">
            <div class="empty-ico"><i class="bi bi-patch-question"></i></div>
            <h4>No FAQ entries yet</h4>
            <p>Add the questions customers ask most often and the bot answers them instantly. This is optional — the bot also answers from your Business info.</p>
          </div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($entries as $entry): ?>
        <tr>
          <td><?= html_escape($entry['question']) ?></td>
          <td class="text-secondary small"><?= html_escape($entry['keywords'] ?: '—') ?></td>
          <td>
            <?php if ($entry['active']): ?>
              <span class="badge text-bg-success">Active</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">Off</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a href="<?= site_url('admin/knowledge/form/' . $entry['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <?= form_open('admin/knowledge/delete/' . $entry['id'], array('class' => 'd-inline', 'onsubmit' => 'return confirm("Delete this entry?");')) ?>
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            <?= form_close() ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
