# Paste this into Claude Code to start

I'm a beginner building my own e-commerce site and I have a complete design handoff package in this folder. Please read README.md first — it has all the business rules, design tokens, and screen descriptions. The designs/ folder has high-fidelity HTML mockups of every page (open them in a browser), and emails/ has the two decision-email templates.

Build me the Wrist Watch Club WordPress site:

1. Set up WordPress + WooCommerce locally first so I can see it working, then we'll deploy to my hosting.
2. Follow the target stack in the README exactly (WooPayments, WooCommerce Subscriptions, a memberships plugin, AliNext).
3. Recreate each design as WordPress pages/templates matching the mockups pixel-perfectly (fonts are free Google Fonts: Marcellus, EB Garamond, Space Grotesk).
4. Implement the business rules in the README exactly — especially:
   - $150 one-time membership requiring a $100+ qualifying watch in the first order
   - Members-only 50% down / 4-month / 0% financing
   - The store-credit application must POST sensitive data to an external underwriting endpoint and NEVER store SSN/DOB in WordPress; build the webhook receiver that saves only approved/declined + limit
5. Explain each step as you go — I'm learning. Tell me when you need credentials, purchases, or decisions from me.

Known placeholders (don't block on these): legal copy and Schumer Box values are pending my lawyer; product images are placeholders until AliExpress import; the denial email is sent by the external service, not WordPress.
