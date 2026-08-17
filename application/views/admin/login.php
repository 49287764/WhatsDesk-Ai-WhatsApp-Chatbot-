<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · WhatsDesk</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500;1,9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root{
    --ink:#16130e; --line:#e7e5e4; --text:#1c1917; --muted:#78716c;
    --accent:#f59e0b; --accent-2:#d97706;
    --font-display:"Fraunces",Georgia,serif; --font-ui:"Inter",system-ui,sans-serif;
  }
  *{ -webkit-font-smoothing:antialiased; }
  body{ font-family:var(--font-ui); background:#faf9f7; color:var(--text); }
  .split{ display:flex; min-height:100vh; }
  .panel{
    flex:1; display:none; flex-direction:column; justify-content:space-between;
    padding:3rem; color:#fff; position:relative; overflow:hidden;
    background:
      radial-gradient(1200px 500px at 85% -10%, rgba(249,115,22,.28), transparent 60%),
      radial-gradient(900px 600px at -10% 110%, rgba(245,158,11,.22), transparent 55%),
      var(--ink);
  }
  .panel::after{
    content:""; position:absolute; width:380px; height:380px; border-radius:50%;
    background:radial-gradient(circle, rgba(245,158,11,.18), transparent 65%);
    top:-90px; right:-80px; pointer-events:none; animation:orb 10s ease-in-out infinite;
  }
  @keyframes orb{ 0%,100%{ transform:translate(0,0) scale(1); } 50%{ transform:translate(-30px,26px) scale(1.1); } }
  @media (min-width:992px){ .panel{ display:flex; } }
  .panel .brand{ display:flex; align-items:center; gap:.7rem; }
  .brand-mark{ width:40px; height:40px; border-radius:12px; overflow:hidden; display:grid; place-items:center; box-shadow:0 6px 16px -8px rgba(245,158,11,.6); }
  .brand-mark img{ width:100%; height:100%; object-fit:cover; }
  .brand-name{ font-family:var(--font-display); font-weight:600; font-size:1.25rem; }
  .panel h1{ font-family:var(--font-display); font-weight:600; font-size:2.5rem; letter-spacing:-.01em; line-height:1.15; }
  .panel h1 em{ font-style:normal; color:var(--accent); }
  .panel .feat{ display:flex; align-items:center; gap:.8rem; color:#d6d3d1; font-size:.95rem; padding:.5rem 0; }
  .panel .feat i{ color:var(--accent); font-size:1.05rem; }
  .quote{ border-left:3px solid var(--accent); padding-left:1rem; color:#a8a29e; font-size:.9rem; }
  .formside{ flex:1; display:flex; align-items:center; justify-content:center; padding:2rem; }
  .login-card{ width:100%; max-width:400px; animation:cardIn .6s cubic-bezier(.22,.9,.3,1) both; }
  @keyframes cardIn{ from{ opacity:0; transform:translateY(18px); } to{ opacity:1; transform:none; } }
  .login-card h2{ font-family:var(--font-display); font-weight:600; font-size:1.7rem; }
  .form-control{ border-color:#ddd9d3; border-radius:10px; padding:.6rem .85rem; }
  .form-control:focus{ border-color:#d6b36a; box-shadow:0 0 0 .2rem rgba(245,158,11,.14); }
  .btn{ border-radius:10px; font-weight:600; }
  .btn-primary{ --bs-btn-bg:var(--accent); --bs-btn-border-color:var(--accent); --bs-btn-color:#16130e;
    --bs-btn-hover-bg:var(--accent-2); --bs-btn-hover-border-color:var(--accent-2); --bs-btn-hover-color:#fff;
    --bs-btn-active-bg:#b45309; --bs-btn-active-border-color:#b45309; --bs-btn-active-color:#fff;
    box-shadow:0 8px 20px -10px rgba(245,158,11,.55); }
  .btn-primary:hover{ transform:translateY(-1px); box-shadow:0 12px 24px -10px rgba(217,119,6,.6); }
  .feat{ animation:featIn .7s cubic-bezier(.22,.9,.3,1) both; }
  .feat:nth-child(1){ animation-delay:.15s; } .feat:nth-child(2){ animation-delay:.25s; } .feat:nth-child(3){ animation-delay:.35s; }
  @keyframes featIn{ from{ opacity:0; transform:translateX(-14px); } to{ opacity:1; transform:none; } }
  .panel .brand, .panel h1, .quote{ animation:fadeIn .7s ease both; }
  @keyframes fadeIn{ from{ opacity:0; } to{ opacity:1; } }
  @media (prefers-reduced-motion: reduce){
    .login-card, .feat, .panel .brand, .panel h1, .quote, .panel::after{ animation:none; }
  }
</style>
</head>
<body>
<div class="split">
  <aside class="panel">
    <div class="brand">
      <div class="brand-mark"><img src="<?= base_url('favicon.svg') ?>" alt="WhatsDesk logo"></div>
      <div class="brand-name">WhatsDesk</div>
    </div>

    <div>
      <h1>Your business's<br>new <em>front desk</em>.</h1>
      <div class="mt-4">
        <div class="feat"><i class="bi bi-chat-square-text"></i> Answers product, price &amp; opening-hour questions 24/7</div>
        <div class="feat"><i class="bi bi-basket"></i> Takes orders &amp; bookings over WhatsApp, straight to your team</div>
        <div class="feat"><i class="bi bi-headset"></i> Hands a customer to a human the moment they ask</div>
      </div>
    </div>

    <div class="quote">"Hi, what do you offer?" — answered instantly, while you run the business.</div>
  </aside>

  <main class="formside">
    <div class="login-card">
      <h2 class="mb-1">Welcome back</h2>
      <p class="text-secondary mb-4" style="font-size:.9rem;">Sign in to manage your WhatsApp assistant.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2" style="font-size:.875rem;"><?= html_escape($error) ?></div>
      <?php endif; ?>

      <?= form_open('admin/auth/login') ?>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Username</label>
          <input type="text" name="username" class="form-control" autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold small">Password</label>
          <div class="input-group">
            <input type="password" name="password" class="form-control" autocomplete="current-password" required>
            <button class="btn btn-outline-secondary" type="button" data-pw-toggle="[name=password]" tabindex="-1" title="Show / hide"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Sign in</button>
      <?= form_close() ?>

      <?php if (isset($is_seed) && $is_seed): ?>
        <div class="text-secondary text-center mt-4" style="font-size:.8rem;">
          First time here? <a href="<?= site_url('admin/auth/register') ?>">Create your account</a> — it takes 30 seconds.
        </div>
      <?php else: ?>
        <div class="text-secondary text-center mt-4" style="font-size:.8rem;">
          Forgot your password? Contact the account owner to reset it.
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<script src="<?= base_url('assets/js/admin.js') ?>"></script>
</body>
</html>
