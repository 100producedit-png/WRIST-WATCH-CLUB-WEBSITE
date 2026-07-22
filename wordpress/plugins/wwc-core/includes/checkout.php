<?php
/**
 * Checkout touches:
 *  - "Membership eligible" note when the bag holds membership + $100 watch.
 *  - Member financing panel (informational until the installment engine —
 *    WooCommerce Subscriptions — is installed; then the real payment options
 *    replace it).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_before_checkout_form', function () {
	echo '<div class="wwc-checkout-head wwc-section"><h1>Checkout</h1><span>ENCRYPTED · PCI-COMPLIANT GATEWAY</span></div>';

	if ( wwc_cart_has_membership() && wwc_cart_has_qualifying_watch() ) {
		echo '<p class="wwc-eligible-note wwc-section"><span>✓</span><span>Membership eligible — your order includes a qualifying watch of $100 or more.</span></p>';
	}

	if ( wwc_is_member() ) {
		$credit = wwc_user_credit();
		if ( 'approved' === $credit['status'] && $credit['limit'] > 0 ) {
			echo '<div class="wwc-financing-panel wwc-section"><h4>Store credit — 0% financing <span class="wwc-flag">APPROVED · $' . esc_html( number_format_i18n( $credit['limit'] - $credit['used'], 2 ) ) . ' AVAILABLE</span></h4>'
				. '<p>50% down by card; balance financed from your approved store-credit limit, repaid over up to 4 months. 0% interest, no late fees. Installment checkout goes live once the payment-plan engine is connected — until then orders are paid in full.</p></div>';
		} else {
			echo '<div class="wwc-financing-panel wwc-section"><h4>Member financing <span class="wwc-flag">MEMBERS</span></h4>'
				. '<p>Pay 50% now and the rest over up to 4 months at 0% interest — from approved store credit or your own card on file. <a href="' . esc_url( wwc_url( 'credit' ) ) . '" style="text-decoration:underline;">Apply for store credit →</a></p></div>';
		}
	} elseif ( ! wwc_cart_has_membership() ) {
		echo '<p class="wwc-eligible-note wwc-section"><span></span><span><a href="' . esc_url( wwc_url( 'club' ) ) . '" style="text-decoration:underline;">Join the Club →</a> to unlock member pricing and 0% financing.</span></p>';
	}
} );

// Legal agreement line under the place-order button.
add_action( 'woocommerce_review_order_after_submit', function () {
	echo '<p style="font-size:12px;text-align:center;margin:12px 0 0 0;color:#8b857a;line-height:1.6;">By placing your order you agree to the <a href="' . esc_url( wwc_url( 'legal' ) . '#membership' ) . '" style="text-decoration:underline;">Membership Terms</a> and, if financing, the <a href="' . esc_url( wwc_url( 'legal' ) . '#credit' ) . '" style="text-decoration:underline;">Store-Credit Agreement</a>.</p>';
} );
