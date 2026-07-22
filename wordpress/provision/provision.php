<?php
/**
 * One-shot provisioning for the Wrist Watch Club storefront.
 * Run once on the live site (included via Novamira). Idempotent.
 *
 * Creates: product categories, membership products, placeholder retail
 * prices, and all Elementor pages from the design handoff.
 */

defined( 'ABSPATH' ) || exit;

function wwc_provision() {
	$log = array();

	// ------------------------------------------------------------------
	// 0. Sideload the handoff's placeholder imagery into the media library.
	// ------------------------------------------------------------------
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$cdn = 'https://d8j0ntlcm91z4.cloudfront.net/user_3FNvaPQ5fqKnghvkSrSOCzKDybZ/';
	$img_sources = array(
		'hero'     => $cdn . 'hf_20260722_023216_8d1ed47c-808f-4fd0-8d6a-4a0ea0103773.png',
		'men_tile' => $cdn . 'hf_20260722_023216_4517a8c6-5660-4f6f-a3ba-f7a7bc2dde2c.png',
		'wom_tile' => $cdn . 'hf_20260722_023217_b7cd671b-d1ee-41f6-8774-443d322ae21a.png',
		'wom_ed'   => $cdn . 'hf_20260722_023217_9a073b9e-d521-4588-aac8-8021356213d1.png',
	);
	$img = array();
	$stored = get_option( 'wwc_provision_images', array() );
	foreach ( $img_sources as $key => $url ) {
		if ( ! empty( $stored[ $key ] ) && wp_get_attachment_url( $stored[ $key ] ) ) {
			$img[ $key ] = wp_get_attachment_url( $stored[ $key ] );
			continue;
		}
		$id = media_sideload_image( $url, 0, 'Wrist Watch Club placeholder — ' . $key, 'id' );
		if ( ! is_wp_error( $id ) ) {
			$stored[ $key ] = $id;
			$img[ $key ]    = wp_get_attachment_url( $id );
			$log[] = "image $key sideloaded #$id";
		} else {
			$img[ $key ] = $url; // fall back to the CDN placeholder
			$log[] = "image $key fallback CDN (" . $id->get_error_message() . ')';
		}
	}
	update_option( 'wwc_provision_images', $stored );
	$img['men_ed'] = $img['hero']; // design reuses the hero shot for the men's editorial

	// ------------------------------------------------------------------
	// 1. Product categories.
	// ------------------------------------------------------------------
	$ensure_term = function ( $name, $slug, $parent = 0 ) use ( &$log ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term ) {
			return (int) $term->term_id;
		}
		$res = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent ) );
		if ( is_wp_error( $res ) ) {
			$log[] = "term $slug ERROR " . $res->get_error_message();
			return 0;
		}
		$log[] = "term $slug created";
		return (int) $res['term_id'];
	};

	$men   = $ensure_term( "Men's Watches", 'mens-watches' );
	$women = $ensure_term( "Women's Watches", 'womens-watches' );
	$men_children = array(
		'mens-divers'        => array( 'Divers', 'diver' ),
		'mens-chronographs'  => array( 'Chronographs', 'chrono' ),
		'mens-field'         => array( 'Field', 'sport|military|field' ),
		'mens-dress'         => array( 'Dress', '' ),
	);
	foreach ( $men_children as $slug => $def ) {
		$ensure_term( $def[0], $slug, $men );
	}
	$ensure_term( 'Dress', 'womens-dress', $women );
	$ensure_term( 'Quartz', 'womens-quartz', $women );
	$ensure_term( 'Bracelet', 'womens-bracelet', $women );

	// ------------------------------------------------------------------
	// 2. Membership products (two one-time $150 tiers).
	// ------------------------------------------------------------------
	$ensure_membership = function ( $slug, $name, $desc ) use ( &$log ) {
		$existing = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $existing ) {
			return (int) $existing->ID;
		}
		$p = new WC_Product_Simple();
		$p->set_name( $name );
		$p->set_slug( $slug );
		$p->set_regular_price( '150' );
		$p->set_virtual( true );
		$p->set_sold_individually( true );
		$p->set_catalog_visibility( 'hidden' );
		$p->set_description( $desc );
		$p->set_status( 'publish' );
		$id = $p->save();
		$log[] = "membership product $slug created #$id";
		return $id;
	};
	$club_id   = $ensure_membership( 'wwc-club-membership', 'Club Membership', 'One-time $150 Club membership. Member pricing across the catalog, access to members-only references, first access to new drops. Requires a qualifying watch of $100+ in your first order. No monthly fee, ever.' );
	$credit_id = $ensure_membership( 'wwc-club-credit-membership', 'Club + Credit Membership', 'Everything in Club membership, plus store-credit eligibility up to $6,500 (subject to approval — soft credit check only). One-time $150, no monthly fee.' );
	update_option( 'wwc_membership_product_ids', array( 'club' => $club_id, 'club_credit' => $credit_id ) );

	// ------------------------------------------------------------------
	// 3. Categorise imported products + placeholder retail prices.
	//    (AliNext imports carry supplier cost; retail = ~2.5x, PLACEHOLDER
	//     until the owner sets real prices.)
	// ------------------------------------------------------------------
	foreach ( wc_get_products( array( 'limit' => -1, 'status' => 'publish' ) ) as $p ) {
		if ( in_array( $p->get_id(), array( $club_id, $credit_id ), true ) ) {
			continue;
		}
		$title = strtolower( $p->get_name() );
		$child = 'mens-dress';
		foreach ( $men_children as $slug => $def ) {
			if ( $def[1] && preg_match( '/(' . $def[1] . ')/', $title ) ) {
				$child = $slug;
				break;
			}
		}
		$term_ids = array( $men );
		$child_term = get_term_by( 'slug', $child, 'product_cat' );
		if ( $child_term ) {
			$term_ids[] = (int) $child_term->term_id;
		}
		wp_set_object_terms( $p->get_id(), $term_ids, 'product_cat' );

		if ( '' === $p->get_regular_price() && $p->get_price() ) {
			$retail = max( 29, round( (float) $p->get_price() * 2.5 ) );
			$p->set_regular_price( (string) $retail );
			$p->set_price( (string) $retail );
			$p->save();
			$log[] = 'priced #' . $p->get_id() . " -> $$retail";
		}
	}

	// ------------------------------------------------------------------
	// 4. Pages.
	// ------------------------------------------------------------------
	$el_widget = function ( $type, $content ) {
		$key = 'html' === $type ? 'html' : 'shortcode';
		return array(
			'id'         => substr( md5( wp_rand() . $content ), 0, 8 ),
			'elType'     => 'widget',
			'widgetType' => $type,
			'settings'   => array( $key => $content ),
			'elements'   => array(),
		);
	};
	$el_section = function ( $widget ) {
		return array(
			'id'       => substr( md5( wp_rand() ), 0, 8 ),
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => array(
				'content_width' => 'full',
				'flex_gap'      => array( 'column' => '0', 'row' => '0', 'unit' => 'px', 'size' => 0, 'isLinked' => true ),
				'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
			),
			'elements' => array( $widget ),
		);
	};

	$u = array(
		'men'    => home_url( '/mens-watches/' ),
		'women'  => home_url( '/womens-watches/' ),
		'club'   => home_url( '/join-the-club/' ),
		'credit' => home_url( '/store-credit/' ),
		'how'    => home_url( '/how-it-works/' ),
		'legal'  => home_url( '/legal/' ),
	);

	// ---- section markup -------------------------------------------------

	$home_sections = array(
		array( 'html', '<section class="wwc-hero wwc-section"><img class="wwc-hero-img" src="' . esc_url( $img['hero'] ) . '" alt="Watch on wrist, cinematic"><div class="wwc-hero-shade"></div><div class="wwc-hero-inner"><button type="button" class="wwc-hero-play" aria-label="Play film"><span></span></button><h1>Timepieces worth keeping.</h1><p class="wwc-hero-caption">FILM — INSIDE THE MEMBERS\' BENCH, 01:24</p></div></section>' ),
		array( 'html', '<section class="wwc-statement wwc-section"><span class="wwc-eyebrow">THE COLLECTION</span><p>A curated bench of automatic and quartz watches — authenticated, and delivered to your door.</p></section>' ),
		array( 'html', '<section class="wwc-tiles wwc-section"><a class="wwc-tile" href="' . $u['men'] . '"><img src="' . esc_url( $img['men_tile'] ) . '" alt="Men\'s collection"><span class="wwc-tile-shade"></span><span class="wwc-tile-label"><span class="wwc-tile-title">Men\'s Collection</span><span class="wwc-tile-cta">DISCOVER</span></span></a><a class="wwc-tile" href="' . $u['women'] . '"><img src="' . esc_url( $img['wom_tile'] ) . '" alt="Women\'s collection"><span class="wwc-tile-shade"></span><span class="wwc-tile-label"><span class="wwc-tile-title">Women\'s Collection</span><span class="wwc-tile-cta">DISCOVER</span></span></a></section>' ),
		array( 'shortcode', '<section class="wwc-featured wwc-section"><span class="wwc-eyebrow">THIS WEEK ON THE BENCH</span>[wwc_products count="8"]</section>' ),
		array( 'html', '<section class="wwc-band wwc-section"><span class="wwc-eyebrow">THE CLUB</span><p>Members finance any watch — half down, up to four months, 0% interest, no late fees.</p><a class="wwc-btn wwc-btn--light" href="' . $u['club'] . '">BECOME A MEMBER</a></section>' ),
	);

	$men_sections = array(
		array( 'shortcode', '<section class="wwc-collection-head wwc-section"><div><span class="wwc-eyebrow">MEN\'S COLLECTION · [wwc_collection_count cat="mens-watches"] REFERENCES</span><h1>Divers, chronographs &amp; dress automatics.</h1><p>Hand-picked references built to last. Every piece ships with the club\'s authenticity check and members finance at checkout.</p></div><img src="' . esc_url( $img['men_ed'] ) . '" alt="Men\'s editorial wrist shot"></section>' ),
		array( 'shortcode', '<div data-wwc-collection>[wwc_toolbar cat="mens-watches"]<section class="wwc-gridwrap wwc-section">[wwc_products cat="mens-watches" count="24"]</section></div>' ),
	);

	$women_sections = array(
		array( 'shortcode', '<section class="wwc-collection-head wwc-section"><div><span class="wwc-eyebrow">WOMEN\'S COLLECTION · [wwc_collection_count cat="womens-watches"] REFERENCES</span><h1>Slim quartz, jeweled bezels &amp; bracelets.</h1><p>Refined references for every wrist. Every piece ships with the club\'s authenticity check and members finance at checkout.</p></div><img src="' . esc_url( $img['wom_ed'] ) . '" alt="Women\'s editorial wrist shot"></section>' ),
		array( 'shortcode', '<div data-wwc-collection>[wwc_toolbar cat="womens-watches"]<section class="wwc-gridwrap wwc-section">[wwc_products cat="womens-watches" count="24" empty="The women\'s bench is being assembled — first references arrive with the next import."]</section></div>' ),
	);

	$join_sections = array(
		array( 'html', '<section class="wwc-page-hero wwc-section"><span class="wwc-eyebrow">MEMBERSHIP · ONE-TIME · NO MONTHLY FEE</span><h1>Join the Club.</h1><p class="wwc-lede">One-time membership — no monthly fee, ever. Choose the tier that fits how you want to buy.</p><p class="wwc-fineprint">To join, create a free account and add at least one watch ($100+) to your order alongside the membership fee.</p></section>' ),
		array( 'shortcode', '[wwc_tiers]' ),
		array( 'html', '<section class="wwc-ways wwc-section"><div class="wwc-ways-inner"><span class="wwc-eyebrow">ONCE YOU\'RE IN</span><h3>Two ways to buy.</h3><p class="wwc-ways-sub">Financing is optional — members can always pay in full.</p><div class="wwc-ways-grid"><div class="wwc-way"><div class="wwc-way-kicker">OPTION A</div><div class="wwc-way-title">Pay your own way</div><p>Check out with card or pay in full. No application, nothing to approve — available to every member.</p><a class="wwc-btn wwc-btn--ghost-light wwc-btn--sm" href="' . $u['men'] . '">SHOP WATCHES</a></div><div class="wwc-way wwc-way--hi"><div class="wwc-way-kicker">OPTION B</div><div class="wwc-way-title">Apply for store credit</div><p>0% interest financing — 50% down, up to four months to pay. A soft credit check with no impact to your score.</p><a class="wwc-btn wwc-btn--light wwc-btn--sm" href="' . $u['credit'] . '">START APPLICATION</a></div></div></div></section>' ),
	);

	$how_sections = array(
		array( 'html', '<div class="wwc-wrap wwc-wrap--narrow wwc-section"><section style="padding:80px 0 56px 0;"><span class="wwc-eyebrow" style="display:block;margin-bottom:20px;">0% INTEREST · NO LATE FEES</span><h1 style="font-family:var(--wwc-display);font-size:56px;font-weight:400;line-height:1.06;margin:0 0 18px 0;">How it works.</h1><p style="font-size:18px;line-height:1.6;color:var(--wwc-muted);max-width:56ch;">Anyone can buy. Members pay less — and can split any watch into payments.</p></section><section class="wwc-steps"><div class="wwc-step"><div class="wwc-step-n">1</div><h4>Join the Club</h4><p>Create an account and add the one-time $150 membership with a qualifying watch ($100+). No monthly fee, ever.</p></div><div class="wwc-step"><div class="wwc-step-n">2</div><h4>Get approved</h4><p>Optionally apply for store credit — a soft check with no impact to your score. Your limit is ready before checkout.</p></div><div class="wwc-step"><div class="wwc-step-n">3</div><h4>Put 50% down</h4><p>Pick your watch at member pricing and pay half at checkout, by card.</p></div><div class="wwc-step"><div class="wwc-step-n">4</div><h4>Four months, 0%</h4><p>The balance splits over up to four monthly payments. No interest, no late fees.</p></div></section></div>' ),
		array( 'html', '<div class="wwc-wrap wwc-wrap--narrow wwc-section"><section class="wwc-paths"><div class="wwc-path"><div class="wwc-path-kicker">SHOPPING WITHOUT MEMBERSHIP</div><div class="wwc-path-title">Pay in full</div><p>Browse the open catalog and check out with your card. Simple.</p><a class="wwc-btn wwc-btn--ghost wwc-btn--sm" href="' . $u['men'] . '">SHOP WATCHES</a></div><div class="wwc-path wwc-path--hi"><div class="wwc-path-kicker">SHOPPING AS A MEMBER</div><div class="wwc-path-title">50% down, up to 4 months</div><p>Member pricing on every watch, plus 0% financing — from your approved store credit or your own card on file. No interest, no late fees.</p><a class="wwc-btn wwc-btn--solid wwc-btn--sm" href="' . $u['club'] . '">JOIN THE CLUB</a></div></section></div>' ),
		array( 'html', '<div class="wwc-wrap wwc-wrap--narrow wwc-section"><section class="wwc-math"><div><h3>The math on a $200 watch.</h3><p class="wwc-math-copy">Members put half down at checkout and clear the rest over four months — the price never grows.</p></div><div class="wwc-math-table"><div class="wwc-math-row"><span>Member price (10% off $200)</span><span>$180.00</span></div><div class="wwc-math-row"><span>Down payment today (50%)</span><span>$90.00</span></div><div class="wwc-math-row"><span>Month 1–4 payments</span><span>$22.50 / MO</span></div><div class="wwc-math-row"><span>Interest &amp; fees</span><span>$0.00</span></div><div class="wwc-math-row"><span>Total paid</span><span>$180.00</span></div></div></section></div>' ),
		array( 'html', '<div class="wwc-wrap wwc-wrap--narrow wwc-section"><section class="wwc-faq"><h3>Common questions.</h3><div class="wwc-faq-item"><h4>Does applying for store credit hurt my credit score?</h4><p>No. We use a soft inquiry, which never affects your score. You will see the check on your own report, but lenders don’t.</p></div><div class="wwc-faq-item"><h4>When am I charged?</h4><p>At checkout you pay the membership fee (first order only), tax, and 50% of the watch. The remaining payments are charged automatically each month to your card on file — you’ll get a reminder email before each one.</p></div><div class="wwc-faq-item"><h4>How long does shipping take?</h4><p>Most watches ship from our suppliers within 2–5 business days and arrive in 7–15 business days. You’ll get a tracking number the moment your order ships.</p></div><div class="wwc-faq-item"><h4>What if I return the watch?</h4><p>Returns are accepted within 30 days in original condition. Anything you’ve paid toward the watch is refunded and any remaining payment schedule is cancelled. [Return policy pending counsel.]</p></div><div class="wwc-faq-item"><h4>Are the watches authentic?</h4><p>Every reference is checked against our authenticity standards before it’s listed, and every shipment is covered by our guarantee.</p></div><div class="wwc-faq-item"><h4>Can I pay the balance off early?</h4><p>Anytime, with no prepayment penalty — use the Pay Early button in your account.</p></div><div class="wwc-faq-item"><h4>What happens if I miss a payment?</h4><p>There are no late fees — but your payment history, including missed payments, is reported to the credit bureaus. We’ll retry the card and email you — you can update your payment method in your account. Financing on new purchases pauses until the balance is current.</p></div><div class="wwc-faq-item"><h4>Is my payment history reported to the credit bureaus?</h4><p>Yes. We report payment history on store-credit plans to the credit bureaus — on-time payments can help build your credit, and missed payments can hurt it. [Furnishing disclosure pending counsel.]</p></div></section></div>' ),
	);

	$credit_sections = array(
		array( 'html', '<div class="wwc-wrap wwc-wrap--form wwc-section"><section class="wwc-credit-hero"><span class="wwc-eyebrow">STORE CREDIT · SOFT INQUIRY · NO SCORE IMPACT</span><h1>Store Credit Application</h1><p>Apply to finance your watches with 0% interest store credit. We run a <strong>soft credit check</strong> that does not affect your credit score. A decision is typically returned in minutes.</p></section></div>' ),
		array( 'shortcode', '<div class="wwc-wrap wwc-wrap--form">[wwc_schumer][wwc_credit_app]</div>' ),
	);

	$counsel = '<span class="wwc-counsel-tag">COUNSEL TO REVIEW</span>';
	$legal_sections = array(
		array( 'html',
			'<div class="wwc-wrap wwc-wrap--dash wwc-section"><section class="wwc-legal-head"><span class="wwc-eyebrow">LAST UPDATED JULY 22, 2026</span><h1>Legal</h1><p>The agreements that govern shopping, membership, and store credit at Wrist Watch Club. All copy below is placeholder text pending review by counsel.</p></section>'
			. '<div class="wwc-legal-grid"><nav class="wwc-legal-toc"><a href="#terms">TERMS OF SERVICE</a><a href="#privacy">PRIVACY POLICY</a><a href="#membership">MEMBERSHIP AGREEMENT</a><a href="#credit">STORE-CREDIT AGREEMENT</a></nav><div class="wwc-legal-body">'
			. '<h2 id="terms">Terms of Service</h2>' . $counsel
			. '<h3>1. Acceptance of terms</h3><p>[Placeholder] By accessing or purchasing from Wrist Watch Club, you agree to these Terms of Service. If you do not agree, do not use the site.</p>'
			. '<h3>2. Products and pricing</h3><p>[Placeholder] All prices are listed in USD and may change without notice. Member pricing applies only to active Club members. We reserve the right to correct pricing errors and cancel affected orders.</p>'
			. '<h3>3. Orders, shipping and returns</h3><p>[Placeholder] Orders ship via the carrier selected at checkout; delivery estimates are not guarantees. Return window, condition requirements, and refund method to be specified by counsel.</p>'
			. '<h3>4. Limitation of liability; disputes</h3><p>[Placeholder] Governing law, arbitration/venue, and liability limits to be supplied by counsel.</p>'
			. '<h2 id="privacy">Privacy Policy</h2>' . $counsel
			. '<h3>1. What we collect</h3><p>[Placeholder] Account details (name, email), order and payment information, and site usage data. Payment cards are processed and stored by our PCI-compliant payment gateway — card numbers never touch our servers.</p>'
			. '<h3>2. Store-credit applications</h3><p>[Placeholder] Application data, including Social Security Number and date of birth, is transmitted directly to our secure underwriting service and is not stored on this website. Credit decisions use a soft inquiry that does not affect your credit score. GLBA/FCRA privacy notices to be supplied by counsel.</p>'
			. '<h3>3. Sharing and marketing</h3><p>[Placeholder] Disclosure of sharing with carriers, payment processors, marketing platforms, and the consumer-reporting agencies we furnish to; opt-out mechanics per state law (CCPA et al.) to be supplied by counsel.</p>'
			. '<h2 id="membership">Membership Agreement</h2>' . $counsel
			. '<h3>1. Membership</h3><p>[Placeholder] Club membership is a one-time $150 purchase with no recurring fee. To join, you must create an account and include a qualifying item of $100 or more in the same first order. Membership grants member pricing, access to members-only references, and early access to drops.</p>'
			. '<h3>2. Not a credit product</h3><p>[Placeholder] Membership is not a condition of, does not guarantee, and is not payment for store credit. Store credit is governed separately by the Store-Credit Agreement and is subject to approval.</p>'
			. '<h3>3. Revocation and termination</h3><p>[Placeholder] Conduct standards, revocation rights, and refund treatment on termination to be supplied by counsel.</p>'
			. '<h2 id="credit">Store-Credit Agreement</h2>' . $counsel
			. '<h3>1. The plan</h3><p>[Placeholder] Approved members may finance purchases with 0% interest store credit: 50% down at checkout, remaining balance in scheduled installments over up to four months. No interest, no late fees. See the Cost of Credit Summary in the application.</p>'
			. '<h3>2. Approval and credit reporting</h3><p>[Placeholder] Approval is based on a soft credit inquiry with the applicant\'s authorization. Adverse-action notice procedures (ECOA/Reg B), FCRA furnishing disclosures, and dispute-handling procedures to be supplied by counsel.</p>'
			. '<h3>3. Payments and default</h3><p>[Placeholder] Autopay authorization for the card on file, returned-payment fee (if any), prepayment rights, and remedies on non-payment to be supplied by counsel.</p>'
			. '</div></div></div>'
		),
	);

	$checkout_sections = array(
		array( 'shortcode', '<div class="wwc-wrap wwc-wrap--wide wwc-section" style="padding-bottom:88px;">[woocommerce_checkout]</div>' ),
	);
	$account_sections = array(
		array( 'shortcode', '<div class="wwc-wrap wwc-wrap--dash wwc-section">[wwc_dashboard]</div><div class="wwc-wrap wwc-wrap--dash wwc-section" style="padding-bottom:80px;">[woocommerce_my_account]</div>' ),
	);

	// ---- write the pages -------------------------------------------------

	$pages = array(
		array( 'find' => 360, 'title' => 'Home', 'slug' => 'home', 'sections' => $home_sections ),
		array( 'find' => 362, 'title' => "Men's Watches", 'slug' => 'mens-watches', 'sections' => $men_sections ),
		array( 'find' => 363, 'title' => "Women's Watches", 'slug' => 'womens-watches', 'sections' => $women_sections ),
		array( 'find' => 364, 'title' => 'Join the Club', 'slug' => 'join-the-club', 'sections' => $join_sections ),
		array( 'find' => 361, 'title' => 'How It Works', 'slug' => 'how-it-works', 'sections' => $how_sections ),
		array( 'find' => 'store-credit', 'title' => 'Store Credit', 'slug' => 'store-credit', 'sections' => $credit_sections ),
		array( 'find' => 'legal', 'title' => 'Legal', 'slug' => 'legal', 'sections' => $legal_sections ),
		array( 'find' => 9, 'title' => 'Checkout', 'slug' => 'checkout', 'sections' => $checkout_sections ),
		array( 'find' => 10, 'title' => 'My account', 'slug' => 'my-account', 'sections' => $account_sections ),
	);

	foreach ( $pages as $def ) {
		$page_id = is_int( $def['find'] ) ? $def['find'] : 0;
		if ( ! $page_id ) {
			$existing = get_page_by_path( $def['slug'] );
			$page_id  = $existing ? $existing->ID : 0;
		}
		if ( ! $page_id ) {
			$page_id = wp_insert_post( array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $def['title'],
				'post_name'   => $def['slug'],
			) );
			$log[] = "page {$def['slug']} created #$page_id";
		} else {
			wp_update_post( array( 'ID' => $page_id, 'post_title' => $def['title'], 'post_name' => $def['slug'], 'post_status' => 'publish' ) );
		}

		$elements = array();
		foreach ( $def['sections'] as $s ) {
			$elements[] = $el_section( $el_widget( $s[0], $s[1] ) );
		}
		update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '4.2.0' );
		update_post_meta( $page_id, '_wp_page_template', 'elementor_canvas' );
		$log[] = "page {$def['slug']} (#$page_id) elementor data written";
	}

	// ------------------------------------------------------------------
	// 5. Housekeeping.
	// ------------------------------------------------------------------
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
		$log[] = 'elementor css cache cleared';
	}
	flush_rewrite_rules();

	return $log;
}

return wwc_provision();
