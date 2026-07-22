# Handoff: Wrist Watch Club — WordPress storefront

## Overview
Wrist Watch Club is a members-only watch storefront: anyone can buy at full price, but a one-time $150 membership unlocks member pricing and 0% financing (50% down, up to 4 months, no interest, no late fees). Store credit up to $6,500 is offered via an on-site application whose sensitive data goes to an EXTERNAL underwriting service — never stored in WordPress.

## About the Design Files
The files in `designs/` are **design references created in HTML** — high-fidelity prototypes showing the intended look and behavior. They are NOT production code to copy directly. The task is to **recreate these designs in WordPress** (theme templates or Elementor layouts) using WordPress/WooCommerce's established patterns and plugins. Open each `.dc.html` in a browser to see the live design.

## Fidelity
**High-fidelity.** Recreate the UI pixel-perfectly: exact colors, fonts, spacing, and copy as shown. All product data shown is sample data; real products come from AliExpress import.

## Target stack (already decided — do not re-litigate)
- WordPress + WooCommerce, page building in **Elementor**
- Payments: **WooPayments** (handles card tokenization/vaulting — PCI stays with them)
- Installments: **WooCommerce Subscriptions** (safe off-session charges for the 4 monthly payments)
- Membership gating: a WooCommerce Memberships plugin
- Product import: **AliNext** dropshipping importer (do NOT install Importify or Ali2Woo)
- Shipping: live carrier rates (note: dropship origin affects accuracy). Tax: auto-calculate by location.

## Business rules (critical — implement exactly)
1. **Membership**: two tiers, both one-time $150, no monthly fee: "Club" and "Club + Credit" (identical price; the second adds store-credit eligibility up to $6,500 subject to approval).
2. **To become a member**: create an account AND the first order must include a qualifying watch of $100+ in addition to the $150 fee.
3. **Non-members**: pay in full only.
4. **Members**: 50% down, remaining balance over up to 4 months, 0% interest, no late fees — funded EITHER from approved store credit OR the member's own vaulted card (WooPayments token, off-session).
5. **Store credit application**: optional soft-pull. The form POSTs SSN/DOB/income directly to an external secure underwriting service (`SUBMIT_ENDPOINT` — stub in the design; the owner is Equifax-credentialed). **WordPress stores ONLY approved/declined + limit.** Never store SSN, DOB, or credit-report data in WordPress.
6. **Approval happens before checkout** so credit is usable at checkout.
7. **Decision emails**: approval email (see `emails/approval-email.html`) can come from WP on webhook; the DENIAL email is an ECOA adverse-action notice and MUST be sent by the external compliant service with counsel-approved copy (`emails/denial-email.html` is the design reference).
8. A webhook receiver in WP updates account status + limit on approval.
9. All legal copy and Schumer Box (TILA) values are placeholders pending counsel.

## Screens (one file each in designs/)
- **Homepage.dc.html** — dark onyx nav; full-height cinematic hero with play button (placeholder for a real film); centered statement; two full-bleed collection tiles with bottom-gradient labels; 4-across featured grid; dark club band; centered footer.
- **MenCollection.dc.html / WomenCollection.dc.html** — editorial header (label, big serif title, intro + 3:2 image right); filter chips + sort row between hairlines; 4-across product grid: framed tile on `#f1efe9`, brand micro-label, serif name, struck non-member price + MEMBER price, outlined ADD TO BAG button; "MEMBERS ONLY" black chip on gated items.
- **JoinTheClub.dc.html** — hero, two identical-styled tier cards ($150 one-time each, benefit lists with em-dash bullets), dark "Two ways to buy" band (Pay your own way / Apply for store credit).
- **StoreCreditApplication.dc.html** — 3-step form (Applicant → Address & income → Consent & submit), TILA Cost of Credit Summary table (Schumer Box), consent checkboxes (FCRA soft-pull, ECOA notice, terms + e-signature), confirmation state. The submit handler marks where to POST to the underwriting service.
- **HowItWorks.dc.html** — 4 numbered steps, two-path cards, "$200 watch math" example table, 8-question FAQ.
- **Checkout.dc.html** — cart with membership-eligibility note, account creation, Guest/Member toggle, three payment options (pay in full / store credit 0% / 50% now + card), sticky order summary with due-today and 4-month schedule. Financing options disabled for guests.
- **AccountDashboard.dc.html** — member portal: dark store-credit card with limit meter, next-payment card with Pay early, active plan with 4-step schedule, order history table, settings tiles.
- **Legal.dc.html** — sticky side TOC + four agreements (Terms, Privacy, Membership, Store-Credit), every section tagged "COUNSEL TO REVIEW", all placeholder copy.

## Design tokens
Colors:
- Ground (light pages): `#fbfaf7`; tile/panel fill: `#f1efe9`
- Ink / dark ground ("Onyx"): `#111214` (body text `#1c1b18`)
- Porcelain accent (on dark): `#f2f0eb`; muted on dark: `#8f9296`; dark-panel border: `#33353a`
- Muted text on light: `#6d685c`; micro-labels: `#55585c`; faint: `#8b857a`
- Hairlines: `#dedbd4` / `#e4e1da`; input borders: `#c9c8c3`
- NO gold, yellow, or green anywhere (owner explicitly rejected them).

Typography (all free Google Fonts):
- Display/headings: **Marcellus** (weight 400, generous letter-spacing on brand marks: 3–4px)
- Body: **EB Garamond** (15–18px, line-height 1.6)
- Micro-labels/buttons/prices: **Space Grotesk** 9–11px, letter-spacing 1.5–4px, UPPERCASE

Components:
- Buttons: rectangular (no radius), Space Grotesk uppercase; solid `#111214` on light / `#f2f0eb` on dark, or 1px outlined ghost
- No rounded corners anywhere; no shadows; structure via hairlines and whitespace
- Nav: `#111214` bar, brand centered, MEN/WOMEN left, THE CLUB/CREDIT/BAG/ACCOUNT right; active link porcelain with underline

## Interactions & state
- Collections: category filter chips (active = solid black), price sort; member prices computed as discount % off list (default 10–20%, adjustable)
- Checkout: Guest/Member segmented toggle changes pricing and enables financing options; selecting financing shows down payment + 4-month schedule and store-credit remaining
- Credit app: 3-step wizard with progress dots; submit → confirmation state
- Hero play button is a placeholder — swap in a real brand film when available

## Assets
Product/lifestyle images in the designs are AI-generated mockups hosted on a CDN — they are placeholders. Real product images arrive via AliNext import. The hero expects a video (film) eventually.

## Pre-launch checklist
1. Replace ALL `[Placeholder]` legal copy + Schumer Box values with counsel-approved text
2. Wire `SUBMIT_ENDPOINT` to the chosen underwriting service; build the approval webhook receiver (updates member credit status + limit only)
3. Configure denial adverse-action email in the external service (counsel-approved)
4. Configure live carrier rates + auto tax
5. Import products via AliNext; verify member pricing applies
6. Test full flows: guest purchase, join + qualifying item enforcement ($100+), credit application, 50%-down checkout with both funding sources, installment charging, early payoff
