<?php
/**
 * Plugin Name: Wrist Watch Club Core
 * Description: Design system, site chrome, membership + store-credit business rules for clubwristwatch.com.
 * Version: 1.0.0
 * Author: Wrist Watch Club
 * License: GPL-2.0+
 * Text Domain: wwc
 */

defined( 'ABSPATH' ) || exit;

define( 'WWC_VERSION', '1.0.0' );
define( 'WWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ---------------------------------------------------------------------------
// Options helpers
// ---------------------------------------------------------------------------

/** Member discount percent off list price (README: default 10–20, adjustable). */
function wwc_member_discount() {
	return (float) get_option( 'wwc_member_discount', 10 );
}

/** External underwriting endpoint the credit application POSTs to (browser-side). */
function wwc_underwriting_endpoint() {
	return trim( (string) get_option( 'wwc_underwriting_endpoint', '' ) );
}

/** Shared secret used to authenticate the underwriting service's webhook calls. */
function wwc_webhook_secret() {
	$secret = get_option( 'wwc_webhook_secret' );
	if ( ! $secret ) {
		$secret = wp_generate_password( 48, false, false );
		update_option( 'wwc_webhook_secret', $secret );
	}
	return $secret;
}

/** Product IDs of the two membership tiers, keyed 'club' / 'club_credit'. */
function wwc_membership_product_ids() {
	$ids = get_option( 'wwc_membership_product_ids', array() );
	return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
}

/** True when the given (or current) user is a Club member. */
function wwc_is_member( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	$user = get_userdata( $user_id );
	return $user && in_array( 'wwc_member', (array) $user->roles, true );
}

// ---------------------------------------------------------------------------
// Modules
// ---------------------------------------------------------------------------

require_once WWC_PLUGIN_DIR . 'includes/assets.php';
require_once WWC_PLUGIN_DIR . 'includes/chrome.php';
require_once WWC_PLUGIN_DIR . 'includes/shortcodes.php';
require_once WWC_PLUGIN_DIR . 'includes/membership.php';
require_once WWC_PLUGIN_DIR . 'includes/credit.php';
require_once WWC_PLUGIN_DIR . 'includes/checkout.php';
require_once WWC_PLUGIN_DIR . 'includes/account.php';
require_once WWC_PLUGIN_DIR . 'includes/admin.php';

// ---------------------------------------------------------------------------
// Activation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'wwc_activate' );
function wwc_activate() {
	// Member role: customer capabilities plus the member flag.
	add_role( 'wwc_member', 'Club Member', array( 'read' => true ) );
	wwc_webhook_secret(); // ensure a secret exists.
	if ( false === get_option( 'wwc_member_discount', false ) ) {
		update_option( 'wwc_member_discount', 10 );
	}
}

// Role needs to exist even if activation hook was missed (e.g. manual copy).
add_action( 'init', function () {
	if ( ! get_role( 'wwc_member' ) ) {
		add_role( 'wwc_member', 'Club Member', array( 'read' => true ) );
	}
} );
