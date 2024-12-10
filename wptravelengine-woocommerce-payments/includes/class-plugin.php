<?php
/**
 * Core Class.
 *
 * @package    WPTRAVELENGINE_WC_PAYMENTS
 * @subpackage WPTRAVELENGINE_WC_PAYMENTS/includes
 */

namespace WPTRAVELENGINE_WC_PAYMENTS;

use WPTravelEngine\Core\Booking;

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * @since      1.0.0
 */
class Plugin {

	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Consturctor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();

		$this->checkout = Checkout::instance();
	}

	/**
	 * Return an instance of this class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define Constants.
	 */
	private function define_constants() {

	}

	/**
	 * Include Files.
	 */
	private function includes() {
		require_once WPTRAVELENGINE_WC_PAYMENTS_DIR__ . '/includes/functions.php';
		require_once WPTRAVELENGINE_WC_PAYMENTS_DIR__ . '/includes/class-checkout.php';
		require_once WPTRAVELENGINE_WC_PAYMENTS_DIR__ . '/updater/updater.php';
	}

	/**
	 * Init Hooks.
	 */
	private function init_hooks() {
		add_filter( 'wpte_settings_get_global_tabs', array( $this, 'add_woocommerce_settings_tab' ) );
		add_action( 'wpte_after_save_global_settings_data', array( $this, 'save_wc_settings' ) );
	}

	/**
	 * Save Settings for WC Payments.
	 *
	 * @param array $posted_data $_POST data.
	 */
	public function save_wc_settings( $posted_data ) {
		$settings = get_option( 'wp_travel_engine_settings' );
		if ( isset( $posted_data['wp_travel_engine_settings']['use_woocommerce_payment_gateway'] ) ) {
			$settings['use_woocommerce_payment_gateway'] = $posted_data['wp_travel_engine_settings']['use_woocommerce_payment_gateway'];
			update_option( 'wp_travel_engine_settings', $settings );
		}
	}

	/**
	 * Adds WooCommerce Settings Tab.
	 *
	 * @param array $tabs The tabs array.
	 */
	public function add_woocommerce_settings_tab( $tabs ) {
		$tabs['wpte-payment']['sub_tabs']['woocommerce'] = array(
			'label'        => __( 'WooCommerce', 'wptravelengine-wc-payments' ),
			'content_path' => WPTRAVELENGINE_WC_PAYMENTS_DIR__ . '/includes/tabs/settings/woocommerce.php',
		);
		return $tabs;
	}
}

/**
 * Run the plugin.
 */
function wte_wc_payments_init() {
	return Plugin::instance();
}
wte_wc_payments_init();
