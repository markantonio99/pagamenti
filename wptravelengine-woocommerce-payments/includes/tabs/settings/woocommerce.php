<?php
/**
 * WooCommerce settings tab Content
 */

$settings   = get_option( 'wp_travel_engine_settings', array() );
$is_enabled = isset( $settings['use_woocommerce_payment_gateway'] ) && 'yes' === $settings['use_woocommerce_payment_gateway'];
?>

<div class="wpte-field wpte-checkbox advance-checkbox">
	<label class="wpte-field-label" for="wp_travel_engine_settings[use_woocommerce_payment_gateway]">
		<?php _e( 'Use WooCommerce Payment Gateway for Payments', 'wptravelengine-wc-payments' ); ?>
	</label>
	<div class="wpte-checkbox-wrap">
		<input type="hidden" value="no" name="wp_travel_engine_settings[use_woocommerce_payment_gateway]">
		<input type="checkbox" id="wp_travel_engine_settings[use_woocommerce_payment_gateway]" name="wp_travel_engine_settings[use_woocommerce_payment_gateway]" value="yes"<?php $is_enabled && print( ' checked' ); ?>>
		<label for="wp_travel_engine_settings[use_woocommerce_payment_gateway]"></label>
	</div>
	<span class="wpte-tooltip">
		<?php _e( 'Enabling this option will allow users to use WooCommerce Checkout and Payment Gateways to purchase/book trips. </br><b>Note:</b>This will also disable the default behavior of WP Travel Engine of Trip Booking.', 'wptravelengine-wc-payments' ); ?>
	</span>
</div>
<div class="wpte-info-block">
	<b><?php __( 'Note:', 'wptravelengine-wc-payments' ); ?></b>
	<p>
	<?php echo wp_kses(
		sprintf(
			__( 'If you have enabled tax for WP Travel Engine, You must configure tax settings for WooCommerce. %sView Documentation%s about setting up taxes.', 'wptravelengine-wc-payments' ),
			'<a href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/" target="_blank">',
			'</a>'
		),
		array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			)
		)
	);
	?></p>
</div>
