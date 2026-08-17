<!-- ============ CTA ============ -->
<section style="padding-top:0;">
  <div class="container-lg">
    <div class="cta-band p-5 text-center">
      <div class="position-relative">
        <h2 class="mb-3">Ready to never miss a customer again?</h2>
        <p class="mx-auto mb-4" style="max-width:34rem;">Connect your WhatsApp number today. If it doesn’t pay for itself in the first week, come back and we’ll help you switch it off.</p>
        <a href="<?= html_escape($cta_url) ?>" class="btn btn-brand btn-lg"><?= html_escape($cta_label) ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ============ Footer ============ -->
<footer class="pt-5 pb-4">
  <div class="container-lg">
    <div class="row g-4">
      <div class="col-md-5">
        <a class="brand mb-3" href="<?= site_url() ?>">
          <span class="brand-mark"><img src="<?= base_url('favicon.svg') ?>" alt="WhatsDesk logo" width="24" height="24"></span>
          <span class="brand-name">WhatsDesk</span>
        </a>
        <p class="text-secondary small mt-3 mb-0" style="max-width:22rem;">AI-powered WhatsApp for any business. Answer questions, take orders &amp; bookings, never miss a customer.</p>
      </div>
      <div class="col-6 col-md-2">
        <div class="fw-bold small mb-2">Product</div>
        <div class="d-flex flex-column gap-1"><a href="<?= site_url() ?>#features">Features</a><a href="<?= site_url() ?>#demo">Live demo</a><a href="<?= site_url('site/pricing') ?>">Pricing</a><a href="<?= site_url('site/docs') ?>">Docs</a></div>
      </div>
      <div class="col-6 col-md-2">
        <div class="fw-bold small mb-2">Get started</div>
        <div class="d-flex flex-column gap-1"><a href="<?= site_url('admin/auth/login') ?>">Admin panel</a><?php if (isset($can_register) && $can_register): ?><a href="<?= site_url('admin/auth/register') ?>">Create account</a><?php endif; ?><a href="<?= site_url('health') ?>">System status</a></div>
      </div>
      <div class="col-md-3">
        <div class="fw-bold small mb-2">Contact</div>
        <div class="d-flex flex-column gap-1">
          <?php if (isset($wa_link) && $wa_link): ?><a href="<?= html_escape($wa_link) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i> Message us on WhatsApp</a><?php endif; ?>
          <?php if (isset($business_hours) && $business_hours): ?><span class="text-secondary small"><?= html_escape($business_hours) ?></span><?php endif; ?>
          <?php if (isset($business_address) && $business_address): ?><span class="text-secondary small"><?= html_escape($business_address) ?></span><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="border-top mt-4 pt-3 d-flex flex-wrap justify-content-between gap-2" style="border-color:var(--line)!important;">
      <span class="text-secondary small">© <?= date('Y') ?> WhatsDesk. Built for businesses everywhere.</span>
      <span class="text-secondary small">WhatsApp is a trademark of Meta Platforms, Inc.</span>
    </div>
  </div>
</footer>

<?php if (isset($wa_link) && $wa_link): ?>
<a class="wa-float" href="<?= html_escape($wa_link) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><i class="bi bi-whatsapp"></i></a>
<?php endif; ?>
<button class="to-top" id="toTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/site.js') ?>"></script>
<script>
// Hero mock chat — replay the conversation loop
(function () {
  var stage = document.querySelector('.mock-body');
  if (!stage) return;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var msgs = stage.querySelectorAll('.msg-anim');
  if (!msgs.length) return;
  function replay() {
    msgs.forEach(function (m) { m.style.animation = 'none'; m.offsetHeight; });
    msgs.forEach(function (m) { m.style.animation = ''; });
  }
  // Replay every 9 seconds: fade out the stage, then re-run entrance
  setInterval(function () {
    stage.style.transition = 'opacity .5s ease';
    stage.style.opacity = '0';
    setTimeout(function () {
      replay();
      stage.style.opacity = '1';
    }, 520);
  }, 9000);
})();
</script>
<script>
// Back to top
(function () {
  var btn = document.getElementById('toTop');
  if (!btn) return;
  function onScroll() {
    btn.classList.toggle('show', (window.scrollY || document.documentElement.scrollTop) > 600);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  onScroll();
})();
</script>
<script>
// Scroll reveal (shared)
(function () {
  var els = document.querySelectorAll('[data-reveal]');
  if (!els.length) return;
  if (!('IntersectionObserver' in window)) {
    els.forEach(function (el) { el.classList.add('in'); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
    });
  }, { threshold: 0.12 });
  els.forEach(function (el) { io.observe(el); });
})();
</script>
</body>
</html>
