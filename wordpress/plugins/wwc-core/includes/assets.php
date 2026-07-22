<?php
/**
 * Fonts + stylesheet + scripts, loaded sitewide on the front end.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	// Google Fonts per the handoff: Marcellus, EB Garamond, Space Grotesk.
	wp_enqueue_style(
		'wwc-fonts',
		'https://fonts.googleapis.com/css2?family=Marcellus&family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Space+Grotesk:wght@300;400;500&display=swap',
		array(),
		null
	);

	wp_enqueue_style( 'wwc', WWC_PLUGIN_URL . 'assets/css/wwc.css', array(), WWC_VERSION );

	wp_enqueue_script( 'wwc', WWC_PLUGIN_URL . 'assets/js/wwc.js', array(), WWC_VERSION, true );
	wp_localize_script( 'wwc', 'WWC', array(
		'underwritingEndpoint' => wwc_underwriting_endpoint(),
		'checkoutUrl'          => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
		'isMember'             => wwc_is_member(),
		'memberDiscount'       => wwc_member_discount(),
	) );
}, 20 );

// Preconnect for the font CDN.
add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

// Body class that scopes the design system.
add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'wwc-body';
	return $classes;
} );
