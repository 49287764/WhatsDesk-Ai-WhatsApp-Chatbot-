/* ============================================================
   WhatsDesk — Admin panel shared JS
   Toasts, copy-to-clipboard, password visibility, reveal on
   scroll, animated stat counters, sidebar helpers.
   All animations respect prefers-reduced-motion.
   ============================================================ */
(function () {
  'use strict';

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Toasts ---------- */
  function ensureWrap() {
    var wrap = document.getElementById('bizToastWrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.id = 'bizToastWrap';
      wrap.className = 'toast-wrap';
      document.body.appendChild(wrap);
    }
    return wrap;
  }

  window.bizToast = function (message, type) {
    if (!message) return;
    type = type || 'info';
    var icons = { ok: 'bi-check-circle-fill', err: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
    var el = document.createElement('div');
    el.className = 'biz-toast ' + type;
    el.innerHTML = '<i class="bi ' + (icons[type] || icons.info) + '"></i><div>' + message + '</div>';
    ensureWrap().appendChild(el);
    setTimeout(function () {
      el.classList.add('out');
      setTimeout(function () { el.remove(); }, 320);
    }, 4200);
  };

  /* Flash message from a data attribute on <body> (set by header.php) */
  var body = document.body;
  if (body && body.dataset.flash && body.dataset.flashType) {
    setTimeout(function () {
      window.bizToast(body.dataset.flash, body.dataset.flashType);
    }, 450);
    // JS is working — hide the no-JS fallback alert (it duplicates the toast)
    var fb = document.getElementById('flashFallback');
    if (fb) fb.remove();
  }

  /* ---------- Copy to clipboard ---------- */
  function copyText(text, btn) {
    function done() {
      if (!btn) return;
      var old = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied!';
      btn.classList.add('btn-success');
      btn.classList.remove('btn-outline-secondary');
      setTimeout(function () {
        btn.innerHTML = old;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
      }, 1600);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () {
        fallbackCopy(text, done);
      });
    } else {
      fallbackCopy(text, done);
    }
  }
  function fallbackCopy(text, done) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    ta.setSelectionRange(0, 99999);
    try { document.execCommand('copy'); } catch (e) {}
    ta.remove();
    done();
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    var target = btn.getAttribute('data-copy');
    var text = target === 'self' ? btn.textContent.trim() : (btn.dataset.value || '');
    if (!text) {
      var src = document.querySelector(target);
      if (src) text = src.value || src.textContent.trim();
    }
    if (text) {
      copyText(text, btn);
      window.bizToast('Copied to clipboard', 'ok');
    }
  });

  /* ---------- Field help ("?") popovers ----------
     Bootstrap 5 requires explicit JS initialization for popovers —
     without this the help buttons on the Settings page do nothing. */
  if (window.bootstrap && window.bootstrap.Popover) {
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
      new window.bootstrap.Popover(el, {
        html: true,
        trigger: 'hover focus'
      });
    });
  }

  /* ---------- Password visibility toggle ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-pw-toggle]');
    if (!btn) return;
    var input = document.querySelector(btn.getAttribute('data-pw-toggle'));
    if (!input) return;
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    input.focus();
  });

  /* ---------- Reveal on scroll ---------- */
  var revealEls = document.querySelectorAll('[data-reveal]');
  if (revealEls.length) {
    if (reduce || !('IntersectionObserver' in window)) {
      revealEls.forEach(function (el) { el.classList.add('in'); });
    } else {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12 });
      revealEls.forEach(function (el) { io.observe(el); });
    }
  }

  /* ---------- Animated stat counters ---------- */
  if (!reduce) {
    var nums = document.querySelectorAll('.stat .num');
    nums.forEach(function (el) {
      var raw = el.textContent.trim();
      var match = raw.replace(/,/g, '').match(/^([^\d]*)([\d.]+)(.*)$/);
      if (!match) return;
      var prefix = match[1], target = parseFloat(match[2]), suffix = match[3];
      var decimals = (match[2].split('.')[1] || '').length;
      var start = null;
      function step(ts) {
        if (!start) start = ts;
        var p = Math.min((ts - start) / 700, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = prefix + (target * eased).toFixed(decimals) + suffix;
        if (p < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    });
  }

  /* ---------- Button ripple ---------- */
  if (!reduce) {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.btn');
      if (!btn) return;
      var rect = btn.getBoundingClientRect();
      var d = Math.max(rect.width, rect.height);
      var r = document.createElement('span');
      r.className = 'btn-ripple';
      r.style.width = r.style.height = d + 'px';
      r.style.left = (e.clientX - rect.left - d / 2) + 'px';
      r.style.top = (e.clientY - rect.top - d / 2) + 'px';
      btn.appendChild(r);
      setTimeout(function () { r.remove(); }, 600);
    });
  }

  /* ---------- Sidebar active-link smooth scroll ---------- */
  var sidebar = document.getElementById('sidebar');
  document.addEventListener('click', function (e) {
    if (e.target.closest('.side-link')) {
      var sb = document.getElementById('sidebar');
      if (sb && sb.classList.contains('show')) sb.classList.remove('show');
    }
  });
})();
