<?php
  // Group consecutive same-direction messages for a WhatsApp-style thread.
  $groups = array();
  foreach ($thread as $m)
  {
    $day = date('Y-m-d', strtotime($m['created_at']));
    $last = count($groups) ? $groups[count($groups) - 1] : NULL;
    if ($last && $last['dir'] === $m['direction'] && $last['day'] === $day)
    {
      $groups[count($groups) - 1]['msgs'][] = $m;
    }
    else
    {
      $groups[] = array('dir' => $m['direction'], 'day' => $day, 'msgs' => array($m));
    }
  }
?>
<?php function _day_label($day) { return $day === date('Y-m-d') ? 'Today' : ($day === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('M j, Y', strtotime($day))); } ?>
<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Chat <span class="mono" style="font-size:1.1rem;"><?= html_escape($conv['wa_id']) ?></span></h1>
    <div class="page-sub">
      Bot is <strong><?= $conv['bot_active'] ? 'on' : 'paused (you are replying manually)' ?></strong>
      · state: <span class="mono"><?= html_escape($conv['state']) ?></span>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?= form_open('admin/chats/toggle_bot/' . $conv['id'], array('class' => 'd-inline')) ?>
      <button type="submit" class="btn btn-sm <?= $conv['bot_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
        <?= $conv['bot_active'] ? '<i class="bi bi-pause-circle me-1"></i> Pause bot' : '<i class="bi bi-play-circle me-1"></i> Re-enable bot' ?>
      </button>
    <?= form_close() ?>
    <a href="<?= site_url('admin/chats') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> All chats</a>
  </div>
</div>

<div class="card">
  <div class="card-body chat-scroll" id="chatThread">
    <?php if ( ! $groups): ?>
      <div class="empty-state">
        <div class="empty-ico"><i class="bi bi-chat-dots"></i></div>
        <h4>No messages yet</h4>
        <p>This conversation is empty. Reply below to start it.</p>
      </div>
    <?php endif; ?>
    <?php foreach ($groups as $g): ?>
      <div class="day-sep"><?= _day_label($g['day']) ?></div>
      <?php if ($g['dir'] === 'in'): ?>
        <div class="d-flex justify-content-start mb-1 chat-group">
          <?php foreach ($g['msgs'] as $i => $m): ?>
            <div class="chat-bubble chat-bubble-in">
              <div><?= nl2br(html_escape($m['body'])) ?></div>
              <?php if ($i === count($g['msgs']) - 1): ?>
                <div class="small opacity-50 mt-1"><?= html_escape(date('H:i', strtotime($m['created_at']))) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="d-flex justify-content-end mb-1 chat-group">
          <?php foreach ($g['msgs'] as $i => $m): ?>
            <div class="chat-bubble chat-bubble-out">
              <div><?= nl2br(html_escape($m['body'])) ?></div>
              <?php if ($i === count($g['msgs']) - 1): ?>
                <div class="small opacity-60 mt-1 text-end">
                  <?= html_escape(date('H:i', strtotime($m['created_at']))) ?>
                  <i class="bi <?= $m['status'] === 'failed' ? 'bi-exclamation-circle text-danger' : 'bi-check2-all' ?>"></i>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-sm btn-light shadow chat-jump" id="chatJump" title="Jump to latest"><i class="bi bi-arrow-down"></i></button>
  <div class="card-footer bg-white">
    <?= form_open('admin/chats/reply/' . $conv['id'], array('id' => 'replyForm')) ?>
      <div class="input-group">
        <textarea name="message" rows="2" class="form-control" id="replyBox" placeholder="Reply as the business… (Enter to send, Shift+Enter for new line)" required></textarea>
        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Send</button>
      </div>
    <?= form_close() ?>
    <div class="form-text mt-1">Sending a reply pauses the bot for this conversation so it doesn't talk over you.</div>
  </div>
</div>

<style>
.chat-scroll{ max-height: 480px; overflow-y: auto; display: flex; flex-direction: column; }
.day-sep{
  align-self: center; font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
  color: var(--muted); background: #f1eee8; border: 1px solid var(--line); border-radius: 999px;
  padding: .2rem .7rem; margin: .6rem 0;
}
.chat-group{ display: flex; flex-direction: column; align-items: flex-start; }
.d-flex.justify-content-end.chat-group{ align-items: flex-end; }
.chat-group .chat-bubble{ max-width: 75%; }
.chat-group + .chat-group{ margin-top: .35rem; }
.chat-jump{
  position: absolute; right: 1.5rem; bottom: 6.5rem; z-index: 5;
  width: 36px; height: 36px; border-radius: 50%; display: none; place-items: center;
  box-shadow: var(--shadow-md);
}
.card{ position: relative; }
</style>

<script>
(function () {
  var thread = document.getElementById('chatThread');
  var jump = document.getElementById('chatJump');
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function scrollBottom() {
    if (!thread) return;
    thread.scrollTop = thread.scrollHeight;
  }
  scrollBottom();

  if (thread && jump) {
    thread.addEventListener('scroll', function () {
      var nearBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 120;
      jump.style.display = nearBottom ? 'none' : 'grid';
    });
    jump.addEventListener('click', function () {
      thread.scrollTo({ top: thread.scrollHeight, behavior: reduce ? 'auto' : 'smooth' });
    });
  }

  // Enter sends, Shift+Enter makes a new line.
  var box = document.getElementById('replyBox');
  var form = document.getElementById('replyForm');
  if (box && form) {
    box.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (box.value.trim()) form.submit();
      }
    });
  }
})();
</script>
