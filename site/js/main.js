/* ═══════════════════════════════════════════════════════════
   WRIST WATCH CLUB — ECLIPSE
   Scroll-scrubbed cinematic engine
   Lenis smooth scroll + GSAP ScrollTrigger + canvas sequences

   Section-tolerant: every animation binds only if its markup is
   present, so the experience can be composed from independent
   Shopify sections (hero / story / macro / engineering / edition /
   waitlist) in any combination and order.
   ═══════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  if (window.__WWC_INIT__) return; /* idempotent under section re-injection */
  window.__WWC_INIT__ = true;

  gsap.registerPlugin(ScrollTrigger);

  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $ = function (sel) { return document.querySelector(sel); };

  /* ── Sequence manifest (generated from Seedance 2.0 clips) ──
     When the page is served away from the static files (e.g. embedded in a
     Shopify theme), set window.WWC_ASSET_BASE to the absolute URL of the
     site/ folder before this script loads. Defaults to same-folder. */
  var BASE = window.WWC_ASSET_BASE || '';
  var SEQ = {
    orbit:    { dir: BASE + 'assets/frames/orbit/',    count: 161, pad: 4, ext: '.jpg' },
    macro:    { dir: BASE + 'assets/frames/macro/',    count: 121, pad: 4, ext: '.jpg' },
    assembly: { dir: BASE + 'assets/frames/assembly/', count: 121, pad: 4, ext: '.jpg' }
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
      var target = $(a.getAttribute('href'));
      if (!target) return; /* section may have been removed in the editor */
      e.preventDefault();
      lenis.scrollTo(target, { duration: 2.2 });
    });
  });

  /* ═════════════════════ Sequences (each optional) ═════════════════════ */

  var orbit    = $('#canvasOrbit')    ? new FrameSequence($('#canvasOrbit'),    SEQ.orbit)    : null;
  var macro    = $('#canvasMacro')    ? new FrameSequence($('#canvasMacro'),    SEQ.macro)    : null;
  var assembly = $('#canvasAssembly') ? new FrameSequence($('#canvasAssembly'), SEQ.assembly) : null;

  window.addEventListener('resize', function () {
    if (orbit) orbit.resize();
    if (macro) macro.resize();
    if (assembly) assembly.resize();
  });

  /* ═════════════════════ Preloader (optional) ═════════════════════ */

  var loaderEl  = $('#loader');
  var loaderBar = $('#loaderBar');
  var loaderPct = $('#loaderPct');
  var revealed = false;

  function reveal() {
    if (revealed) return;
    revealed = true;
    if (loaderEl) loaderEl.classList.add('is-done');
    document.body.classList.remove('is-loading');
    ScrollTrigger.refresh();
    if (orbit) orbit.render();
    heroIntro();
    /* stream the remaining sequences in the background */
    if (macro) macro.load();
    if (assembly) assembly.load();
  }

  if (orbit) {
    orbit.onProgress = function (p) {
      var pct = Math.round(p * 100);
      if (loaderBar) loaderBar.style.transform = 'scaleX(' + p + ')';
      if (loaderPct) loaderPct.textContent = pct < 10 ? '0' + pct : '' + pct;
      if (p >= 0.999) reveal();
    };
    orbit.load();
    /* Safety net: never trap the visitor on the loader */
    setTimeout(function () {
      if (!revealed && orbit.loadedCount > SEQ.orbit.count * 0.35) reveal();
    }, 9000);
    setTimeout(reveal, 20000);
  } else {
    reveal();
  }

  /* ═════════════════ Hero intro (tracking-in) ═════════════════ */

  function heroIntro() {
    if (!$('#heroBrand')) return;
    var tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
    tl.fromTo('#heroBrand .overline',
        { opacity: 0 }, { opacity: 1, duration: 1.6 }, 0.15)
      .fromTo('#heroTitle [data-track]',
        { opacity: 0, letterSpacing: '0.34em', filter: 'blur(6px)' },
        { opacity: 1, letterSpacing: '0.06em', filter: 'blur(0px)', duration: 2.6, ease: 'power2.inOut' }, 0.3)
      .fromTo('#heroBrand .hero-sub',
        { opacity: 0, y: 12 }, { opacity: 0.9, y: 0, duration: 1.4 }, 1.9);
    if ($('#scrollCue')) {
      tl.fromTo('#scrollCue', { opacity: 0 }, { opacity: 1, duration: 1.2 }, 2.4);
    }
  }

  /* ═════════════════ Hero orbit scrub ═════════════════ */

  if ($('#hero') && orbit) {
    ScrollTrigger.create({
      trigger: '#hero',
      start: 'top top',
      end: 'bottom bottom',
      scrub: true,
      onUpdate: function (st) { orbit.setProgress(st.progress); }
    });

    /* text stages keyed to the orbit progress */
    var heroTl = gsap.timeline({
      scrollTrigger: { trigger: '#hero', start: 'top top', end: 'bottom bottom', scrub: true },
      defaults: { ease: 'none' }
    });
    if ($('#heroBrand')) heroTl.to('#heroBrand', { opacity: 0, y: -46, duration: 0.16 }, 0.06);
    if ($('#scrollCue')) heroTl.to('#scrollCue', { opacity: 0, duration: 0.08 }, 0.05);
    if ($('#heroName')) {
      /* window chosen to sit on the dark edge-on/case-back frames
         (~55-115 of 161), so the name never fights the lit dial */
      heroTl
        .fromTo('#heroName',
          { opacity: 0, scale: 0.96 },
          { opacity: 1, scale: 1, duration: 0.12 }, 0.30)
        .to('#heroName', { opacity: 0, y: -40, duration: 0.08 }, 0.62);
    }
    /* dummy tween pins total duration to 1 so the positions above
       read as true 0-1 fractions of the hero scroll */
    heroTl.to({ _: 0 }, { _: 1, duration: 0.001 }, 0.999);
  }

  /* ═════════════════ Story reveals (pinned) ═════════════════ */

  if ($('#story')) {
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
  }

  /* ═════════════════ Macro scrub + captions ═════════════════ */

  if ($('#macro') && macro) {
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
      if (!$(c.sel)) return;
      gsap.timeline({
        scrollTrigger: { trigger: '#macro', start: 'top top', end: 'bottom bottom', scrub: true },
        defaults: { ease: 'none' }
      })
        .fromTo(c.sel, { opacity: 0, y: 34 }, { opacity: 1, y: 0, duration: 0.07 }, c.inAt)
        .to(c.sel, { opacity: 0, y: -26, duration: 0.06 }, c.outAt);
    });
  }

  /* ═════════════ Assembly scrub + spec callouts ═════════════ */

  if ($('#engineering') && assembly) {
    ScrollTrigger.create({
      trigger: '#engineering',
      start: 'top top',
      end: 'bottom bottom',
      scrub: true,
      onUpdate: function (st) { assembly.setProgress(st.progress); }
    });

    var engTl = gsap.timeline({
      scrollTrigger: { trigger: '#engineering', start: 'top top', end: 'bottom bottom', scrub: true },
      defaults: { ease: 'none' }
    });
    if ($('.eng-header')) engTl.fromTo('.eng-header', { opacity: 0 }, { opacity: 1, duration: 0.06 }, 0.04);
    if ($('[data-spec="0"]')) engTl.fromTo('[data-spec="0"]', { opacity: 0, x: -40 }, { opacity: 1, x: 0, duration: 0.08 }, 0.18);
    if ($('[data-spec="1"]')) engTl.fromTo('[data-spec="1"]', { opacity: 0, x: 40 },  { opacity: 1, x: 0, duration: 0.08 }, 0.40);
    if ($('[data-spec="2"]')) engTl.fromTo('[data-spec="2"]', { opacity: 0, x: -40 }, { opacity: 1, x: 0, duration: 0.08 }, 0.62);
    engTl.to('.eng-header, .spec', { opacity: 0, duration: 0.08 }, 0.9);
  }

  /* ═════════════ Edition + waitlist entrances ═════════════ */

  if ($('#edition')) {
    gsap.timeline({
      scrollTrigger: { trigger: '#edition', start: 'top 62%', end: 'top 8%', scrub: true },
      defaults: { ease: 'none' }
    })
      .fromTo('.edition-el', { opacity: 0, y: 44 }, { opacity: 1, y: 0, stagger: 0.12, duration: 0.5 });
  }

  if ($('#waitlist') && $('.wl-el')) {
    gsap.timeline({
      scrollTrigger: { trigger: '#waitlist', start: 'top 68%', end: 'top 18%', scrub: true },
      defaults: { ease: 'none' }
    })
      .fromTo('.wl-el', { opacity: 0, y: 38 }, { opacity: 1, y: 0, stagger: 0.14, duration: 0.5 });
  }

  /* ═════════════════════ Waitlist form (static demo only) ═════════════════════
     The Shopify theme replaces this with a real {% form 'customer' %}, which
     has no #waitlistForm id and therefore posts normally. */

  var wlForm = document.getElementById('waitlistForm');
  if (wlForm) {
    wlForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = document.getElementById('wlEmail').value.trim();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        gsap.fromTo('#waitlistForm', { x: 0 }, { x: 8, duration: 0.07, repeat: 5, yoyo: true, clearProps: 'x' });
        return;
      }
      document.getElementById('waitlist').classList.add('is-confirmed');
    });
  }

  /* ═════════════════ Reduced motion fallback ═════════════════ */

  if (REDUCED) {
    lenis.destroy();
    gsap.ticker.lagSmoothing(500, 33);
  }
})();
