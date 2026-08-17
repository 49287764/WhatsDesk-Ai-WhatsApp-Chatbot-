<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
<script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= html_escape($page_title) ?> · WhatsDesk</title>
<link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500;1,9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<?php
  $seg2 = (string)$this->uri->segment(2);
  $admin_user = $this->session->userdata('admin_user');
  $admin_name = isset($admin_user['username']) ? $admin_user['username'] : 'admin';
  $last_run = $this->settings_model->get('last_cron_run', '');
  $worker_ok = ($last_run !== '' && (time() - strtotime($last_run)) <= 300);
  $worker_txt = $worker_ok ? 'Bot worker: live' : 'Bot worker: off (optional)';
  $setup_settings = $this->settings_model->merged();
  $setup_incomplete = ($setup_settings['wa_token'] === '' || $setup_settings['wa_phone_number_id'] === ''
    || $setup_settings['ai_api_key'] === '' || $setup_settings['cron_key'] === 'change-me'
    || $setup_settings['cron_key'] === '');
  $is_setup = (string)$this->uri->segment(2) === 'setup' && (string)$this->uri->segment(3) === '';
  $flash_msg = (string)$this->session->flashdata('msg');
  $flash_type = (string)$this->session->flashdata('msg_type');
  if ($flash_type === '') $flash_type = 'info';
?>
<body data-flash="<?= html_escape($flash_msg) ?>" data-flash-type="<?= html_escape($flash_type) ?>">
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="brand-mark"><img src="<?= base_url('favicon.svg') ?>" alt="WhatsDesk logo"></div>
    <div>
      <div class="brand-name">WhatsDesk</div>
      <div class="brand-tag">WhatsApp for business</div>
    </div>
  </div>

  <div class="nav-group">Overview</div>
  <a class="side-link <?= $seg2 === 'dashboard' ? 'active' : '' ?>" href="<?= site_url('admin/dashboard') ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
  <a class="side-link <?= $is_setup ? 'active' : '' ?>" href="<?= site_url('admin/setup') ?>">
    <i class="bi bi-rocket-takeoff"></i> Setup guide
    <?php if ($setup_incomplete): ?><span class="nav-badge">TODO</span><?php endif; ?>
  </a>
  <a class="side-link <?= $seg2 === 'orders' ? 'active' : '' ?>" href="<?= site_url('admin/orders') ?>"><i class="bi bi-receipt"></i> Orders</a>
  <a class="side-link <?= $seg2 === 'reports' ? 'active' : '' ?>" href="<?= site_url('admin/reports') ?>"><i class="bi bi-graph-up"></i> Sales report</a>
  <a class="side-link <?= $seg2 === 'chats' ? 'active' : '' ?>" href="<?= site_url('admin/chats') ?>"><i class="bi bi-chat-dots"></i> Conversations</a>
  <a class="side-link <?= $seg2 === 'messages' ? 'active' : '' ?>" href="<?= site_url('admin/messages') ?>"><i class="bi bi-envelope-open"></i> Messages</a>

  <div class="nav-group">Your business</div>
  <a class="side-link <?= $seg2 === 'business_info' ? 'active' : '' ?>" href="<?= site_url('admin/business_info') ?>">
    <i class="bi bi-journal-text"></i> Business info
    <span class="sub-label">Teach the bot about you</span>
  </a>
  <a class="side-link <?= $seg2 === 'menu' ? 'active' : '' ?>" href="<?= site_url('admin/menu') ?>">
    <i class="bi bi-bag"></i> Products &amp; services
    <span class="sub-label">What customers can order</span>
  </a>
  <a class="side-link <?= $seg2 === 'knowledge' ? 'active' : '' ?>" href="<?= site_url('admin/knowledge') ?>">
    <i class="bi bi-patch-question"></i> FAQs
    <span class="sub-label">Quick answers (optional)</span>
  </a>

  <div class="nav-group">Account</div>
  <a class="side-link <?= $seg2 === 'settings' ? 'active' : '' ?>" href="<?= site_url('admin/settings') ?>"><i class="bi bi-sliders"></i> Settings</a>
  <a class="side-link <?= $seg2 === 'accounts' ? 'active' : '' ?>" href="<?= site_url('admin/accounts') ?>"><i class="bi bi-people"></i> Accounts</a>
  <a class="side-link" href="<?= site_url('admin/auth/logout') ?>"><i class="bi bi-box-arrow-right"></i> Sign out</a>

  <div class="side-user">
    <div class="avatar"><?= html_escape(mb_substr($admin_name, 0, 1)) ?></div>
    <div class="overflow-hidden">
      <div class="text-white small fw-semibold text-truncate"><?= html_escape($admin_name) ?></div>
      <div class="text-secondary" style="font-size:.72rem;">Business owner</div>
    </div>
  </div>
</aside>
<div class="sidebar-backdrop" onclick="document.getElementById('sidebar').classList.remove('show')"></div>

<div class="main">
  <header class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-outline-secondary d-lg-none" type="button" aria-label="Open menu"
              onclick="document.getElementById('sidebar').classList.toggle('show')">
        <i class="bi bi-list"></i>
      </button>
      <div class="crumb">
        Admin panel
        <span class="crumb-sep">/</span>
        <strong><?= html_escape($page_title) ?></strong>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="<?= site_url('admin/setup') ?>" class="btn btn-sm <?= $setup_incomplete ? 'btn-outline-primary' : 'btn-outline-secondary' ?> d-none d-md-inline-flex">
        <i class="bi bi-rocket-takeoff me-1"></i> <?= $setup_incomplete ? 'Finish setup' : 'Setup guide' ?>
      </a>
      <a href="<?= site_url('site/docs') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex" title="Full step-by-step guide">
        <i class="bi bi-question-circle me-1"></i> Help
      </a>
      <div class="worker-pill" title="<?= $worker_ok ? 'Cron safety net is running — last run ' . html_escape($last_run) : 'Cron is optional: the webhook replies instantly on its own. The cron worker is just a safety net for missed messages.' ?>">
        <span class="pulse <?= $worker_ok ? '' : 'stale' ?>"></span> <?= html_escape($worker_txt) ?>
      </div>
    </div>
  </header>

  <div class="content">
    <?php if ($flash_msg): ?>
      <?php
        $flash_alert = $flash_type === 'err' ? 'alert-danger' : ($flash_type === 'ok' ? 'alert-success' : 'alert-info');
      ?>
      <div class="alert <?= $flash_alert ?> alert-dismissible fade show" id="flashFallback">
        <?= html_escape($flash_msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
