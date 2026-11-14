/* The Cure Medicall Hall - public site behaviour. No framework, no storage of personal data. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Search overlay
  var overlay = $('#searchOverlay');
  function openSearch() { if (!overlay) return; overlay.classList.add('open'); document.body.style.overflow = 'hidden'; var i = $('#searchInput'); if (i) setTimeout(function () { i.focus(); }, 30); }
  function closeSearch() { if (!overlay) return; overlay.classList.remove('open'); document.body.style.overflow = ''; }
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-action]');
    if (t) {
      var a = t.getAttribute('data-action');
      if (a === 'open-search') { e.preventDefault(); openSearch(); }
      if (a === 'close-search') { e.preventDefault(); closeSearch(); }
    } else if (overlay && e.target === overlay) { closeSearch(); }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSearch(); });

  // Back to top
  var top = $('#toTop');
  if (top) {
    window.addEventListener('scroll', function () { top.classList.toggle('show', window.scrollY > 500); }, { passive: true });
    top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  // Reveal on scroll
  var els = $$('.reveal');
  if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('in'); }); }
  else {
    var io = new IntersectionObserver(function (en) { en.forEach(function (x) { if (x.isIntersecting) { x.target.classList.add('in'); io.unobserve(x.target); } }); }, { threshold: .12 });
    els.forEach(function (e) { io.observe(e); });
  }

  // Client-side hints on enquiry forms (the server validates everything again)
  $$('form[data-enquiry]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!f.checkValidity()) { e.preventDefault(); f.classList.add('was-validated'); var bad = f.querySelector(':invalid'); if (bad) bad.focus(); return; }
      var b = f.querySelector('button[type=submit]');
      if (b) setTimeout(function () { b.disabled = true; }, 0);
    });
  });
})();
