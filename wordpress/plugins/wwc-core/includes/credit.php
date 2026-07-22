<?php
/**
 * Store credit (README rules 5–8):
 *  - The application form POSTs sensitive data (SSN/DOB/income) from the
 *    BROWSER directly to the external underwriting service. WordPress never
 *    receives or stores it.
 *  - This webhook receiver stores ONLY approved/declined + limit.
 *  - Approval email is sent from WP on webhook; the DENIAL email (ECOA
 *    adverse-action notice) is sent by the external compliant service, never
 *    from WordPress.
 *
 * Webhook contract:
 *   POST /wp-json/wwc/v1/credit-decision
 *   Header:  X-WWC-Signature: hex HMAC-SHA256 of the raw JSON body, keyed
 *            with the shared secret (WooCommerce → Wrist Watch Club settings).
 *   Body:    { "email": "…", "status": "approved"|"declined", "limit": 6500 }
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'wwc/v1', '/credit-decision', array(
		'methods'             => 'POST',
		'callback'            => 'wwc_handle_credit_decision',
		'permission_callback' => 'wwc_verify_webhook_signature',
	) );
} );

function wwc_verify_webhook_signature( WP_REST_Request $request ) {
	$sig = $request->get_header( 'x-wwc-signature' );
	if ( ! $sig ) {
		return new WP_Error( 'wwc_no_signature', 'Missing X-WWC-Signature header.', array( 'status' => 401 ) );
	}
	$expected = hash_hmac( 'sha256', $request->get_body(), wwc_webhook_secret() );
	if ( ! hash_equals( $expected, strtolower( trim( $sig ) ) ) ) {
		return new WP_Error( 'wwc_bad_signature', 'Invalid signature.', array( 'status' => 403 ) );
	}
	return true;
}

function wwc_handle_credit_decision( WP_REST_Request $request ) {
	$email  = sanitize_email( (string) $request->get_param( 'email' ) );
	$status = strtolower( sanitize_key( (string) $request->get_param( 'status' ) ) );
	$limit  = (float) $request->get_param( 'limit' );

	if ( ! in_array( $status, array( 'approved', 'declined' ), true ) ) {
		return new WP_Error( 'wwc_bad_status', 'status must be approved or declined.', array( 'status' => 400 ) );
	}
	$user = $email ? get_user_by( 'email', $email ) : false;
	if ( ! $user ) {
		return new WP_Error( 'wwc_no_user', 'No account found for that email.', array( 'status' => 404 ) );
	}

	// Store ONLY the decision + limit. Never SSN/DOB/report data.
	update_user_meta( $user->ID, 'wwc_credit_status', $status );
	update_user_meta( $user->ID, 'wwc_credit_limit', 'approved' === $status ? $limit : 0 );
	update_user_meta( $user->ID, 'wwc_credit_decided_at', current_time( 'mysql', true ) );

	if ( 'approved' === $status ) {
		wwc_send_approval_email( $user, $limit );
	}
	// Declined: the external service sends the ECOA adverse-action notice.

	return rest_ensure_response( array( 'ok' => true, 'status' => $status ) );
}

/** Approval email, from emails/approval-email.html in the handoff. */
function wwc_send_approval_email( WP_User $user, $limit ) {
	$first = $user->first_name ? $user->first_name : $user->display_name;
	$vars  = array(
		'{first_name}' => esc_html( $first ),
		'{limit}'      => '$' . number_format_i18n( $limit, 0 ),
		'{shop_url}'   => esc_url( wwc_url( 'men' ) ),
		'{account_url}'=> esc_url( wwc_url( 'account' ) ),
		'{support}'    => esc_html( get_option( 'admin_email' ) ),
	);
	$body = strtr( wwc_approval_email_template(), $vars );

	add_filter( 'wp_mail_content_type', 'wwc_html_mail' );
	wp_mail( $user->user_email, "You're approved — Wrist Watch Club", $body );
	remove_filter( 'wp_mail_content_type', 'wwc_html_mail' );
}

function wwc_html_mail() {
	return 'text/html';
}

function wwc_approval_email_template() {
	$file = WWC_PLUGIN_DIR . 'templates/approval-email.html';
	if ( file_exists( $file ) ) {
		return (string) file_get_contents( $file );
	}
	return '<p>Dear {first_name}, your Club Credit application has been approved. Your credit limit is {limit}.</p>';
}

/** [wwc_credit_status] — small helper used on the account dashboard. */
function wwc_user_credit( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	return array(
		'status' => get_user_meta( $user_id, 'wwc_credit_status', true ),
		'limit'  => (float) get_user_meta( $user_id, 'wwc_credit_limit', true ),
		// Financed balance in use — becomes live once the installment engine
		// (WooCommerce Subscriptions) is connected. 0 until then.
		'used'   => (float) get_user_meta( $user_id, 'wwc_credit_used', true ),
	);
}
