/* WRIST WATCH CLUB — Maison homepage
   Header state, mobile drawer, scroll reveals, newsletter. Dependency-free. */

(function () {
  'use strict';

  /* ── Header: solid after leaving the hero top ── */
  const hd = document.getElementById('hd');
  const onScroll = () => hd.classList.toggle('is-solid', window.scrollY > 40);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ── Mobile drawer ── */
  const burger = document.getElementById('burger');
  const drawer = document.getElementById('drawer');
  const setMenu = (open) => {
    document.body.classList.toggle('menu-open', open);
    burger.setAttribute('aria-expanded', String(open));
    drawer.setAttribute('aria-hidden', String(!open));
  };
  burger.addEventListener('click', () =>
    setMenu(!document.body.classList.contains('menu-open'))
  );
  drawer.querySelectorAll('a').forEach((a) =>
    a.addEventListener('click', () => setMenu(false))
  );
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setMenu(false);
  });

  /* ── Scroll reveals ── */
  const revealed = document.querySelectorAll('.rv');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: '0px 0px -8% 0px' }
    );
    revealed.forEach((el) => io.observe(el));
  } else {
    revealed.forEach((el) => el.classList.add('is-in'));
  }

  /* ── Newsletter ── */
  const form = document.getElementById('newsForm');
  const email = document.getElementById('newsEmail');
  const confirmMsg = document.getElementById('newsConfirm');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!email.value || !email.checkValidity()) {
      email.focus();
      return;
    }
    confirmMsg.classList.add('is-on');
    form.reset();
  });
})();
