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

  // Featured product slider, using the homepage hero's custom design.
  var heroSlider = $('#heroSlider');
  if (heroSlider) {
    var heroSlides = $$('.hero-slide', heroSlider);
    var heroDots = $$('.slider-dot', heroSlider);
    var heroIndex = 0;
    var heroTimer;
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function showHeroSlide(index) {
      heroIndex = (index + heroSlides.length) % heroSlides.length;
      heroSlides.forEach(function (slide, i) { slide.classList.toggle('active', i === heroIndex); });
      heroDots.forEach(function (dot, i) {
        dot.classList.toggle('active', i === heroIndex);
        if (i === heroIndex) dot.setAttribute('aria-current', 'true');
        else dot.removeAttribute('aria-current');
      });
    }
    function startHeroTimer() {
      clearInterval(heroTimer);
      if (!reducedMotion && heroSlides.length > 1) heroTimer = setInterval(function () { showHeroSlide(heroIndex + 1); }, 5000);
    }
    var heroNext = $('.slider-next', heroSlider);
    var heroPrev = $('.slider-prev', heroSlider);
    if (heroNext) heroNext.addEventListener('click', function () { showHeroSlide(heroIndex + 1); startHeroTimer(); });
    if (heroPrev) heroPrev.addEventListener('click', function () { showHeroSlide(heroIndex - 1); startHeroTimer(); });
    heroDots.forEach(function (dot, index) { dot.addEventListener('click', function () { showHeroSlide(index); startHeroTimer(); }); });
    heroSlider.addEventListener('mouseenter', function () { clearInterval(heroTimer); });
    heroSlider.addEventListener('mouseleave', startHeroTimer);
    startHeroTimer();
  }

  // Open gallery, product, brand and category images in the shared image modal.
  var galleryModal = $('#galleryImageModal');
  if (galleryModal) {
    var galleryTriggers = $$('.gallery-modal-trigger');
    var galleryIndex = 0;
    galleryModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      if (!trigger) return;
      galleryIndex = galleryTriggers.indexOf(trigger);
      setGalleryImage(galleryIndex);
    });
    function setGalleryImage(index) {
      if (!galleryTriggers.length) return;
      galleryIndex = (index + galleryTriggers.length) % galleryTriggers.length;
      var trigger = galleryTriggers[galleryIndex];
      var image = $('#galleryImageModalPhoto', galleryModal);
      var title = $('#galleryImageModalTitle', galleryModal);
      var type = $('#galleryImageModalType', galleryModal);
      var count = $('#galleryImageModalCount', galleryModal);
      image.src = trigger.getAttribute('data-gallery-image') || '';
      image.alt = trigger.getAttribute('data-gallery-title') || '';
      title.textContent = trigger.getAttribute('data-gallery-title') || '';
      type.textContent = trigger.getAttribute('data-gallery-type') || '';
      count.textContent = (galleryIndex + 1) + ' / ' + galleryTriggers.length;
      $$('.gallery-modal-nav', galleryModal).forEach(function (button) { button.hidden = galleryTriggers.length < 2; });
    }
    $$('.gallery-modal-nav', galleryModal).forEach(function (button) {
      button.addEventListener('click', function () {
        setGalleryImage(galleryIndex + (button.getAttribute('data-gallery-nav') === 'next' ? 1 : -1));
      });
    });
    galleryModal.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowRight' && galleryTriggers.length > 1) { event.preventDefault(); setGalleryImage(galleryIndex + 1); }
      if (event.key === 'ArrowLeft' && galleryTriggers.length > 1) { event.preventDefault(); setGalleryImage(galleryIndex - 1); }
    });
    galleryModal.addEventListener('hidden.bs.modal', function () {
      var image = $('#galleryImageModalPhoto', galleryModal);
      if (image) image.removeAttribute('src');
    });
  }

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
