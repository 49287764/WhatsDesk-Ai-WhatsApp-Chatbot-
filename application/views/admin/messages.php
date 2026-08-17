<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Messages</h1>
    <div class="page-sub">Contact form submissions from your website.</div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr><th>Name</th><th>Contact</th><th>Message</th><th>Received</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php if ( ! $messages): ?>
          <tr><td colspan="5">
            <div class="empty-state">
              <div class="empty-ico"><i class="bi bi-envelope-open"></i></div>
              <h4>No messages yet</h4>
              <p>Submissions from the Contact page on your website will appear here.</p>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($messages as $m): ?>
          <tr>
            <td class="fw-semibold"><?= html_escape($m['name']) ?></td>
            <td class="small">
              <?php if ($m['phone']): ?><div><i class="bi bi-telephone me-1 text-secondary"></i><?= html_escape($m['phone']) ?></div><?php endif; ?>
              <?php if ($m['email']): ?><div><i class="bi bi-envelope me-1 text-secondary"></i><?= html_escape($m['email']) ?></div><?php endif; ?>
            </td>
            <td class="small text-secondary" style="max-width:340px;"><?= html_escape(mb_strimwidth($m['message'], 0, 160, '…')) ?></td>
            <td class="small text-secondary"><?= html_escape(date('M j, H:i', strtotime($m['created_at']))) ?></td>
            <td class="text-end">
              <?= form_open('admin/messages/delete/' . $m['id'], array('class' => 'd-inline', 'onsubmit' => 'return confirm("Delete this message?");')) ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
              <?= form_close() ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
