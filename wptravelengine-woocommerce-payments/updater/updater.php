<?php
/**
 * Easy Digital Downloads Plugin Updater
 *
 * @package WPTRAVELENGINE_WC_PAYMENTS
 */
namespace WPTRAVELENGINE_WC_PAYMENTS\Updater;

defined( 'ABSPATH' ) || exit;

/**
 * Includes the files needed for the plugin updater.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php';
}

/**
 * Download ID for the product in Easy Digital Downloads.
 *
 * @since 1.0.0
 */
define( 'WPTRAVELENGINE_WC_PAYMENTS_ITEM_ID', 118727 );

/**
 * Add-ons name for plugin license page.
 *
 * @since 1.0.0
 */
function addon_name( $array ) {
	$array['WP Travel Engine Support for WooCommerce Payment Gateways'] = 'wptravelengine_wc_payments';
	return $array;
}
add_filter( 'wp_travel_engine_addons', __NAMESPACE__ . '\addon_name' );

/**
 * Add-ons Item ID for plugin license page.
 *
 * @since 1.0.0
 */
function addon_id( $array ) {
	$array['wptravelengine_wc_payments'] = WPTRAVELENGINE_WC_PAYMENTS_ITEM_ID;
	return $array;
}
add_filter( 'wp_travel_engine_addons_id', __NAMESPACE__ . '\addon_id' );

/**
 * Add-ons License details for showing updates in plugin license page.
 *
 * @since 1.0.0
 */
function addon_license( $array ) {
	$settings    = get_option( 'wp_travel_engine_license' );
	$license_key = isset( $settings['wptravelengine_wc_payments_license_key'] ) ? esc_attr( $settings['wptravelengine_wc_payments_license_key'] ) : '';

	$array[] =
		array(
			'version' => WPTRAVELENGINE_WC_PAYMENTS_VERSION, // Current version number.
			'license' => $license_key,  // License key (used get_option above to retrieve from DB).
			'item_id' => WPTRAVELENGINE_WC_PAYMENTS_ITEM_ID,   // ID of this product in EDD.
			'author'  => 'WP Travel Engine',  // Author of this plugin.
			'url'     => home_url(),
		);
	return $array;
}

$wp_travel_engine = get_option( 'wp_travel_engine_license', array() );
$addon_status     = isset( $wp_travel_engine['wptravelengine_wc_payments_license_status'] ) ? esc_attr( $wp_travel_engine['wptravelengine_wc_payments_license_status'] ) : '';

if ( isset( $addon_status ) && 'valid' === $addon_status ) {
	add_filter( 'wp_travel_engine_licenses', __NAMESPACE__ . '\addon_license' );
}
