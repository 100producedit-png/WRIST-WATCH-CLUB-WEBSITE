# Putting the Eclipse site on Shopify — step by step

The experience is heavy (403 scrub frames ≈ 33 MB, fonts, JS). Shopify's
theme editor is not a good home for hundreds of image files, so the setup is:

> **Markup lives in your Shopify theme. Assets stay on GitHub Pages (free),
> served from this repo.**

The site already supports this split: `js/main.js` reads
`window.WWC_ASSET_BASE` and loads all frames from there.

---

## Step 1 — Asset hosting: ALREADY DONE ✓

A `gh-pages` branch in this repo serves the assets via GitHub Pages. Nothing
to do — the asset base is:

```
https://100producedit-png.github.io/WRIST-WATCH-CLUB-WEBSITE/site/
```

Open that URL in a browser: you should see the site itself running. (If you
ever regenerate frames, copy `site/` onto the `gh-pages` branch and push.)

## Step 2 — Add the blank layout to your theme

1. Shopify admin → **Online Store → Themes → ⋯ → Edit code**.
2. Under **Layout**, click **Add a new layout** → name it `full-bleed`.
3. Delete the boilerplate and paste in the contents of
   [`shopify/layout/full-bleed.liquid`](shopify/layout/full-bleed.liquid).
4. **Save.** (This layout hides your theme's normal header/footer so the
   cinematic page runs edge to edge. Your other pages are untouched.)

## Step 3 — Add the page template

1. Still in **Edit code**, under **Templates**, click **Add a new template**.
2. Choose type **page**, format **liquid**, name it `eclipse`.
3. Paste in the contents of
   [`shopify/templates/page.eclipse.liquid`](shopify/templates/page.eclipse.liquid).
4. If your GitHub Pages URL differs from the default, update the one
   `assign wwc_base = '...'` line near the top.
5. **Save.**

## Step 4 — Create the page

1. Shopify admin → **Online Store → Pages → Add page**.
2. Title: `Eclipse` (leave the content box empty — the template is the page).
3. In the right sidebar, set **Theme template** to `eclipse`.
4. **Save**, then **View page**. Scroll. The watch should turn.

## Step 5 (optional) — Make it your homepage

Shopify's homepage always uses the `index` template, so either:
- **Redirect:** Online Store → Navigation → URL Redirects → from `/` is not
  allowed, so instead point your main menu's first item at `/pages/eclipse`; or
- **Duplicate:** create `templates/index.eclipse-test.liquid`? Not supported —
  the practical route is simply linking the nav/hero of your theme to
  `/pages/eclipse`, or pasting the same template body into `templates/index.liquid`
  on a **duplicated theme** (test first).

## Credit application page

The theme ships a financing form: `shopify/theme/sections/credit-application.liquid`
plus the template `shopify/theme/templates/page.credit-application.json`. To use it:

1. Shopify admin → **Online Store → Pages → Add page**. Title: `Credit Application`.
2. In the right sidebar set **Theme template** to `credit-application`. **Save.**
3. Applications submit through Shopify's built-in contact form, so each one
   arrives as an email to your store's sender address (Settings → Notifications).
4. Link it from your menu: Online Store → Navigation → add `/pages/credit-application`
   (label it "Financing").

The form intentionally collects **no SSN / DOB / account numbers** — run the actual
credit check through a financing partner (Affirm, Shop Pay Installments have limits
far below $48k; for luxury amounts look at partners like Klarna Financing or a
bank program) and treat this page as the pre-qualification intake.

A static preview lives at `site/credit-application.html` (open it locally or on
GitHub Pages).

### Join Now page

The same application also ships as a membership page: create a page titled
`Join Now` and assign it the `join-now` template. It reuses the credit
application form with membership copy ("Join the club"). Static preview:
`site/join-now.html`.

## Hooking the waitlist to real customers (later)

The form is presentation-only. When you want real signups, replace the
`<form class="waitlist__form">` block in the template with a Shopify customer
form (`{% form 'customer' %}` with `contact[tags] = waitlist`) — happy to wire
that up on request.

---

# How to write / edit the HTML (a working primer)

HTML is nested boxes. Every visible thing on the page is an *element*:
an opening tag, content, a closing tag:

```html
<p class="overline">PRIVATE WAITLIST</p>
└┬┘ └───────┬─────┘└──────┬───────┘└─┬┘
 tag    attribute      content    closing tag
```

- The **tag** says what it is (`h1` heading, `p` paragraph, `section` a page
  band, `canvas` a drawing surface, `a` a link, `form`/`input`/`button` forms).
- **Attributes** add settings. `class` is the important one here — it's how
  the CSS file (`site/css/style.css`) finds elements to style, and how the
  JS (`site/js/main.js`) finds elements to animate. `id` must be unique and
  is used for anchors (`#waitlist`) and scripts.
- Elements nest, and the closing tags must un-nest in the same order.

Rules of thumb for THIS site:

1. **To change copy, edit text between tags — never the tags or classes.**
   Example: to change the price, find `<p class="edition__price edition-el">$48,000</p>`
   and edit only `$48,000`. The classes `edition__price` (styling) and
   `edition-el` (scroll reveal) must stay or the styling/animation breaks.
2. **Special characters are escaped:** `&` is written `&amp;`, and `&nbsp;`
   is a non-breaking space (keeps "in Darkness." on one line).
3. **`<br/>` forces a line break** inside a caption — move it to control
   where lines split.
4. **Adding a new caption or spec:** copy an existing block (e.g. a whole
   `<div class="macro-caption" data-caption="1">…</div>`), bump the
   `data-caption` number, then register its scroll window in `js/main.js`
   (the array of `{ sel, inAt, outAt }` entries — values are 0→1 scroll
   progress within that section).
5. **Two copies of the markup exist** — `site/index.html` (localhost) and
   `shopify/templates/page.eclipse.liquid` (Shopify). They are line-for-line
   the same body; when you edit one, make the same edit in the other.
6. After editing, test locally: `cd site && python3 -m http.server 4173`,
   open `http://localhost:4173`, hard-refresh (Ctrl/Cmd-Shift-R).
