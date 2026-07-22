<?php
/**
 * [wwc_dashboard] — the member portal from AccountDashboard.dc.html.
 * Renders above the native WooCommerce account area for logged-in users.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'wwc_dashboard', function () {
	if ( ! is_user_logged_in() ) {
		return ''; // Woo shows its login form below.
	}

	$user   = wp_get_current_user();
	$first  = $user->first_name ? $user->first_name : $user->display_name;
	$member = wwc_is_member();
	$since  = get_user_meta( $user->ID, 'wwc_member_since', true );
	$credit = wwc_user_credit( $user->ID );

	$money = function ( $n ) {
		return '$' . number_format_i18n( (float) $n, 2 );
	};

	ob_start();
	?>
	<div class="wwc-section">
		<section class="wwc-dash-head">
			<h1>Hello, <?php echo esc_html( $first ); ?>.</h1>
			<span class="wwc-dash-since">
				<?php if ( $member ) : ?>
					CLUB MEMBER<?php echo $since ? ' · SINCE ' . esc_html( strtoupper( date_i18n( 'M Y', strtotime( $since ) ) ) ) : ''; ?>
				<?php else : ?>
					NOT A MEMBER YET
				<?php endif; ?>
			</span>
		</section>

		<section class="wwc-dash-cards">
			<?php if ( 'approved' === $credit['status'] && $credit['limit'] > 0 ) :
				$available = max( 0, $credit['limit'] - $credit['used'] );
				$used_pct  = $credit['limit'] > 0 ? round( $credit['used'] / $credit['limit'] * 100, 1 ) : 0;
				?>
				<div class="wwc-credit-card">
					<div class="wwc-kicker">STORE CREDIT AVAILABLE</div>
					<div class="wwc-credit-amount"><strong><?php echo esc_html( $money( $available ) ); ?></strong><span>of <?php echo esc_html( $money( $credit['limit'] ) ); ?> limit</span></div>
					<div class="wwc-credit-meter"><i style="width:<?php echo esc_attr( $used_pct ); ?>%;"></i></div>
					<div class="wwc-credit-legend"><span>In use: <?php echo esc_html( $money( $credit['used'] ) ); ?></span><span>0% interest · no late fees</span></div>
				</div>
			<?php else : ?>
				<div class="wwc-credit-card">
					<div class="wwc-kicker">STORE CREDIT</div>
					<div class="wwc-credit-amount"><strong><?php echo 'declined' === $credit['status'] ? 'Not approved' : 'Not applied'; ?></strong></div>
					<div class="wwc-credit-legend" style="margin-top:18px;">
						<span><?php echo 'declined' === $credit['status'] ? 'See the notice we emailed you for details.' : 'Apply for up to $6,500 — soft check, no score impact.'; ?></span>
					</div>
					<?php if ( 'declined' !== $credit['status'] ) : ?>
						<a class="wwc-btn wwc-btn--light wwc-btn--sm" style="margin-top:20px;" href="<?php echo esc_url( wwc_url( 'credit' ) ); ?>">START APPLICATION</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="wwc-next-card">
				<div class="wwc-kicker">NEXT PAYMENT</div>
				<div class="wwc-next-amt">—</div>
				<div class="wwc-next-meta">No installment payments scheduled.</div>
				<?php if ( ! $member ) : ?>
					<a class="wwc-btn wwc-btn--ghost wwc-btn--sm" href="<?php echo esc_url( wwc_url( 'club' ) ); ?>">JOIN THE CLUB</a>
				<?php endif; ?>
			</div>
		</section>

		<section class="wwc-dash-section">
			<h3>Active payment plans</h3>
			<p class="wwc-dash-empty">No active plans. Financed purchases will show their 4-month schedule here.</p>
		</section>

		<section class="wwc-dash-section">
			<h3>Order history</h3>
			<?php
			$orders = wc_get_orders( array( 'customer_id' => $user->ID, 'limit' => 10 ) );
			if ( $orders ) :
				?>
				<div class="wwc-orders-headrow"><span>ORDER</span><span>DATE</span><span>ITEM</span><span>TOTAL</span><span>STATUS</span></div>
				<?php foreach ( $orders as $order ) :
					$names = array();
					foreach ( $order->get_items() as $item ) {
						$names[] = wp_strip_all_tags( $item->get_name() );
					}
					$label = $names ? ( mb_strlen( implode( ' + ', $names ) ) > 48 ? mb_substr( implode( ' + ', $names ), 0, 45 ) . '…' : implode( ' + ', $names ) ) : '—';
					?>
					<div class="wwc-orders-row">
						<span class="wwc-num">#<?php echo esc_html( $order->get_order_number() ); ?></span>
						<span><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'M j, Y' ) : '' ); ?></span>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="wwc-total"><?php echo esc_html( $money( $order->get_total() ) ); ?></span>
						<span class="wwc-status"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="wwc-dash-empty">No orders yet. <a href="<?php echo esc_url( wwc_url( 'men' ) ); ?>" style="text-decoration:underline;">Browse the bench →</a></p>
			<?php endif; ?>
		</section>

		<section class="wwc-dash-tiles">
			<a class="wwc-dash-tile" href="<?php echo esc_url( wc_get_account_endpoint_url( 'payment-methods' ) ); ?>"><b>Payment methods</b><span>Manage your vaulted cards.</span></a>
			<a class="wwc-dash-tile" href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><b>Profile &amp; addresses</b><span>Update your details.</span></a>
			<a class="wwc-dash-tile" href="<?php echo esc_url( wwc_url( 'credit' ) ); ?>"><b>Store credit</b><span><?php echo 'approved' === $credit['status'] ? 'Request a limit increase.' : 'Apply for store credit.'; ?></span></a>
			<a class="wwc-dash-tile" href="<?php echo esc_url( wwc_url( 'legal' ) ); ?>"><b>Legal</b><span>Terms, privacy &amp; agreements.</span></a>
		</section>
	</div>
	<?php
	return ob_get_clean();
} );
