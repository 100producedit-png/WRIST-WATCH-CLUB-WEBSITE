# Wrist Watch Club — WordPress build

WordPress/WooCommerce implementation of the design handoff in `design-handoff/`,
live on **clubwristwatch.com** (WordPress.com Atomic).

## Architecture

**`plugins/wwc-core/`** — the heart of the build (deployed and active on the site):

| Piece | What it does |
| --- | --- |
| `assets/css/wwc.css` | The full design system from the handoff: colors (`#fbfaf7` ground, `#111214` onyx, `#f1efe9` panels…), Marcellus / EB Garamond / Space Grotesk type, buttons, hairlines, every page's section styles, responsive rules. |
| `includes/chrome.php` | The dark onyx nav (MEN/WOMEN · brand · THE CLUB/CREDIT/BAG/ACCOUNT) and the centered footer, painted around every Elementor Canvas page. Live bag count via cart fragments. |
| `includes/shortcodes.php` | `[wwc_products]` (grid with member pricing + MEMBERS ONLY flags), `[wwc_toolbar]` (filter chips + sort), `[wwc_tiers]`, `[wwc_schumer]`, `[wwc_credit_app]`. |
| `includes/membership.php` | Business rules: $150 one-time tiers, **$100+ qualifying watch required in the first order**, member role granted when the order is paid, storewide member discount (option, default 10%), members-only gating. |
| `includes/credit.php` | Webhook receiver `POST /wp-json/wwc/v1/credit-decision` (HMAC-signed). Stores **only** approved/declined + limit. Sends the approval email; the denial email is the external service's job (ECOA adverse action). |
| `includes/checkout.php` | Checkout heading, membership-eligibility note, member financing panel. |
| `includes/account.php` | `[wwc_dashboard]` member portal (credit card w/ limit meter, next payment, plans, order history, settings tiles). |
| `includes/admin.php` | WooCommerce → **Wrist Watch Club** settings: member discount %, underwriting endpoint, webhook secret. |
| `assets/js/wwc.js` | Collection filter/sort; the 3-step credit wizard that POSTs **directly from the browser** to the external underwriting endpoint — SSN/DOB never touch WordPress. |

**Pages** — all Elementor documents on the Canvas template (edit via *Edit with Elementor*;
each design section is one HTML or Shortcode block):

Home (#360) · Men's Watches (#362) · Women's Watches (#363) · Join the Club (#364) ·
How It Works (#361) · Store Credit (#383) · Legal (#384) · Checkout (#9) · Cart (#8) ·
My account (#10).

**`provision/provision.php`** — the one-shot script that created categories,
membership products, placeholder retail prices and wrote the Elementor data. Idempotent.

## Deploying plugin changes

The site pulls plugin files from this repo (branch `claude/new-session-vicwmd`) — push,
then re-run the fetch loop in `includes/…` via Novamira, or copy files manually to
`wp-content/plugins/wwc-core/`.

## Still pending (decisions / purchases / counsel)

1. **Installment engine** — the real 50%-down + 4-month auto-charging needs
   **WooCommerce Subscriptions** (paid, ~$279/yr) per the target stack. Until it's
   installed, members see financing info at checkout but orders are paid in full.
2. **Memberships plugin** — the custom role-based gating in `wwc-core` covers the
   rules today; WooCommerce Memberships (paid) can replace it if preferred.
3. **Underwriting endpoint** — set the real `SUBMIT_ENDPOINT` in WooCommerce →
   Wrist Watch Club; give the provider the webhook URL + secret from the same screen.
4. **Legal copy + Schumer Box** — all placeholders tagged COUNSEL TO REVIEW.
5. **Retail prices** — imported products carried supplier cost; placeholder retail
   (≈2.5× cost) was applied. Set real prices in WooCommerce → Products.
6. **Women's catalog** — empty until you import women's references via AliNext.
7. **Hero film** — the play button is a placeholder for the brand film.
