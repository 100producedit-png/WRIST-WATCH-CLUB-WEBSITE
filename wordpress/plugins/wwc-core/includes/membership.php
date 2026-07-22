<?php
/**
 * Membership business rules (README "Business rules" 1–4):
 *  - Two one-time $150 tiers: Club and Club + Credit.
 *  - Joining requires an account AND a qualifying watch ($100+) in the first order.
 *  - Members get the club discount off list price, storewide.
 *  - Products tagged "members-only" can only be bought by members.
 */

defined( 'ABSPATH' ) || exit;

const WWC_QUALIFYING_MIN = 100.0;

/** Does this cart contain one of the membership products? */
function wwc_cart_has_membership() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	$ids = array_values( wwc_membership_product_ids() );
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( in_array( (int) $item['product_id'], $ids, true ) ) {
			return true;
		}
	}
	return false;
}

/** Does this cart contain a qualifying watch ($100+ list price, non-membership)? */
function wwc_cart_has_qualifying_watch() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	$ids = array_values( wwc_membership_product_ids() );
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( in_array( (int) $item['product_id'], $ids, true ) ) {
			continue;
		}
		$product = $item['data'];
		$list    = (float) ( $product->get_regular_price() ? $product->get_regular_price() : $product->get_price() );
		if ( $list >= WWC_QUALIFYING_MIN ) {
			return true;
		}
	}
	return false;
}

// Rule 2: membership requires a qualifying $100+ watch in the same order.
add_action( 'woocommerce_check_cart_items', function () {
	if ( ! wwc_cart_has_membership() ) {
		return;
	}
	if ( wwc_is_member() ) {
		wc_add_notice( 'You are already a Club member — remove the membership from your bag.', 'error' );
		return;
	}
	if ( ! wwc_cart_has_qualifying_watch() ) {
		wc_add_notice(
			'To join the Club, your first order must include a qualifying watch of $100 or more alongside the $150 membership fee. <a href="' . esc_url( wwc_url( 'men' ) ) . '">Add a watch →</a>',
			'error'
		);
	}
} );

// Rule 2 (account): membership purchases must create/have an account.
add_filter( 'woocommerce_checkout_registration_required', function ( $required ) {
	return wwc_cart_has_membership() ? true : $required;
} );
add_filter( 'woocommerce_checkout_registration_enabled', function ( $enabled ) {
	return wwc_cart_has_membership() ? true : $enabled;
} );

// Grant the member role once a qualifying order is paid.
function wwc_maybe_grant_membership( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->get_customer_id() ) {
		return;
	}
	$ids  = wwc_membership_product_ids();
	$tier = '';
	$has_watch = false;
	foreach ( $order->get_items() as $item ) {
		$pid = (int) $item->get_product_id();
		if ( in_array( $pid, array_values( $ids ), true ) ) {
			$tier = ( isset( $ids['club_credit'] ) && $pid === $ids['club_credit'] ) ? 'club_credit' : 'club';
			continue;
		}
		$product = $item->get_product();
		if ( $product ) {
			$list = (float) ( $product->get_regular_price() ? $product->get_regular_price() : $product->get_price() );
			if ( $list >= WWC_QUALIFYING_MIN ) {
				$has_watch = true;
			}
		}
	}
	if ( ! $tier || ! $has_watch ) {
		return;
	}

	$user = get_userdata( $order->get_customer_id() );
	if ( $user && ! in_array( 'wwc_member', (array) $user->roles, true ) ) {
		$user->add_role( 'wwc_member' );
		update_user_meta( $user->ID, 'wwc_member_since', $order->get_date_paid() ? $order->get_date_paid()->format( 'Y-m-d' ) : current_time( 'Y-m-d' ) );
		update_user_meta( $user->ID, 'wwc_tier', $tier );
		$order->add_order_note( 'Club membership activated (' . $tier . ').' );
	}
}
add_action( 'woocommerce_order_status_processing', 'wwc_maybe_grant_membership' );
add_action( 'woocommerce_order_status_completed', 'wwc_maybe_grant_membership' );

// Rule: member pricing — club discount off list, storewide (except memberships).
function wwc_apply_member_price( $price, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $price;
	}
	if ( ! wwc_is_member() ) {
		return $price;
	}
	if ( in_array( (int) $product->get_id(), array_values( wwc_membership_product_ids() ), true ) ) {
		return $price;
	}
	$base = (float) $product->get_regular_price( 'edit' );
	if ( $base <= 0 ) {
		return $price;
	}
	return round( $base * ( 1 - wwc_member_discount() / 100 ), 2 );
}
add_filter( 'woocommerce_product_get_price', 'wwc_apply_member_price', 20, 2 );
add_filter( 'woocommerce_product_get_sale_price', 'wwc_apply_member_price', 20, 2 );

// Members-only references are not purchasable by non-members.
add_filter( 'woocommerce_is_purchasable', function ( $purchasable, $product ) {
	if ( $purchasable && has_term( 'members-only', 'product_tag', $product->get_id() ) && ! wwc_is_member() ) {
		return false;
	}
	return $purchasable;
}, 10, 2 );

add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id ) {
	if ( has_term( 'members-only', 'product_tag', $product_id ) && ! wwc_is_member() ) {
		wc_add_notice( 'This reference is exclusive to Club members. <a href="' . esc_url( wwc_url( 'club' ) ) . '">Join the Club →</a>', 'error' );
		return false;
	}
	return $passed;
}, 10, 2 );
