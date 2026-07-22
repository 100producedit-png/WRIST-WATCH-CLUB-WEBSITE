<?php
/**
 * Site chrome: the onyx header and centered footer from the handoff.
 *
 * Pages are built on the Elementor Canvas template (a blank slate), and this
 * module paints the shared header/footer around them so every page gets the
 * exact same chrome. Also available as [wwc_header] / [wwc_footer].
 */

defined( 'ABSPATH' ) || exit;

/** Named front-end URLs used across the chrome and shortcodes. */
function wwc_url( $key ) {
	$map = array(
		'home'    => home_url( '/' ),
		'men'     => home_url( '/mens-watches/' ),
		'women'   => home_url( '/womens-watches/' ),
		'club'    => home_url( '/join-the-club/' ),
		'credit'  => home_url( '/store-credit/' ),
		'how'     => home_url( '/how-it-works/' ),
		'legal'   => home_url( '/legal/' ),
		'account' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
		'bag'     => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
	);
	return isset( $map[ $key ] ) ? $map[ $key ] : home_url( '/' );
}

/** Is the current request the page behind one of the wwc_url() keys? */
function wwc_is_current( $key ) {
	$slugs = array(
		'men'    => 'mens-watches',
		'women'  => 'womens-watches',
		'club'   => 'join-the-club',
		'credit' => 'store-credit',
		'how'    => 'how-it-works',
		'legal'  => 'legal',
	);
	if ( isset( $slugs[ $key ] ) ) {
		return is_page( $slugs[ $key ] );
	}
	if ( 'account' === $key ) {
		return function_exists( 'is_account_page' ) && is_account_page();
	}
	if ( 'bag' === $key ) {
		return function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() );
	}
	return false;
}

function wwc_nav_link( $key, $label ) {
	$active = wwc_is_current( $key ) ? ' class="is-active" aria-current="page"' : '';
	return '<a href="' . esc_url( wwc_url( $key ) ) . '"' . $active . '>' . $label . '</a>';
}

function wwc_render_header() {
	$bag = 0;
	if ( function_exists( 'WC' ) && WC()->cart ) {
		$bag = (int) WC()->cart->get_cart_contents_count();
	}
	?>
	<header class="wwc-header wwc-section">
		<nav>
			<?php echo wwc_nav_link( 'men', 'MEN' ); // phpcs:ignore ?>
			<?php echo wwc_nav_link( 'women', 'WOMEN' ); // phpcs:ignore ?>
		</nav>
		<a class="wwc-brand" href="<?php echo esc_url( wwc_url( 'home' ) ); ?>">WRIST WATCH CLUB</a>
		<nav>
			<?php echo wwc_nav_link( 'club', 'THE CLUB' ); // phpcs:ignore ?>
			<?php echo wwc_nav_link( 'credit', 'CREDIT' ); // phpcs:ignore ?>
			<a href="<?php echo esc_url( wwc_url( 'bag' ) ); ?>">BAG (<span class="wwc-bag-count"><?php echo esc_html( $bag ); ?></span>)</a>
			<?php echo wwc_nav_link( 'account', 'ACCOUNT' ); // phpcs:ignore ?>
		</nav>
	</header>
	<?php
}

function wwc_render_footer() {
	$note = '';
	if ( is_front_page() ) {
		$note = 'Pay-in-4 is a 0% interest, 4-installment plan with no late fees. Membership fees purchase pricing and access, not credit. Financing availability and terms subject to the plan agreement.';
	} elseif ( is_page( 'join-the-club' ) ) {
		$note = 'Membership is a one-time $150 purchase granting pricing and access; it is not a condition of and does not guarantee credit approval. Store credit is subject to a soft-inquiry approval. 0% interest, no late fees. <a href="' . esc_url( wwc_url( 'legal' ) ) . '">Legal</a>';
	} elseif ( is_page( 'store-credit' ) ) {
		$note = 'Store credit is offered subject to approval based on a soft credit inquiry. 0% interest, no late fees. Membership is not required to apply and does not guarantee approval. Disclosures shown are placeholders pending final counsel-approved terms. <a href="' . esc_url( wwc_url( 'legal' ) . '#credit' ) . '">Store-Credit Agreement</a>';
	}
	$line = ( is_front_page() || is_page( 'join-the-club' ) ) ? '' : ' wwc-footer--line';
	?>
	<footer class="wwc-footer wwc-section<?php echo esc_attr( $line ); ?>">
		<div class="wwc-footer-brand">WRIST WATCH CLUB</div>
		<nav>
			<a href="<?php echo esc_url( wwc_url( 'men' ) ); ?>">MEN</a>
			<a href="<?php echo esc_url( wwc_url( 'women' ) ); ?>">WOMEN</a>
			<a href="<?php echo esc_url( wwc_url( 'club' ) ); ?>">MEMBERSHIP</a>
			<a href="<?php echo esc_url( wwc_url( 'how' ) ); ?>">HOW IT WORKS</a>
			<a href="<?php echo esc_url( wwc_url( 'account' ) ); ?>">ACCOUNT</a>
			<a href="<?php echo esc_url( wwc_url( 'legal' ) ); ?>">LEGAL</a>
		</nav>
		<?php if ( $note ) : ?>
			<p class="wwc-footer-note"><?php echo wp_kses_post( $note ); ?></p>
		<?php endif; ?>
	</footer>
	<?php
}

// Paint chrome around Elementor Canvas pages (all WWC pages use Canvas).
add_action( 'elementor/page_templates/canvas/before_content', 'wwc_render_header' );
add_action( 'elementor/page_templates/canvas/after_content', 'wwc_render_footer' );

add_shortcode( 'wwc_header', function () {
	ob_start();
	wwc_render_header();
	return ob_get_clean();
} );
add_shortcode( 'wwc_footer', function () {
	ob_start();
	wwc_render_footer();
	return ob_get_clean();
} );

// Live bag count via Woo cart fragments.
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$bag = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
	$fragments['span.wwc-bag-count'] = '<span class="wwc-bag-count">' . $bag . '</span>';
	return $fragments;
} );
