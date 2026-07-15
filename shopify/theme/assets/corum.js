/* WRIST WATCH CLUB — Maison storefront interactions (no dependencies) */
(function () {
  'use strict';
  if (window.__WWC_MAISON__) return;
  window.__WWC_MAISON__ = true;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Reveal on scroll */
  function initReveals() {
    var targets = document.querySelectorAll('[data-reveal], [data-reveal-media]');
    if (!targets.length) return;
    if (reduced || !('IntersectionObserver' in window)) {
      targets.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        var delay = parseInt(el.getAttribute('data-reveal-delay') || '0', 10);
        setTimeout(function () { el.classList.add('is-in'); }, delay);
        io.unobserve(el);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.05 });
    targets.forEach(function (el) { io.observe(el); });
  }

  /* Header: solid after leaving the hero */
  function initChrome() {
    var chrome = document.querySelector('.m-chrome');
    if (!chrome) return;
    var onScroll = function () {
      chrome.classList.toggle('is-solid', window.scrollY > window.innerHeight * 0.55);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Slow parallax drift on collection panel media */
  function initParallax() {
    var media = Array.prototype.slice.call(document.querySelectorAll('[data-parallax]'));
    if (!media.length || reduced) return;
    var ticking = false;
    function update() {
      ticking = false;
      var vh = window.innerHeight;
      media.forEach(function (el) {
        var rect = el.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) return;
        var progress = (rect.top + rect.height / 2 - vh / 2) / (vh + rect.height);
        var img = el.firstElementChild;
        if (img) img.style.transform = 'translateY(' + (progress * -9).toFixed(2) + '%)';
      });
    }
    window.addEventListener('scroll', function () {
      if (!ticking) { ticking = true; requestAnimationFrame(update); }
    }, { passive: true });
    update();
  }

  /* Carousels: arrows, drag, progress bar */
  function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach(function (root) {
      var track = root.querySelector('.m-track');
      if (!track) return;
      var bar = root.querySelector('.m-progress span');

      function step() {
        var card = track.querySelector('.m-card');
        return card ? card.getBoundingClientRect().width + 26 : track.clientWidth * 0.8;
      }
      root.querySelectorAll('[data-carousel-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: reduced ? 'auto' : 'smooth' }); });
      });
      root.querySelectorAll('[data-carousel-next]').forEach(function (btn) {
        btn.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: reduced ? 'auto' : 'smooth' }); });
      });

      if (bar) {
        var syncBar = function () {
          var max = track.scrollWidth - track.clientWidth;
          var frac = track.clientWidth / track.scrollWidth;
          bar.style.width = Math.max(frac * 100, 8) + '%';
          var x = max > 0 ? (track.scrollLeft / max) * (track.clientWidth * (1 - frac)) : 0;
          bar.style.transform = 'translateX(' + x + 'px)';
        };
        track.addEventListener('scroll', syncBar, { passive: true });
        window.addEventListener('resize', syncBar);
        syncBar();
      }

      /* pointer drag */
      var down = false, startX = 0, startLeft = 0, moved = false;
      track.addEventListener('pointerdown', function (e) {
        if (e.pointerType !== 'mouse') return;
        down = true; moved = false; startX = e.clientX; startLeft = track.scrollLeft;
        track.classList.add('is-dragging');
      });
      window.addEventListener('pointermove', function (e) {
        if (!down) return;
        var dx = e.clientX - startX;
        if (Math.abs(dx) > 4) moved = true;
        track.scrollLeft = startLeft - dx;
      });
      window.addEventListener('pointerup', function () {
        if (!down) return;
        down = false; track.classList.remove('is-dragging');
      });
      track.addEventListener('click', function (e) {
        if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
      }, true);
    });
  }

  function boot() {
    initReveals();
    initChrome();
    initParallax();
    initCarousels();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();

  /* Re-init inside the Shopify theme editor when sections reload */
  document.addEventListener('shopify:section:load', function () {
    initReveals(); initCarousels(); initParallax();
  });
})();
