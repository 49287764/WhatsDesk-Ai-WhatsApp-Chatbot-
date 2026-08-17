<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Conversations</h1>
    <div class="page-sub">Every customer chat — step in and reply whenever you want.</div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr><th>Customer</th><th>State</th><th>Bot</th><th>Last message</th><th>Updated</th><th></th></tr>
      </thead>
      <tbody>
        <?php if ( ! $conversations): ?>
          <tr><td colspan="6">
            <div class="empty-state">
              <div class="empty-ico"><i class="bi bi-chat-dots"></i></div>
              <h4>No conversations yet</h4>
              <p>When customers message your WhatsApp number, their chats will appear here.</p>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($conversations as $c): ?>
          <tr class="<?= (int)$c['unread'] > 0 ? 'table-warning' : '' ?>">
            <td>
              <strong><?= html_escape($c['customer_name']) ?></strong>
              <div class="text-secondary small"><?= html_escape($c['wa_id']) ?></div>
            </td>
            <td><span class="badge text-bg-light border"><?= html_escape($c['state']) ?></span></td>
            <td>
              <?php if ($c['bot_active']): ?>
                <span class="badge text-bg-success">On</span>
              <?php else: ?>
                <span class="badge text-bg-danger">Paused</span>
              <?php endif; ?>
            </td>
            <td class="small text-secondary"><?= html_escape(mb_strimwidth((string)$c['last_message'], 0, 60, '…')) ?></td>
            <td class="small text-secondary"><?= html_escape(date('M j, H:i', strtotime($c['updated_at']))) ?></td>
            <td class="text-end">
              <?php if ((int)$c['unread'] > 0): ?><span class="badge text-bg-warning me-2"><?= (int)$c['unread'] ?> new</span><?php endif; ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/chats/view/' . $c['id']) ?>">Open</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
