/* WRIST WATCH CLUB — Active storefront interactions (no dependencies) */
(function () {
  'use strict';
  if (window.__WWC_ACTIVE__) return;
  window.__WWC_ACTIVE__ = true;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Announcement bar rotation */
  function initAnnouncements() {
    document.querySelectorAll('.a-annc').forEach(function (bar) {
      var msgs = bar.querySelectorAll('.a-annc__msg');
      if (!msgs.length) return;
      var i = 0;
      msgs[0].classList.add('is-on');
      if (msgs.length < 2) return;
      setInterval(function () {
        msgs[i].classList.remove('is-on');
        i = (i + 1) % msgs.length;
        msgs[i].classList.add('is-on');
      }, 3800);
    });
  }

  /* Sticky header shadow + mobile menu */
  function initHeader() {
    var head = document.querySelector('.a-head');
    if (!head) return;
    var onScroll = function () { head.classList.toggle('is-scrolled', window.scrollY > 4); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    var burger = head.querySelector('.a-head__burger');
    var mobile = head.querySelector('.a-mobilenav');
    if (burger && mobile) {
      burger.addEventListener('click', function () {
        var open = mobile.classList.toggle('is-open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }
  }

  /* Product rails: arrows + drag */
  function initRails() {
    document.querySelectorAll('[data-rail]').forEach(function (root) {
      var rail = root.querySelector('.a-rail');
      if (!rail) return;
      function step() {
        var card = rail.querySelector('.a-pcard');
        return card ? card.getBoundingClientRect().width + 20 : rail.clientWidth * 0.8;
      }
      root.querySelectorAll('[data-rail-prev]').forEach(function (b) {
        b.addEventListener('click', function () { rail.scrollBy({ left: -step() * 2, behavior: reduced ? 'auto' : 'smooth' }); });
      });
      root.querySelectorAll('[data-rail-next]').forEach(function (b) {
        b.addEventListener('click', function () { rail.scrollBy({ left: step() * 2, behavior: reduced ? 'auto' : 'smooth' }); });
      });

      var down = false, startX = 0, startLeft = 0, moved = false;
      rail.addEventListener('pointerdown', function (e) {
        if (e.pointerType !== 'mouse') return;
        down = true; moved = false; startX = e.clientX; startLeft = rail.scrollLeft;
        rail.classList.add('is-dragging');
      });
      window.addEventListener('pointermove', function (e) {
        if (!down) return;
        var dx = e.clientX - startX;
        if (Math.abs(dx) > 4) moved = true;
        rail.scrollLeft = startLeft - dx;
      });
      window.addEventListener('pointerup', function () {
        if (!down) return;
        down = false; rail.classList.remove('is-dragging');
      });
      rail.addEventListener('click', function (e) {
        if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
      }, true);
    });
  }

  function boot() { initAnnouncements(); initHeader(); initRails(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();

  document.addEventListener('shopify:section:load', function () { initRails(); initHeader(); initAnnouncements(); });
})();
