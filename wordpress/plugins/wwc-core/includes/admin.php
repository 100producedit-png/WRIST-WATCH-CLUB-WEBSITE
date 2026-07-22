<?php
/**
 * Settings: WooCommerce → Wrist Watch Club.
 * Member discount %, underwriting endpoint, webhook secret.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_submenu_page(
		'woocommerce',
		'Wrist Watch Club',
		'Wrist Watch Club',
		'manage_woocommerce',
		'wwc-settings',
		'wwc_render_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'wwc_settings', 'wwc_member_discount', array( 'sanitize_callback' => 'absint' ) );
	register_setting( 'wwc_settings', 'wwc_underwriting_endpoint', array( 'sanitize_callback' => 'esc_url_raw' ) );
} );

function wwc_render_settings_page() {
	?>
	<div class="wrap">
		<h1>Wrist Watch Club</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'wwc_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="wwc_member_discount">Member discount (%)</label></th>
					<td>
						<input name="wwc_member_discount" id="wwc_member_discount" type="number" min="0" max="30" step="1"
							value="<?php echo esc_attr( wwc_member_discount() ); ?>" class="small-text">
						<p class="description">Percent off list price for Club members, storewide. Design default 10–20%.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wwc_underwriting_endpoint">Underwriting endpoint (SUBMIT_ENDPOINT)</label></th>
					<td>
						<input name="wwc_underwriting_endpoint" id="wwc_underwriting_endpoint" type="url" class="regular-text"
							value="<?php echo esc_attr( wwc_underwriting_endpoint() ); ?>" placeholder="https://underwriting.example.com/applications">
						<p class="description">The store-credit application POSTs directly from the visitor's browser to this URL.
						SSN/DOB never touch WordPress. Leave empty to keep the form in stub mode (no data transmitted).</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Decision webhook</th>
					<td>
						<code><?php echo esc_html( rest_url( 'wwc/v1/credit-decision' ) ); ?></code>
						<p class="description">Give this URL + the secret below to the underwriting service. It must POST
						<code>{"email","status":"approved|declined","limit"}</code> with header <code>X-WWC-Signature</code> =
						HMAC-SHA256 (hex) of the raw body keyed with the secret. WordPress stores only the decision and limit.</p>
						<p><strong>Webhook secret:</strong> <code><?php echo esc_html( wwc_webhook_secret() ); ?></code></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
