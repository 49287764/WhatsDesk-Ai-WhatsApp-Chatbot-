<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
<script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= html_escape(isset($page_title) && $page_title !== '' ? $page_title : 'AI WhatsApp assistant for your business') ?> · WhatsDesk</title>
<meta name="description" content="An AI assistant that answers questions, shows your products and prices, and takes orders or bookings on WhatsApp — 24/7, in seconds. Free WhatsApp service conversations, 5-minute setup.">
<meta property="og:title" content="WhatsDesk — WhatsApp AI for your business">
<meta property="og:description" content="Answer questions, take orders and bookings, and keep customers happy on WhatsApp — 24/7, automatically.">
<meta property="og:type" content="website">
<meta name="csrf-name" content="<?= $this->security->get_csrf_token_name() ?>">
<meta name="csrf-hash" content="<?= $this->security->get_csrf_hash() ?>">
<link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500;1,9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>">
</head>
<body>
<?php $nav = isset($nav_active) ? $nav_active : ''; ?>
<div class="nav-wrap">
  <nav class="navbar navbar-expand-lg container-lg">
    <a class="brand" href="<?= site_url() ?>">
      <span class="brand-mark"><img src="<?= base_url('favicon.svg') ?>" alt="WhatsDesk logo" width="24" height="24"></span>
      <span class="brand-name">WhatsDesk</span>
    </a>
    <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Toggle navigation">
      <i class="bi bi-list"></i>
    </button>
    <div class="collapse navbar-collapse" id="siteNav">
      <div class="nav-links d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-4 mt-3 mt-lg-0 ms-lg-4">
        <a class="<?= $nav === 'features' ? 'active' : '' ?>" href="<?= site_url() ?>#features">Features</a>
        <a class="<?= $nav === 'how' ? 'active' : '' ?>" href="<?= site_url() ?>#how">How it works</a>
        <a class="<?= $nav === 'demo' ? 'active' : '' ?>" href="<?= site_url() ?>#demo">Live demo</a>
        <a class="<?= $nav === 'pricing' ? 'active' : '' ?>" href="<?= site_url('site/pricing') ?>">Pricing</a>
        <a class="<?= $nav === 'docs' ? 'active' : '' ?>" href="<?= site_url('site/docs') ?>">Docs</a>
        <a class="<?= $nav === 'contact' ? 'active' : '' ?>" href="<?= site_url('site/contact') ?>">Contact</a>
      </div>
      <div class="d-flex flex-column gap-2 mt-3 d-lg-none">
        <a href="<?= site_url('admin/auth/login') ?>" class="btn btn-ghost btn-sm">Sign in</a>
        <?php if (isset($can_register) && $can_register): ?>
          <a href="<?= site_url('admin/auth/register') ?>" class="btn btn-brand btn-sm">Create account</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2 d-none d-lg-flex">
      <a href="<?= site_url('admin/auth/login') ?>" class="btn btn-ghost btn-sm">Sign in</a>
      <?php if (isset($can_register) && $can_register): ?>
        <a href="<?= site_url('admin/auth/register') ?>" class="btn btn-brand btn-sm">Create account</a>
      <?php endif; ?>
    </div>
  </nav>
</div>
