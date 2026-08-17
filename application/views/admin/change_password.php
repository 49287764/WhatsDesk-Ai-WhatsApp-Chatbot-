<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Change password</h1>
    <div class="page-sub">Keep your panel secure — use something only you know.</div>
  </div>
</div>

<div class="card p-4" style="max-width: 480px;">
  <?php if ($ok): ?>
    <div class="alert alert-success py-2"><?= html_escape($ok) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= html_escape($error) ?></div>
  <?php endif; ?>

  <?= form_open('admin/auth/change_password') ?>
    <div class="mb-3">
      <label class="form-label">Current password</label>
      <div class="input-group">
        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=current_password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">New password</label>
      <div class="input-group">
        <input type="password" name="new_password" class="form-control" required autocomplete="new-password" minlength="8">
        <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=new_password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label">Confirm new password</label>
      <div class="input-group">
        <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password" minlength="8">
        <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=confirm_password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Change password</button>
  <?= form_close() ?>
</div>
