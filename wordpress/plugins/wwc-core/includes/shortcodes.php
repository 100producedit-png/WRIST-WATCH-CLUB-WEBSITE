<?php
/**
 * Dynamic components placed inside Elementor pages as shortcode widgets.
 */

defined( 'ABSPATH' ) || exit;

/** Member price for a product, per the configurable club discount. */
function wwc_member_price( $product ) {
	$base = (float) $product->get_regular_price();
	if ( ! $base ) {
		$base = (float) $product->get_price();
	}
	return round( $base * ( 1 - wwc_member_discount() / 100 ) );
}

/** Short display brand pulled from the first word of the product title. */
function wwc_card_brand( $product ) {
	$brand = $product->get_attribute( 'brand' );
	if ( ! $brand ) {
		$words = preg_split( '/\s+/', trim( wp_strip_all_tags( $product->get_name() ) ) );
		$brand = isset( $words[0] ) ? $words[0] : '';
	}
	return strtoupper( $brand );
}

/** One product card matching the handoff design. */
function wwc_render_card( $product ) {
	$id           = $product->get_id();
	$members_only = has_term( 'members-only', 'product_tag', $id );
	$price        = (float) ( $product->get_regular_price() ? $product->get_regular_price() : $product->get_price() );
	$member_price = wwc_member_price( $product );
	$img          = $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) );
	$cats         = wp_list_pluck( (array) get_the_terms( $id, 'product_cat' ), 'slug' );
	$name         = $product->get_name();
	if ( mb_strlen( $name ) > 60 ) {
		$name = mb_substr( $name, 0, 57 ) . '…';
	}

	$can_buy = $product->is_purchasable() && $product->is_in_stock();
	$add_url = esc_url( add_query_arg( 'add-to-cart', $id, wc_get_checkout_url() ) );

	ob_start();
	?>
	<a class="wwc-card" href="<?php echo esc_url( get_permalink( $id ) ); ?>"
		data-price="<?php echo esc_attr( $price ); ?>"
		data-cats="<?php echo esc_attr( implode( '|', array_filter( $cats ) ) ); ?>">
		<span class="wwc-card-media">
			<?php echo $img; // phpcs:ignore ?>
			<?php if ( $members_only ) : ?>
				<span class="wwc-card-flag">MEMBERS ONLY</span>
			<?php endif; ?>
		</span>
		<span class="wwc-card-brand"><?php echo esc_html( wwc_card_brand( $product ) ); ?></span>
		<span class="wwc-card-name"><?php echo esc_html( $name ); ?></span>
		<span class="wwc-card-price">
			<s>$<?php echo esc_html( number_format_i18n( $price, ( floor( $price ) == $price ? 0 : 2 ) ) ); ?></s>
			<span class="wwc-member-price">MEMBER $<?php echo esc_html( number_format_i18n( $member_price ) ); ?></span>
		</span>
		<?php if ( $can_buy ) : ?>
			<span class="wwc-add add_to_cart_button" data-product_id="<?php echo esc_attr( $id ); ?>"
				onclick="event.preventDefault(); window.location.href='<?php echo $add_url; // phpcs:ignore ?>';">ADD TO BAG</span>
		<?php endif; ?>
	</a>
	<?php
	return ob_get_clean();
}

/**
 * [wwc_products cat="mens-watches" count="8" columns="4" empty="…"]
 * Product grid; excludes membership products.
 */
add_shortcode( 'wwc_products', function ( $atts ) {
	$atts = shortcode_atts( array(
		'cat'   => '',
		'count' => 8,
		'empty' => 'New references are being added to the bench — check back shortly.',
	), $atts, 'wwc_products' );

	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}

	$args = array(
		'limit'   => (int) $atts['count'],
		'status'  => 'publish',
		'exclude' => array_values( wwc_membership_product_ids() ),
	);
	if ( $atts['cat'] ) {
		$args['category'] = array_map( 'trim', explode( ',', $atts['cat'] ) );
	}
	$products = wc_get_products( $args );

	$out = '<div class="wwc-grid wwc-section">';
	if ( $products ) {
		foreach ( $products as $product ) {
			$out .= wwc_render_card( $product );
		}
	} else {
		$out .= '<p class="wwc-grid-empty">' . esc_html( $atts['empty'] ) . '</p>';
	}
	$out .= '</div>';
	return $out;
} );

/**
 * [wwc_toolbar cat="mens-watches"]
 * Filter chips (child categories of the given category) + price sort.
 * Must live inside the same [data-wwc-collection] wrapper as the grid.
 */
add_shortcode( 'wwc_toolbar', function ( $atts ) {
	$atts   = shortcode_atts( array( 'cat' => '' ), $atts, 'wwc_toolbar' );
	$chips  = '<button type="button" class="wwc-chip is-active" data-cat="all">ALL</button>';
	$parent = $atts['cat'] ? get_term_by( 'slug', $atts['cat'], 'product_cat' ) : null;
	if ( $parent ) {
		$children = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent->term_id,
			'hide_empty' => false,
		) );
		foreach ( (array) $children as $child ) {
			$chips .= '<button type="button" class="wwc-chip" data-cat="' . esc_attr( $child->slug ) . '">' . esc_html( strtoupper( $child->name ) ) . '</button>';
		}
	}
	return '<section class="wwc-toolbar wwc-section">'
		. '<div class="wwc-chips">' . $chips . '</div>'
		. '<div class="wwc-sort"><span>SORT</span><select>'
		. '<option value="featured">Featured</option>'
		. '<option value="low">Price: low to high</option>'
		. '<option value="high">Price: high to low</option>'
		. '</select></div>'
		. '</section>';
} );

/** [wwc_collection_count cat="mens-watches"] — number of published products. */
add_shortcode( 'wwc_collection_count', function ( $atts ) {
	$atts = shortcode_atts( array( 'cat' => '' ), $atts, 'wwc_collection_count' );
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '0';
	}
	$products = wc_get_products( array(
		'limit'    => -1,
		'status'   => 'publish',
		'category' => array( $atts['cat'] ),
		'return'   => 'ids',
		'exclude'  => array_values( wwc_membership_product_ids() ),
	) );
	return (string) count( $products );
} );

/**
 * [wwc_tiers] — the two $150 membership tier cards.
 * CTAs add the right membership product to the bag.
 */
add_shortcode( 'wwc_tiers', function () {
	$ids        = wwc_membership_product_ids();
	$club_url   = ! empty( $ids['club'] ) ? add_query_arg( 'add-to-cart', $ids['club'], wc_get_checkout_url() ) : wc_get_checkout_url();
	$credit_url = wwc_url( 'credit' );

	$tiers = array(
		array(
			'name'     => 'CLUB',
			'tagline'  => 'Lifetime membership · no recurring charge',
			'cta'      => 'BECOME A MEMBER — $150',
			'href'     => $club_url,
			'note'     => 'Activates the moment payment clears.',
			'benefits' => array(
				'Member pricing across the entire catalog',
				'Access to exclusive, members-only references',
				'First access to new drops',
				'Pay in full or by card at checkout',
			),
		),
		array(
			'name'     => 'CLUB + CREDIT',
			'tagline'  => 'Everything in Club, plus store-credit eligibility',
			'cta'      => 'JOIN &amp; APPLY — $150',
			'href'     => $credit_url,
			'note'     => 'Approval subject to a soft credit check (no score impact).',
			'benefits' => array(
				'Everything in Club membership',
				'Apply for store credit up to $6,500 (subject to approval)',
				'0% interest financing — 50% down, up to 4 months',
				'Soft credit check only — no impact to your score',
			),
		),
	);

	$out = '<section class="wwc-tiers wwc-section">';
	foreach ( $tiers as $t ) {
		$out .= '<div class="wwc-tier">'
			. '<div class="wwc-tier-name">' . $t['name'] . '</div>'
			. '<div class="wwc-tier-pricerow"><span class="wwc-tier-price">$150</span><span class="wwc-tier-once">ONE-TIME</span></div>'
			. '<div class="wwc-tier-tag">' . $t['tagline'] . '</div>'
			. '<div class="wwc-tier-benefits">';
		foreach ( $t['benefits'] as $b ) {
			$out .= '<div class="wwc-tier-benefit"><span class="wwc-dash">—</span><span>' . $b . '</span></div>';
		}
		$out .= '</div>'
			. '<a class="wwc-btn wwc-btn--solid" href="' . esc_url( $t['href'] ) . '">' . $t['cta'] . '</a>'
			. '<p class="wwc-tier-note">' . $t['note'] . '</p>'
			. '</div>';
	}
	$out .= '</section>';
	return $out;
} );

/**
 * [wwc_credit_app] — 3-step wizard + confirmation state.
 * The form has NO action; JS posts it straight to the external underwriting
 * endpoint. SSN/DOB never reach WordPress (see assets/js/wwc.js).
 */
add_shortcode( 'wwc_credit_app', function () {
	ob_start();
	?>
	<div class="wwc-section" data-wwc-credit-app>
		<section class="wwc-wizard-steps">
			<div class="wwc-wstep is-on"><i>1</i> APPLICANT</div>
			<div class="wwc-wstep"><i>2</i> ADDRESS &amp; INCOME</div>
			<div class="wwc-wstep"><i>3</i> CONSENT &amp; SUBMIT</div>
		</section>

		<form autocomplete="on" novalidate>
			<div data-step="1">
				<div class="wwc-fields">
					<label class="wwc-field"><span>LEGAL FIRST NAME</span><input name="first_name" autocomplete="given-name"></label>
					<label class="wwc-field"><span>LEGAL LAST NAME</span><input name="last_name" autocomplete="family-name"></label>
					<label class="wwc-field"><span>EMAIL</span><input type="email" name="email" autocomplete="email"></label>
					<label class="wwc-field"><span>MOBILE PHONE</span><input type="tel" name="phone" autocomplete="tel"></label>
					<label class="wwc-field"><span>DATE OF BIRTH</span><input type="date" name="dob"></label>
				</div>
				<div class="wwc-wizard-nav wwc-wizard-nav--end">
					<button type="button" class="wwc-btn wwc-btn--solid" data-next>CONTINUE →</button>
				</div>
			</div>

			<div data-step="2" style="display:none;">
				<div class="wwc-fields">
					<label class="wwc-field wwc-field--full"><span>STREET ADDRESS</span><input name="street" autocomplete="address-line1"></label>
					<label class="wwc-field"><span>CITY</span><input name="city" autocomplete="address-level2"></label>
					<label class="wwc-field"><span>STATE</span><input name="state" autocomplete="address-level1"></label>
					<label class="wwc-field"><span>ZIP</span><input name="zip" autocomplete="postal-code"></label>
					<label class="wwc-field"><span>HOUSING STATUS</span><select name="housing"><option>Rent</option><option>Own</option><option>Other</option></select></label>
					<label class="wwc-field"><span>EMPLOYMENT STATUS</span><select name="employment"><option>Employed</option><option>Self-employed</option><option>Retired</option><option>Other</option></select></label>
					<label class="wwc-field"><span>GROSS ANNUAL INCOME (USD)</span><input type="number" name="income" min="0" step="1000"></label>
				</div>
				<div class="wwc-wizard-nav">
					<button type="button" class="wwc-btn wwc-btn--ghost" data-back>← BACK</button>
					<button type="button" class="wwc-btn wwc-btn--solid" data-next>CONTINUE →</button>
				</div>
			</div>

			<div data-step="3" style="display:none;">
				<div class="wwc-fields">
					<label class="wwc-field"><span>SOCIAL SECURITY NUMBER</span><input name="ssn" inputmode="numeric" placeholder="•••-••-••••" autocomplete="off"></label>
					<label class="wwc-field"><span>REQUESTED CREDIT LIMIT (USD)</span><input type="number" name="requested_limit" min="150" step="50" placeholder="500"></label>
				</div>
				<p class="wwc-privacy-note">🔒 Your SSN is transmitted directly to our secure underwriting service over an encrypted connection and is <strong>never stored on this website</strong>.</p>

				<div class="wwc-consents">
					<label class="wwc-consent"><input type="checkbox" name="consent_softpull"><span>I authorize Wrist Watch Club and its underwriting partner to obtain a <strong>soft-inquiry consumer credit report</strong> from Equifax for the purpose of evaluating this store-credit application. I understand a soft inquiry does not affect my credit score. <em>[FCRA permissible-purpose language — replace with counsel-approved text.]</em></span></label>
					<label class="wwc-consent"><input type="checkbox" name="consent_ecoa"><span>I understand that if my application is declined I will receive an <strong>adverse-action notice</strong> explaining the decision, and that the Equal Credit Opportunity Act prohibits discrimination in credit decisions. <em>[ECOA/Reg B disclosure — replace with counsel-approved text.]</em></span></label>
					<label class="wwc-consent"><input type="checkbox" name="consent_terms"><span>I have reviewed the Cost of Credit Summary above and agree to the store-credit terms and the electronic signature disclosure.</span></label>
				</div>

				<label class="wwc-esign"><span>TYPE YOUR FULL LEGAL NAME TO SIGN</span><input name="esignature" placeholder="Full legal name"></label>
				<p class="wwc-credit-error" role="alert"></p>

				<div class="wwc-wizard-nav">
					<button type="button" class="wwc-btn wwc-btn--ghost" data-back>← BACK</button>
					<button type="submit" class="wwc-btn wwc-btn--solid">SUBMIT APPLICATION</button>
				</div>
			</div>
		</form>
	</div>

	<section class="wwc-credit-confirm wwc-section" data-wwc-credit-confirm style="display:none;">
		<span class="wwc-eyebrow">APPLICATION RECEIVED</span>
		<h1>Your application is being reviewed.</h1>
		<p>Our underwriting service is processing your soft credit check now. You'll receive your decision by email — usually within a few minutes. If approved, your store credit limit will appear in your account.</p>
		<div class="wwc-actions">
			<a class="wwc-btn wwc-btn--solid" href="<?php echo esc_url( wwc_url( 'account' ) ); ?>">GO TO MY ACCOUNT</a>
			<a class="wwc-btn wwc-btn--ghost" href="<?php echo esc_url( wwc_url( 'men' ) ); ?>">KEEP BROWSING</a>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/** [wwc_schumer] — TILA Cost of Credit Summary table (placeholder values). */
add_shortcode( 'wwc_schumer', function () {
	$rows = array(
		array( 'APR FOR PURCHASES', '0.00% — this is a 0% interest store-credit plan.' ),
		array( 'APR FOR CASH ADVANCES', 'Not applicable. Store credit cannot be drawn as cash.' ),
		array( 'PAYING INTEREST', 'You will never be charged interest on purchases financed with store credit.' ),
		array( 'MINIMUM INTEREST CHARGE', 'None.' ),
		array( 'ANNUAL / MEMBERSHIP FEE', 'None for the credit line. (Club membership is a separate one-time $150 purchase and is not required to apply.)' ),
		array( 'PENALTY FEES — LATE PAYMENT', 'None. There are no late fees on this plan.' ),
		array( 'PENALTY FEES — RETURNED PAYMENT', 'Up to $[XX] per returned payment. [Placeholder]' ),
		array( 'REPAYMENT TERMS', '50% down at checkout; remaining balance in installments over up to 4 months.' ),
	);
	$out = '<section class="wwc-section" style="padding-bottom:40px;">'
		. '<h3 class="wwc-schumer-title">COST OF CREDIT SUMMARY</h3>'
		. '<div class="wwc-schumer">';
	foreach ( $rows as $r ) {
		$out .= '<div class="wwc-schumer-row"><div class="wwc-schumer-k">' . esc_html( $r[0] ) . '</div><div class="wwc-schumer-v">' . esc_html( $r[1] ) . '</div></div>';
	}
	$out .= '</div>'
		. '<p class="wwc-schumer-foot">Placeholder disclosure values — replace with your counsel-approved TILA terms before launch. The $150 Club membership is a separate purchase and is not a condition of receiving this credit.</p>'
		. '</section>';
	return $out;
} );
