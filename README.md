# WRIST WATCH CLUB

Two experiences, one repo, one Shopify theme:

1. **The storefront** — a nixon.com-style e-commerce theme (announcement bar,
   mega-menu header, full-bleed hero, category tiles, product carousels,
   split banners, newsletter footer). Lives in
   [`shopify/theme/`](shopify/theme/) as a complete Online Store 2.0 theme.
2. **The Eclipse** — a cinematic "3D scroll" launch page for the Eclipse
   tourbillon chronograph. Scrolling scrubs three Seedance 2.0 clips
   (generated on Higgsfield from a single hero image) as canvas frame
   sequences. Ships inside the same theme on its own full-bleed layout.

## Put it on Shopify

See **[SHOPIFY.md](SHOPIFY.md)** — short version: run
`tools/package_theme.sh` and upload `dist/wrist-watch-club-theme.zip` under
**Online Store → Themes → Add theme → Upload zip file**.

## Preview locally (no Shopify needed)

```bash
python3 -m http.server 4173
# storefront mock:   http://localhost:4173/preview-home.html
# eclipse film:      http://localhost:4173/site/
```

Everything is dependency-free at runtime (Lenis, GSAP and ScrollTrigger are
vendored in `site/js/vendor/`).

## Structure

- `shopify/theme/` — the full Shopify theme (storefront + Eclipse sections)
- `site/` — the standalone Eclipse website (also serves the frame sequences
  to Shopify via GitHub Pages from the `gh-pages` branch)
  - `assets/frames/{orbit,macro,assembly}/` — JPEG scrub sequences
  - `js/main.js` — Lenis + ScrollTrigger scroll engine
- `media/` — raw generated assets (hero image + three 1080p clips)
- `dist/` — packaged theme zip, ready to upload
- `tools/package_theme.sh` — rebuilds the theme zip
- `tools/extract_frames.sh` — regenerates the frame sequences with ffmpeg
- `preview-home.html` — static mock of the storefront homepage

## Eclipse scroll choreography

1. **Hero** — brand tracks in, then scroll rotates the watch 360°; the
   Eclipse title crossfades mid-orbit.
2. **Crafted in Darkness** — pinned story reveals.
3. **The Surface** — macro fly-through scrub with captions.
4. **The Assembly** — exploded movement converges; 42 mm / 72 h / 217
   component callouts.
5. **Edition of 88 — $48,000**, then the private waitlist.
