<div class="page-hero">
  <div class="container-lg position-relative">
    <div class="sec-kicker mb-3">Contact</div>
    <h1 class="mb-2">Talk to a human</h1>
    <p class="sub mb-0">Questions about setup, pricing or custom needs? We reply fast.</p>
  </div>
</div>

<section style="padding-top:2rem;">
  <div class="container-lg">
    <div class="row g-4 justify-content-center">
      <div class="col-lg-6">
        <div class="card shadow-none" style="border:1px solid var(--line);border-radius:var(--radius);">
          <div class="card-body p-4">
            <?php if (isset($success) && $success): ?>
              <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i><strong>Message sent!</strong> We’ll get back to you shortly.
              </div>
            <?php endif; ?>

            <?php if (isset($errors) && $errors): ?>
              <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?><div><i class="bi bi-exclamation-circle me-2"></i><?= html_escape($err) ?></div><?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?= form_open('site/contact_send') ?>
              <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= html_escape(set_value('name')) ?>" autocomplete="name">
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Phone</label>
                  <input type="tel" name="phone" class="form-control" value="<?= html_escape(set_value('phone')) ?>" autocomplete="tel">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" value="<?= html_escape(set_value('email')) ?>" autocomplete="email">
                </div>
              </div>
              <div class="mb-4 mt-3">
                <label class="form-label">Message *</label>
                <textarea name="message" rows="5" class="form-control" required placeholder="Tell us about your business and what you need…"><?= html_escape(set_value('message')) ?></textarea>
              </div>
              <button type="submit" class="btn btn-brand w-100">Send message</button>
            <?= form_close() ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-none" style="border:1px solid var(--line);border-radius:var(--radius);">
          <div class="card-body p-4">
            <h3 class="h6 fw-bold mb-3">Other ways to reach us</h3>
            <?php if (isset($wa_link) && $wa_link): ?>
              <a class="d-flex align-items-center gap-2 mb-3" href="<?= html_escape($wa_link) ?>" target="_blank" rel="noopener">
                <span class="feat-ico" style="background:#dcfce7;color:#15803d;"><i class="bi bi-whatsapp"></i></span>
                <span><strong>WhatsApp</strong><br><span class="text-secondary small">Chat with us directly</span></span>
              </a>
            <?php endif; ?>
            <?php if (isset($business_hours) && $business_hours): ?>
              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="feat-ico" style="background:#fef3c7;color:#b45309;"><i class="bi bi-clock"></i></span>
                <span><strong>Hours</strong><br><span class="text-secondary small"><?= html_escape($business_hours) ?></span></span>
              </div>
            <?php endif; ?>
            <?php if (isset($business_address) && $business_address): ?>
              <div class="d-flex align-items-center gap-2">
                <span class="feat-ico" style="background:#e0f2fe;color:#0369a1;"><i class="bi bi-geo-alt"></i></span>
                <span><strong>Visit</strong><br><span class="text-secondary small"><?= html_escape($business_address) ?></span></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
