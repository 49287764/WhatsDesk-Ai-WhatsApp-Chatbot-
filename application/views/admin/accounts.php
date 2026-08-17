<div class="page-head mb-4">
  <div>
    <h1 class="page-title">Accounts</h1>
    <div class="page-sub">Everyone who can sign in to this admin panel. Your account can't be deleted, and neither can the last one.</div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7" data-reveal>
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Panel users</span>
        <span class="badge text-bg-light"><?= count($accounts) ?> account<?= count($accounts) === 1 ? '' : 's' ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>Username</th><th>Role</th><th>Created</th><th class="text-end">Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($accounts as $acc): ?>
              <?php $me = $this->session->userdata('admin_user'); $is_me = isset($me['id']) && (int)$me['id'] === (int)$acc['id']; ?>
              <tr>
                <td class="fw-semibold">
                  <?= html_escape($acc['username']) ?>
                  <?php if ($is_me): ?><span class="badge text-bg-warning ms-1">you</span><?php endif; ?>
                  <?php if ((int)$acc['is_seed'] === 1): ?><span class="badge text-bg-secondary ms-1">unclaimed</span><?php endif; ?>
                </td>
                <td class="text-secondary small"><?= $is_me ? 'Owner' : 'Staff' ?></td>
                <td class="text-secondary small"><?= html_escape(date('M j, Y', strtotime($acc['created_at']))) ?></td>
                <td class="text-end">
                  <?php if ( ! $is_me): ?>
                    <form method="post" action="<?= site_url('admin/accounts/delete/' . (int)$acc['id']) ?>" class="d-inline"
                          onsubmit="return confirm('Delete this account? They will lose access immediately.');">
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i> Delete</button>
                    </form>
                  <?php else: ?>
                    <a href="<?= site_url('admin/accounts#changePassword') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-key"></i> Change password</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5" data-reveal>
    <div class="card h-100">
      <div class="card-header">
        <span>Add a staff account</span>
      </div>
      <div class="card-body">
        <p class="text-secondary small mb-3">Give a team member their own login — orders, chats and reports work the same for every account.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2" style="font-size:.875rem;"><?= html_escape($error) ?></div>
        <?php endif; ?>

        <?= form_open('admin/accounts') ?>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Username</label>
            <input type="text" name="username" class="form-control" required minlength="3" maxlength="30" autocomplete="off"
                   placeholder="e.g. kitchen.staff" pattern="[a-zA-Z0-9_.-]{3,30}">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Password</label>
            <div class="input-group">
              <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters">
              <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold small">Confirm password</label>
            <div class="input-group">
              <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
              <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=confirm_password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-person-plus me-1"></i> Create account</button>
        <?= form_close() ?>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3" data-reveal id="changePassword">
  <div class="card-header">
    <span><i class="bi bi-key me-1"></i> Change your password</span>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold small">Current password</label>
        <div class="input-group">
          <input type="password" name="current_password" form="pwForm" class="form-control" autocomplete="current-password" required>
          <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=current_password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold small">New password</label>
        <div class="input-group">
          <input type="password" name="new_password" form="pwForm" class="form-control" autocomplete="new-password" required minlength="8" placeholder="At least 8 characters">
          <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=new_password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold small">Confirm new password</label>
        <div class="input-group">
          <input type="password" name="confirm_password" form="pwForm" class="form-control" autocomplete="new-password" required minlength="8">
          <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=confirm_password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="col-12">
        <?= form_open('admin/auth/change_password', array('id' => 'pwForm')) ?>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update my password</button>
        <?= form_close() ?>
      </div>
    </div>
  </div>
</div>
