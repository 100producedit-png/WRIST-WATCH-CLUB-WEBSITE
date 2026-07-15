# WRIST WATCH CLUB — Eclipse

## New: the "Maison" storefront (Corum-inspired)

`shopify/theme/` now ships a cinematic luxury homepage inspired by the
Awwwards-featured Corum site: full-bleed hero with overlay navigation,
novelties product carousel, full-screen collection panels with parallax,
statement interstitial, editorial split, product spotlight, and a rich
newsletter footer. All of it is built as Online Store 2.0 sections
(`sections/corum-*.liquid` + `assets/corum.css` / `assets/corum.js`) —
every panel, product row and headline is editable in the Shopify theme
editor and pulls live collections/products from the connected store.

Install: upload `dist/wrist-watch-club-eclipse.zip` via
**Online Store → Themes → Add theme → Upload zip file**, then assign your
collections/products to the homepage sections in the editor.

A cinematic “3D scroll” launch site for the Eclipse tourbillon chronograph, a
fictional Swiss luxury watch by Wrist Watch Club. Scrolling scrubs three Seedance 2.0 clips
(generated on Higgsfield from a single hero image reference) as canvas frame
sequences, so the watch rotates, the dial glides by, and the movement
assembles itself under your fingertips.

## Run it

```bash
cd site
python3 -m http.server 4173
# open http://localhost:4173
```

Any static file server works — the site is dependency-free at runtime
(Lenis, GSAP and ScrollTrigger are vendored in `site/js/vendor/`).

## Structure

- `site/` — the website (open `index.html` via a local server)
  - `assets/frames/{orbit,macro,assembly}/` — JPEG scrub sequences
  - `js/main.js` — Lenis + ScrollTrigger scroll engine
- `media/` — the raw generated assets (hero image + three 1080p clips)
- `tools/extract_frames.sh` — regenerates the frame sequences with ffmpeg
- `.github/workflows/fetch-media.yml` — one-shot workflow that fetched the
  generated media onto this branch (the build sandbox could only reach GitHub)

## Scroll choreography

1. **Hero** — brand tracks in, then scroll rotates the watch 360°; the
   Eclipse title crossfades mid-orbit.
2. **Crafted in Darkness** — pinned story reveals.
3. **The Surface** — macro fly-through scrub with captions.
4. **The Assembly** — exploded movement converges; 42 mm / 72 h / 217
   component callouts.
5. **Edition of 88 — $48,000**, then the private waitlist.
