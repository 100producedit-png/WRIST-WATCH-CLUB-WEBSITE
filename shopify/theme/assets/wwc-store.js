/* WRIST WATCH CLUB — storefront behaviours (header drawer, announcement rotation, carousels) */
(function () {
  'use strict';

  function init() {
    /* ---- mobile drawer ---- */
    document.querySelectorAll('[data-drawer-open]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var drawer = document.getElementById(btn.getAttribute('data-drawer-open'));
        if (!drawer) return;
        drawer.classList.add('is-open');
        document.documentElement.style.overflow = 'hidden';
        btn.setAttribute('aria-expanded', 'true');
      });
    });
    document.querySelectorAll('[data-drawer-close]').forEach(function (el) {
      el.addEventListener('click', function () {
        var drawer = el.closest('.st-drawer');
        if (!drawer) return;
        drawer.classList.remove('is-open');
        document.documentElement.style.overflow = '';
        var opener = document.querySelector('[data-drawer-open="' + drawer.id + '"]');
        if (opener) opener.setAttribute('aria-expanded', 'false');
      });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      document.querySelectorAll('.st-drawer.is-open').forEach(function (drawer) {
        drawer.classList.remove('is-open');
        document.documentElement.style.overflow = '';
      });
    });

    /* ---- rotating announcement bar ---- */
    document.querySelectorAll('[data-announce]').forEach(function (bar) {
      var msgs = bar.querySelectorAll('.st-announce__msg');
      if (msgs.length < 2) return;
      var i = 0;
      setInterval(function () {
        msgs[i].classList.remove('is-active');
        i = (i + 1) % msgs.length;
        msgs[i].classList.add('is-active');
      }, 4000);
    });

    /* ---- carousel arrows ---- */
    document.querySelectorAll('[data-carousel]').forEach(function (root) {
      var track = root.querySelector('.st-carousel__track');
      if (!track) return;
      var step = function () {
        var item = track.querySelector('li');
        return item ? item.getBoundingClientRect().width + 16 : track.clientWidth * 0.8;
      };
      root.querySelectorAll('[data-carousel-prev]').forEach(function (b) {
        b.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
      });
      root.querySelectorAll('[data-carousel-next]').forEach(function (b) {
        b.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
