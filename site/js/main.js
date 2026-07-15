/* ═══════════════════════════════════════════════════════════
   AURUM & NOIR — ECLIPSE
   Scroll-scrubbed cinematic engine
   Lenis smooth scroll + GSAP ScrollTrigger + canvas sequences
   ═══════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  gsap.registerPlugin(ScrollTrigger);

  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ── Sequence manifest (generated from Seedance 2.0 clips) ── */
  var SEQ = {
    orbit:    { dir: 'assets/frames/orbit/',    count: 161, pad: 4, ext: '.jpg' },
    macro:    { dir: 'assets/frames/macro/',    count: 121, pad: 4, ext: '.jpg' },
    assembly: { dir: 'assets/frames/assembly/', count: 121, pad: 4, ext: '.jpg' }
  };

  /* ═════════════════════ Frame sequence ═════════════════════ */

  function FrameSequence(canvas, cfg) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.cfg = cfg;
    this.images = new Array(cfg.count);
    this.loaded = new Array(cfg.count);
    this.loadedCount = 0;
    this.progress = 0;
    this.started = false;
    this.onProgress = null;
    this.resize();
  }

  FrameSequence.prototype.src = function (i) {
    var n = String(i + 1);
    while (n.length < this.cfg.pad) n = '0' + n;
    return this.cfg.dir + 'frame_' + n + this.cfg.ext;
  };

  /* Two-pass load: coarse pass (every 8th frame) so scrubbing is
     usable almost immediately, then a fill pass for full smoothness. */
  FrameSequence.prototype.load = function () {
    if (this.started) return;
    this.started = true;
    var self = this;
    var order = [];
    var stride = 8, i;
    for (i = 0; i < this.cfg.count; i += stride) order.push(i);
    for (i = 0; i < this.cfg.count; i++) if (i % stride !== 0) order.push(i);

    var inFlight = 0, cursor = 0, MAX = 10;
    function pump() {
      while (inFlight < MAX && cursor < order.length) {
        (function (idx) {
          inFlight++;
          var img = new Image();
          img.decoding = 'async';
          img.onload = img.onerror = function (e) {
            inFlight--;
            if (e.type === 'load') {
              self.images[idx] = img;
              self.loaded[idx] = true;
              self.loadedCount++;
              if (self.onProgress) self.onProgress(self.loadedCount / self.cfg.count);
              /* repaint if the newly arrived frame is the one on screen */
              if (idx === self.targetIndex()) self.render();
            }
            pump();
          };
          img.src = self.src(idx);
        })(order[cursor++]);
      }
    }
    pump();
  };

  FrameSequence.prototype.targetIndex = function () {
    var i = Math.round(this.progress * (this.cfg.count - 1));
    return Math.max(0, Math.min(this.cfg.count - 1, i));
  };

  /* nearest loaded frame to the target, so partial loads still scrub */
  FrameSequence.prototype.bestFrame = function () {
    var t = this.targetIndex();
    if (this.loaded[t]) return this.images[t];
    for (var d = 1; d < this.cfg.count; d++) {
      if (t - d >= 0 && this.loaded[t - d]) return this.images[t - d];
      if (t + d < this.cfg.count && this.loaded[t + d]) return this.images[t + d];
    }
    return null;
  };

  FrameSequence.prototype.resize = function () {
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    this.canvas.width = Math.round(this.canvas.clientWidth * dpr);
    this.canvas.height = Math.round(this.canvas.clientHeight * dpr);
    this.render();
  };

  FrameSequence.prototype.setProgress = function (p) {
    this.progress = Math.max(0, Math.min(1, p));
    this.render();
  };

  FrameSequence.prototype.render = function () {
    var img = this.bestFrame();
    if (!img) return;
    var cw = this.canvas.width, ch = this.canvas.height;
    var iw = img.naturalWidth, ih = img.naturalHeight;
    if (!cw || !ch || !iw || !ih) return;
    var s = Math.max(cw / iw, ch / ih);
    var dw = iw * s, dh = ih * s;
    this.ctx.drawImage(img, (cw - dw) / 2, (ch - dh) / 2, dw, dh);
  };

  /* ═════════════════════ Smooth scroll ═════════════════════ */

  var lenis = new Lenis({
    lerp: 0.09,
    smoothWheel: true,
    syncTouch: false
  });
  window.lenis = lenis;
  lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
  gsap.ticker.lagSmoothing(0);

  document.querySelectorAll('[data-scrollto]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      lenis.scrollTo(a.getAttribute('href'), { duration: 2.2 });
    });
  });

  /* ═════════════════════ Sequences ═════════════════════ */

  var orbit    = new FrameSequence(document.getElementById('canvasOrbit'),    SEQ.orbit);
  var macro    = new FrameSequence(document.getElementById('canvasMacro'),    SEQ.macro);
  var assembly = new FrameSequence(document.getElementById('canvasAssembly'), SEQ.assembly);

  window.addEventListener('resize', function () {
    orbit.resize(); macro.resize(); assembly.resize();
  });

  /* ═════════════════════ Preloader ═════════════════════ */

  var loaderEl  = document.getElementById('loader');
  var loaderBar = document.getElementById('loaderBar');
  var loaderPct = document.getElementById('loaderPct');
  var revealed = false;

  function reveal() {
    if (revealed) return;
    revealed = true;
    loaderEl.classList.add('is-done');
    document.body.classList.remove('is-loading');
    lenis.scrollTo(0, { immediate: true });
    ScrollTrigger.refresh();
    orbit.render();
    heroIntro();
    /* stream the remaining sequences in the background */
    macro.load();
    assembly.load();
  }

  orbit.onProgress = function (p) {
    var pct = Math.round(p * 100);
    loaderBar.style.transform = 'scaleX(' + p + ')';
    loaderPct.textContent = pct < 10 ? '0' + pct : '' + pct;
    if (p >= 0.999) reveal();
  };
  orbit.load();
  /* Safety net: never trap the visitor on the loader */
  setTimeout(function () {
    if (!revealed && orbit.loadedCount > SEQ.orbit.count * 0.35) reveal();
  }, 9000);
  setTimeout(reveal, 20000);

  /* ═════════════════ Hero intro (tracking-in) ═════════════════ */

  function heroIntro() {
    var tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
    tl.fromTo('#heroBrand .overline',
        { opacity: 0 }, { opacity: 1, duration: 1.6 }, 0.15)
      .fromTo('#heroTitle [data-track]',
        { opacity: 0, letterSpacing: '0.55em', filter: 'blur(6px)' },
        { opacity: 1, letterSpacing: '0.06em', filter: 'blur(0px)', duration: 2.6, ease: 'power2.inOut' }, 0.3)
      .fromTo('#heroBrand .hero-sub',
        { opacity: 0, y: 12 }, { opacity: 0.9, y: 0, duration: 1.4 }, 1.9)
      .fromTo('#scrollCue',
        { opacity: 0 }, { opacity: 1, duration: 1.2 }, 2.4);
  }

  /* ═════════════════ Hero orbit scrub ═════════════════ */

  ScrollTrigger.create({
    trigger: '#hero',
    start: 'top top',
    end: 'bottom bottom',
    scrub: true,
    onUpdate: function (st) { orbit.setProgress(st.progress); }
  });

  /* text stages keyed to the orbit progress */
  gsap.timeline({
    scrollTrigger: { trigger: '#hero', start: 'top top', end: 'bottom bottom', scrub: true },
    defaults: { ease: 'none' }
  })
    .to('#heroBrand', { opacity: 0, y: -46, duration: 0.16 }, 0.06)
    .to('#scrollCue', { opacity: 0, duration: 0.08 }, 0.05)
    .fromTo('#heroName',
      { opacity: 0, scale: 0.96 },
      { opacity: 1, scale: 1, duration: 0.2 }, 0.34)
    .to('#heroName', { opacity: 0, y: -40, duration: 0.16 }, 0.72);

  /* ═════════════════ Story reveals (pinned) ═════════════════ */

  gsap.timeline({
    scrollTrigger: { trigger: '#story', start: 'top top', end: 'bottom bottom', scrub: true },
    defaults: { ease: 'none' }
  })
    .fromTo('#story .overline', { opacity: 0 }, { opacity: 1, duration: 0.07 }, 0.05)
    .fromTo('#story .story-line > span',
      { yPercent: 115 },
      { yPercent: 0, duration: 0.2, stagger: 0.07, ease: 'power2.out' }, 0.1)
    .fromTo('#story .story__body p',
      { opacity: 0, y: 26 },
      { opacity: 1, y: 0, duration: 0.12, stagger: 0.12 }, 0.34)
    .to('#story .story__inner', { opacity: 0, y: -60, duration: 0.15 }, 0.85);

  /* ═════════════════ Macro scrub + captions ═════════════════ */

  ScrollTrigger.create({
    trigger: '#macro',
    start: 'top top',
    end: 'bottom bottom',
    scrub: true,
    onUpdate: function (st) { macro.setProgress(st.progress); }
  });

  [ { sel: '[data-caption="0"]', inAt: 0.05, outAt: 0.30 },
    { sel: '[data-caption="1"]', inAt: 0.37, outAt: 0.62 },
    { sel: '[data-caption="2"]', inAt: 0.68, outAt: 0.93 }
  ].forEach(function (c) {
    gsap.timeline({
      scrollTrigger: { trigger: '#macro', start: 'top top', end: 'bottom bottom', scrub: true },
      defaults: { ease: 'none' }
    })
      .fromTo(c.sel, { opacity: 0, y: 34 }, { opacity: 1, y: 0, duration: 0.07 }, c.inAt)
      .to(c.sel, { opacity: 0, y: -26, duration: 0.06 }, c.outAt);
  });

  /* ═════════════ Assembly scrub + spec callouts ═════════════ */

  ScrollTrigger.create({
    trigger: '#engineering',
    start: 'top top',
    end: 'bottom bottom',
    scrub: true,
    onUpdate: function (st) { assembly.setProgress(st.progress); }
  });

  gsap.timeline({
    scrollTrigger: { trigger: '#engineering', start: 'top top', end: 'bottom bottom', scrub: true },
    defaults: { ease: 'none' }
  })
    .fromTo('.eng-header', { opacity: 0 }, { opacity: 1, duration: 0.06 }, 0.04)
    .fromTo('[data-spec="0"]', { opacity: 0, x: -40 }, { opacity: 1, x: 0, duration: 0.08 }, 0.18)
    .fromTo('[data-spec="1"]', { opacity: 0, x: 40 },  { opacity: 1, x: 0, duration: 0.08 }, 0.40)
    .fromTo('[data-spec="2"]', { opacity: 0, x: -40 }, { opacity: 1, x: 0, duration: 0.08 }, 0.62)
    .to('.eng-header, .spec', { opacity: 0, duration: 0.08 }, 0.9);

  /* ═════════════ Edition + waitlist entrances ═════════════ */

  gsap.timeline({
    scrollTrigger: { trigger: '#edition', start: 'top 62%', end: 'top 8%', scrub: true },
    defaults: { ease: 'none' }
  })
    .fromTo('.edition-el', { opacity: 0, y: 44 }, { opacity: 1, y: 0, stagger: 0.12, duration: 0.5 });

  gsap.timeline({
    scrollTrigger: { trigger: '#waitlist', start: 'top 68%', end: 'top 18%', scrub: true },
    defaults: { ease: 'none' }
  })
    .fromTo('.wl-el', { opacity: 0, y: 38 }, { opacity: 1, y: 0, stagger: 0.14, duration: 0.5 });

  /* ═════════════════════ Waitlist form ═════════════════════ */

  document.getElementById('waitlistForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var email = document.getElementById('wlEmail').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      gsap.fromTo('#waitlistForm', { x: 0 }, { x: 8, duration: 0.07, repeat: 5, yoyo: true, clearProps: 'x' });
      return;
    }
    document.getElementById('waitlist').classList.add('is-confirmed');
  });

  /* ═════════════════ Reduced motion fallback ═════════════════ */

  if (REDUCED) {
    lenis.destroy();
    gsap.ticker.lagSmoothing(500, 33);
  }
})();
