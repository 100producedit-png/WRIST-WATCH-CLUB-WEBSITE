# WRIST WATCH CLUB on Shopify

The repo now ships a **complete Shopify Online Store 2.0 theme** in
[`shopify/theme/`](shopify/theme/): a nixon.com-style storefront (announcement
bar, mega-menu header, hero, category tiles, product carousels, split banners,
newsletter footer) **plus** the cinematic Eclipse scroll-film page on its own
full-bleed layout.

## Install the theme (5 minutes)

1. Run `tools/package_theme.sh` (or grab
   [`dist/wrist-watch-club-theme.zip`](dist/wrist-watch-club-theme.zip) from
   this repo).
2. Shopify admin → **Online Store → Themes → Add theme → Upload zip file**.
3. Pick the uploaded **Wrist Watch Club** theme → **Customize** to preview,
   **Publish** when ready.

Using the Shopify CLI instead: `cd shopify/theme && shopify theme push`.

## After installing — 3 things to wire up

### 1. Navigation (powers the mega menu)
**Online Store → Navigation → Main menu.** Top-level items become the nav bar;
their sub-items become mega-menu columns; third-level items become the links
in each column. Example:

```
Shop watches
├── By style
│   ├── Analog / Digital / Automatic / Chronograph
├── By fit
│   ├── Men's / Women's / Oversized
Accessories
The Eclipse            → /pages/eclipse
Explore
```

The **Footer menu** fills the footer's link columns.

### 2. Collections (power the carousels & tiles)
The homepage ships pointing at the built-in `all` and `frontpage` (Home page)
collections, so it works immediately. For real merchandising, create
collections (e.g. *Bestsellers*, *New arrivals*, *Men's*, *Women's*) and pick
them in the theme editor on the **Featured collection** and **Category tiles**
sections. Tiles use the collection's featured image automatically — or set an
image override per tile.

### 3. The Eclipse page
**Online Store → Pages → Add page**, title `Eclipse`, and set **Theme
template** to `eclipse`. That page renders the scroll film full-bleed (no
store header/footer — it has its own chrome). The homepage hero button and
split banner already link to `/pages/eclipse`.

## What's in the theme

| Piece | File(s) |
| --- | --- |
| Store layout (header/footer groups) | `layout/theme.liquid` |
| Full-bleed layout (Eclipse) | `layout/full-bleed.liquid` |
| Header: announcements + mega menu + drawer | `sections/store-header.liquid` |
| Footer: newsletter + link columns + payment icons | `sections/store-footer.liquid` |
| Hero banner | `sections/store-hero.liquid` |
| Category tiles | `sections/store-category-tiles.liquid` |
| Product carousel | `sections/store-featured-collection.liquid` |
| Split/collab banners | `sections/store-split-banner.liquid` |
| Value props strip | `sections/store-value-props.liquid` |
| Storefront design system | `assets/wwc-store.css`, `assets/wwc-store.js` |
| Eclipse scroll film | `sections/eclipse-*.liquid`, `snippets/eclipse-assets.liquid` |
| Product / collection / cart / search / customer pages | `sections/main-*.liquid`, `templates/` |

Every section is editable in the theme editor (**Customize**): all copy,
images, links, collections and menus are settings — no code edits needed for
day-to-day changes.

## Asset hosting

The Eclipse experience streams its 400+ scrub frames from GitHub Pages
(free, already live):

```
https://100producedit-png.github.io/WRIST-WATCH-CLUB-WEBSITE/site/assets/…
```

The storefront also uses a few of those frames as **fallback imagery** so the
homepage never looks empty. Replace them any time by picking images in the
theme editor — picked images always win over fallbacks and are served from
Shopify's CDN.

## Newsletter & waitlist signups

Both the footer signup and the Eclipse waitlist use Shopify's native
`{% form 'customer' %}` — signups appear under **Customers**, tagged
`newsletter` (footer) or `waitlist` (Eclipse page). No app needed.

## Previewing the storefront without Shopify

Open `preview-home.html` through any local server from the repo root
(`python3 -m http.server 4173`) to see a static mock of the homepage using
the exact theme CSS/JS. The Eclipse film still previews via `site/`
(see README).
