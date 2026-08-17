/* ============================================================
   WhatsDesk — public website shared JS
   Animated counters, docs scrollspy, reveal helpers.
   All animations respect prefers-reduced-motion.
   ============================================================ */
(function () {
  'use strict';

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Animated counters (.count-up) ---------- */
  function animateCount(el) {
    var raw = el.textContent.trim();
    var match = raw.replace(/,/g, '').match(/^([^\d]*)([\d.]+)(.*)$/);
    if (!match) return;
    var prefix = match[1], target = parseFloat(match[2]), suffix = match[3];
    var decimals = (match[2].split('.')[1] || '').length;
    var start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / 1100, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = prefix + (target * eased).toFixed(decimals) + suffix;
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  var counters = document.querySelectorAll('.count-up');
  if (counters.length && !reduce && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { io.observe(el); });
  }

  /* ---------- Docs sidebar scrollspy ---------- */
  var docsNav = document.querySelector('.docs-nav');
  if (docsNav) {
    var links = Array.prototype.slice.call(docsNav.querySelectorAll('a'));
    var sections = links.map(function (a) {
      var id = a.getAttribute('href').replace('#', '');
      return document.getElementById(id);
    }).filter(Boolean);

    function onScroll() {
      var pos = (window.scrollY || document.documentElement.scrollTop) + 110;
      var current = '';
      sections.forEach(function (sec) {
        if (sec.offsetTop <= pos) current = sec.id;
      });
      links.forEach(function (a) {
        a.classList.toggle('active', a.getAttribute('href') === '#' + current);
      });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------- WhatsApp float: hide while scrolling down ---------- */
  var waFloat = document.querySelector('.wa-float');
  if (waFloat) {
    var lastY = window.scrollY || 0;
    window.addEventListener('scroll', function () {
      var y = window.scrollY || 0;
      if (y > lastY && y > 240) waFloat.classList.add('hide');
      else waFloat.classList.remove('hide');
      lastY = y;
    }, { passive: true });
  }

  /* ---------- Smooth-scroll anchor links (fallback for old browsers) ---------- */
  if (!reduce && !('scrollBehavior' in document.documentElement.style)) {
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href^="#"]');
      if (!a) return;
      var id = a.getAttribute('href').slice(1);
      var el = id ? document.getElementById(id) : null;
      if (el) {
        e.preventDefault();
        el.scrollIntoView({ behavior: 'smooth' });
      }
    });
  }
})();
