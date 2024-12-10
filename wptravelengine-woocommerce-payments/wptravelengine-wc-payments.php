<?php
/**
 * Plugin Name: WP Travel Engine - WooCommerce Payments
 * Plugin URI: https://wptravelengine.com
 * Description: The plugin provides compatibility with WooCommerce Payment Gateways and WP Travel Engine to make financial transaction.
 * Version: 1.1.1
 * Author: WP Travel Engine
 * Author URI: https://wptravelengine.com
 * Text Domain: wptravelengine-wc-payments
 * Domain Path: /i18n/languages/
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * Tested up to: 6.2.2
 * WTE Requires at least: 5.4
 * WTE tested up to: 5.5
 * WC requires at least: 6.4
 * WC tested up to: 6.4
 * WTE: 118727:wptravelengine_wc_payments_license_key
 *
 * @package WPTRAVELENGINE_WC_PAYMENTS
 */

defined( 'ABSPATH' ) || exit;

define( 'WPTRAVELENGINE_WC_PAYMENTS_FILE__', __FILE__ );
define( 'WPTRAVELENGINE_WC_PAYMENTS_DIR__', __DIR__ );
define( 'WPTRAVELENGINE_WC_PAYMENTS_VERSION', '1.1.1' );
define( 'WPTRAVELENGINE_WC_PAYMENTS_REQUIRES_AT_LEAST', '5.4' );
define( 'WOOCOMMERCE_WC_PAYMENTS_REQUIRES_AT_LEAST', '6.4' );

add_action( 'plugins_loaded', 'wptravelengine_wc_payments' );
/**
 * Runs on plugins_loaded hook.
 */
function wptravelengine_wc_payments() {

	$meet_requirements = defined( 'WP_TRAVEL_ENGINE_VERSION' ) && version_compare( WP_TRAVEL_ENGINE_VERSION, WPTRAVELENGINE_WC_PAYMENTS_REQUIRES_AT_LEAST, '>=' );
	$meet_requirements = $meet_requirements && defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, WOOCOMMERCE_WC_PAYMENTS_REQUIRES_AT_LEAST, '>=' );

	/**
	 * Load plugin updater file
	 */
	if ( ! $meet_requirements ) {
		add_action(
			'admin_notices',
			function() {
				echo wp_kses(
					sprintf(
						'<div class="wte-admin-notice error"><p>%1$s</p></div>',
						sprintf( __( 'The %1$s requires at least WP Travel Engine %2$s and Woocommerce %3$s, the plugin is currently NOT RUNNING.', 'wptravelengine-wc-payments' ), '<b>WP Travel Engine Support for WooCommerce Payment Gateways</b>', '<code>' . WPTRAVELENGINE_WC_PAYMENTS_REQUIRES_AT_LEAST . '</code>', '<code>' . WOOCOMMERCE_WC_PAYMENTS_REQUIRES_AT_LEAST . '</code>' )
					),
					array(
						'div'  => array( 'class' => array() ),
						'p'    => array(),
						'b'    => array(),
						'code' => array(),
					)
				);
			}
		);
	} else {
		/**
		 * The core plugin class that is used to define internationalization,
		 * admin-specific hooks, and public-facing site hooks.
		 */
		if ( ! class_exists( 'WPTRAVELENGINE_WC_PAYMENTS\Plugin', false ) ) {
			require WPTRAVELENGINE_WC_PAYMENTS_DIR__ . '/includes/class-plugin.php';
		}
	}
}
