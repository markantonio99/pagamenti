<?php
/**
 * Checkout Controller.
 *
 * @package WPTRAVELENGINE_WC_PAYMENTS
 * @subpackage WPTRAVELENGINE_WC_PAYMENTS/includes
 * @since 1.0.0
 */

namespace WPTRAVELENGINE_WC_PAYMENTS;

use WPTravelEngine\Core\Booking;

/**
 * Checkout Controller.
 */
class Checkout {

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
		$this->wc_cart = \WC()->cart;

		$this->wte_checkout = Booking::instance();
		$this->wc_checkout  = \WC_Checkout::instance();
		$this->init_hooks();
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
	 * Initialize the hooks.
	 */
	private function init_hooks() {
		$global_settings = get_option( 'wp_travel_engine_settings' );
		if ( ! isset( $global_settings['use_woocommerce_payment_gateway'] ) || 'yes' !== $global_settings['use_woocommerce_payment_gateway'] ) {
			return;
		}

		add_action( 'wte_after_add_to_cart', array( $this, 'add_trip_to_wc_cart' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_cart_trip_data' ), 10, 2 );

		add_action( 'woocommerce_add_to_cart', array( $this, 'update_order' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'update_order' ) );

		add_action( 'woocommerce_after_order_object_save', array( $this, 'save_booking_data' ), 10, 2 );

		add_action( 'wte_before_update_billing_info', array( $this, 'save_booking_billing_info' ) );

		add_action(
			'init',
			function() {
				add_shortcode( 'WP_TRAVEL_ENGINE_PLACE_ORDER', array( '\WC_Shortcodes', 'checkout' ) );
			}
		);

		add_action( 'wte_payment_gateway_woocommerce', array( $this, 'update_booking_meta' ) );

		add_filter( 'woocommerce_checkout_no_payment_needed_redirect', array( $this, 'get_checkout_redirect_url' ) );

		add_action( 'woocommerce_order_item_meta_start', array( $this, 'woocommerce_order_item_meta_start' ), 10, 3 );
		add_action( 'woocommerce_before_order_itemmeta', array( $this, 'woocommerce_before_order_itemmeta' ), 10, 3 );

	}

	public function woocommerce_before_order_itemmeta( $item_id, $item, $product ) {
		global $post;
		if ( 'shop_order' === $post->post_type ) {
			$this->show_booking_details( $item_id, $item, $post->ID );
		}
	}

	public function woocommerce_order_item_meta_start( $item_id, $item, $order ) {
		$this->show_booking_details( $item_id, $item, $order->id );
	}

	public function show_booking_details( $item_id, $item, $order_id ) {
		global $wpdb;
		$query  = "SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = '_wte_wc_order_id' AND `meta_value` = '{$order_id}' LIMIT 1";
		$result = $wpdb->get_row( $query );
		if ( isset( $result->post_id ) ) {
			$order_trips = get_post_meta( $result->post_id, 'order_trips', true );
			$order_data  = array();
			if ( is_array( $order_trips ) ) {
				foreach ( $order_trips as $order_trip ) {
					/**
					 * Added package name in order details page.
					 *
					 * @since 1.1.1
					 */
					if ( isset( $order_trip['package_name'] ) ) {
						$package_name = $order_trip['package_name'];
					}
					if ( isset( $package_name ) ) {
						$order_data[] = '<p>' . sprintf( __( 'Package: %s', 'wptravelengine-wc-payments' ), '<strong>' . $package_name . '</strong>' ) . '</p>';
					}
					$order_data[] = '<p>' . sprintf( __( 'Trip Date: %s', 'wptravelengine-wc-payments' ), '<strong>' . wp_date( 'F j, Y h:i a', strtotime( $order_trip['datetime'] ) ) . '</strong>' ) . '</p>';
					if ( isset( $order_trip['pax'] ) ) {
						$order_data[]       = '<hr/>' . __( '<strong>Pax: </strong>', 'wptravelengine-wc-payments' );
						$pricing_categories = get_terms(
							array(
								'taxonomy'   => 'trip-packages-categories',
								'hide_empty' => false,
							)
						);
						if ( is_array( $pricing_categories ) ) {
							$pricing_categories = array_column( $pricing_categories, null, 'term_id' );
						}
						foreach ( $order_trip['pax'] as $pax_id => $pax ) {
							if ( isset( $pricing_categories[ $pax_id ] ) ) {
								$order_data[] = '<p>' . $pricing_categories[ $pax_id ]->name . ' x <strong>' . $pax . '</strong></p>';
							}
						}
					}

					if ( isset( $order_trip['trip_extras'] ) && is_array( $order_trip['trip_extras'] ) && count( $order_trip['trip_extras'] ) > 1 ) {
						$order_data[] = '<hr/>' . __( '<strong>Trip Extras: </strong>', 'wptravelengine-wc-payments' );
						foreach ( $order_trip['trip_extras'] as $trip_extra ) {
							$order_data[] = '<p>' . $trip_extra['extra_service'] . ' x <strong>' . $trip_extra['qty'] . '</strong></p>';
						}
					}
				}
			}
			print( wp_kses(
				implode( PHP_EOL, $order_data ),
				array(
					'p'      => array(),
					'strong' => array(),
					'br'     => array(),
					'hr'     => array(),
				)
			) );

		}
	}

	/**
	 *
	 * @since 1.1.0
	 */
	public function tax_enabled() {
		$settings = get_option( 'wp_travel_engine_settings', array() );
		return isset( $settings['tax_enable'] ) && 'yes' === $settings['tax_enable'];
	}

	/**
	 * Updates booking meta.
	 *
	 * @since 1.0.1
	 */
	public function update_booking_meta( $payment_id ) {
		$booking_id = get_post_meta( $payment_id, 'booking_id', true );
		$order_data = $this->wc_order_object->get_data();
		update_post_meta( $booking_id, '_wte_wc_order_id', $order_data['id'] );
		update_post_meta( $booking_id, '_wte_wc_order_data', $order_data );
		// Commented out below code to fix email twice received issue.
		// $this->wte_checkout::send_emails( $payment_id );
	}

	/**
	 * Check if the wc payment gateway is enabled.
	 */
	public function is_wc_gateway_enabled() {
		$global_settings = get_option( 'wp_travel_engine_settings' );
		return isset( $global_settings['use_woocommerce_payment_gateway'] ) && 'yes' === $global_settings['use_woocommerce_payment_gateway'];
	}

	/**
	 * Redirects to WC Checkout Page if WTE Checkout page accessed directly
	 * when woocommerce payment gateway is enabled.
	 */
	public function redirect_to_wc_checkout() {
		if ( wp_travel_engine_is_checkout_page() ) {
			if ( $this->is_wc_gateway_enabled() ) {
				global $wte_cart;
				$wte_cart->clear();

				$redirect_url = wc_get_checkout_url();
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * Save Booking Billing Info.
	 *
	 * @param  array $billing_info Billing Info.
	 */
	public function save_booking_billing_info( $billing_info ) {
		$billing_field_mapping = array(
			'fname'           => 'first_name',
			'lname'           => 'last_name',
			'email'           => 'email',
			'booking_address' => 'address_1',
			'booking_city'    => 'city',
			'booking_country' => 'country',
		);

		$billing_data  = $this->wc_order_object->get_data()['billing'];
		$_billing_info = array();
		foreach ( $billing_field_mapping as $wte_field_name => $wc_field_name ) {
			if ( isset( $billing_data[ $wc_field_name ] ) ) {
				$_billing_info[ $wte_field_name ] = $billing_data[ $wc_field_name ];
			}
		}
		return $_billing_info;
	}

	/**
	 * Create and Save Booking Data.
	 *
	 * @since 1.0.0
	 */
	public function save_booking_data( $wc_order_object, $wc_data_store ) {
		global $wte_cart;
		if ( empty( $wte_cart->getItems() ) ) {
			return; // Nothing to process.
		}

		add_filter(
			'wptravelengine_checkout_payment_method',
			function() {
				return 'woocommerce';
			}
		);

		add_filter( 'wptravelengine_redirect_after_booking', '__return_false' );

		$this->wte_checkout->cart = $wte_cart;
		$this->wc_order_object    = $wc_order_object;

		if ( version_compare( WP_TRAVEL_ENGINE_VERSION, '5.5.3', '>=' ) ) {
			$instance = \WPTravelEngine\Core\Booking::instance();
			$instance->booking_process();
			return;
		}

		$booking_id                  = $this->wte_checkout->create_booking();
		$this->wte_checkout->booking = get_post( $booking_id );
		$this->wte_checkout->cart    = $wte_cart;
		$this->wc_order_object       = $wc_order_object;

		$order_data = $this->wc_order_object->get_data();
		update_post_meta( $booking_id, '_wte_wc_order_id', $order_data['id'] );
		update_post_meta( $booking_id, '_wte_wc_order_data', $order_data );

		$this->wte_checkout->update_billing_info();
		$this->wte_checkout->update_order_items();

		wp_update_post(
			array(
				'ID'          => $booking_id,
				'post_title'  => "Booking #{$booking_id}",
				'post_status' => 'publish',
			)
		);

		$this->wte_checkout->update_payment_info();

		$payment_id = $this->wte_checkout::create_payment(
			$booking_id,
			array(
				'booking_id'      => $booking_id,
				'payment_gateway' => 'woocommerce',
			),
			'full_payment'
		);

		update_post_meta( $booking_id, 'payments', array( $payment_id ) );

		// Send Notification Emails.
		$this->wte_checkout::send_emails( $payment_id );

		$wte_cart->clear();

	}

	/**
	 * Updates Order.
	 */
	public function update_order() {
		$wc_cart = \WC()->cart;
		if ( ! WC()->is_rest_api_request() ) {
			foreach ( $wc_cart->get_cart() as $wc_key => $wc_item ) {
				if ( isset( $wc_item['tripbooking'] ) && is_array( $wc_item['tripbooking'] ) ) {
					/** @var \WC_Product $wc_item ['data'] */
					$this->set_product_price( $wc_item['data'], $wc_item );
				}
			}

			$wc_cart->calculate_totals();
		}
	}

	/**
	 * Set product Price.
	 */
	public function set_product_price( $cart_item_data, $cart_item ) {
		global $wte_cart;

		if ( isset( $cart_item['tripbooking'] ) ) {
			$wte_cart_totals = $wte_cart->get_total();

			$total = isset( $wte_cart_totals['cart_total'] ) ? (int) $wte_cart_totals['cart_total'] : 0;

			$cart_item_data->set_price( $total );
			$cart_item_data->set_regular_price( $total );
			$cart_item_data->apply_changes();
		}
		return $cart_item_data;
	}

	/**
	 * Get Trip to data to be shown in cart.
	 *
	 * @param array       $other_data  Other Data.
	 * @param \WC_Product $wc_item WC Product.
	 */
	public function get_cart_trip_data( $other_data, $wc_item ) {
		if ( isset( $wc_item['tripbooking'] ) ) {
			$trip_booking_data = $wc_item['tripbooking'];

			$format = array( get_option( 'date_format', 'Y-m-d' ), get_option( 'time_format', 'H:i' ) );
			if ( isset( $wc_item['tripbooking']['trip_time'] ) && ! empty( $wc_item['tripbooking']['trip_time'] ) ) {
				$datetime = wp_date( implode( ' @', $format ), strtotime( $wc_item['tripbooking']['trip_time'] ) );
			} else {
				$datetime = wp_date( $format[0], strtotime( $wc_item['tripbooking']['trip_date'] ) );
			}
			$booking_info = array(
				'<hr/>',
				'<strong>' . __( 'Trip Date: ', 'wptravelengine-wc-payments' ) . '</strong>' . $datetime,
			);

			/**
			 * Added package name in checkout page.
			 *
			 * @since 1.1.1
			 */
			if ( isset( $wc_item['tripbooking']['price_key'] ) && ! empty( $wc_item['tripbooking']['price_key'] ) ) {
				$package      = $wc_item['tripbooking']['price_key'];
				$package_info = get_post( $package );
				$package_name = $package_info->post_title;
			}
			if ( isset( $package_name ) ) {
				$other_data[] = array(
					'name'  => __( 'Package', 'wptravelengine-wc-payments' ),
					'value' => $package_name,
				);
			}

			$other_data[] = array(
				'name'  => __( 'Trip Booking Info', 'wptravelengine-wc-payments' ),
				'value' => implode(
					PHP_EOL . PHP_EOL,
					$booking_info
				),
			);

			$pax_info = array(
				'<hr/>',
			);

			if ( isset( $trip_booking_data['pricing_options'] ) && is_array( $trip_booking_data['pricing_options'] ) ) {
				foreach ( $trip_booking_data['pricing_options'] as $pricing_option ) {
					$pax_info[] = '<strong>' . $pricing_option['categoryInfo']['label'] . '</strong> x ' . $pricing_option['pax'];
				}
			}

			$other_data[] = array(
				'name'  => __( 'Traveller\'s Info', 'wptravelengine-wc-payments' ),
				'value' => implode(
					PHP_EOL . PHP_EOL,
					$pax_info
				),
			);

			$trip_extras_info = array(
				'<hr/>',
			);

			if ( isset( $trip_booking_data['trip_extras'] ) && is_array( $trip_booking_data['trip_extras'] ) ) {
				foreach ( $trip_booking_data['trip_extras'] as $trip_extras ) {
					$trip_extras_info[] = '<strong>' . $trip_extras['extra_service'] . '</strong> x ' . $trip_extras['qty'];
				}
			}

			if ( isset( $trip_extras_info ) && is_array( $trip_extras_info ) && count( $trip_extras_info ) > 1 ) {
				$other_data[] = array(
					'name'  => __( 'Trip Extras', 'wptravelengine-wc-payments' ),
					'value' => implode(
						PHP_EOL . PHP_EOL,
						$trip_extras_info
					),
				);
			}

		}

		return $other_data;
	}

	/**
	 * WC Cart.
	 */
	private function get_wc_cart() {
		return \WC()->cart;
	}

	/**
	 * Gets alternative id for the trip.
	 *
	 * @param int $trip_id Trip ID.
	 * @since 1.0.0
	 */
	private function get_product_id_of_trip( int $trip_id ) {
		$product_id = get_post_meta( $trip_id, 'wc_product_id', true );
		$trip       = get_post( $trip_id );
		if ( ! is_null( $trip ) ) {
			if ( empty( $product_id ) || is_null( get_post( $product_id ) ) ) {
				$product_id = wp_insert_post(
					array(
						'post_author'  => get_current_user(),
						'post_title'   => $trip->post_title,
						'post_content' => '',
						'post_status'  => 'publish',
						'post_type'    => 'product',
						'meta_input'   => array(
							'_visibility'            => 'hidden',
							'_stock_status'          => 'instock',
							'total_sales'            => 0,
							'_downloadable'          => 'no',
							'_virtual'               => 'yes',
							'_regular_price'         => 0,
							'_sale_price'            => '',
							'_purchase_note'         => '',
							'_featured'              => 'no',
							'_weight'                => '',
							'_length'                => '',
							'_width'                 => '',
							'_height'                => '',
							'_sku'                   => '',
							'_product_attributes'    => array(),
							'_sale_price_dates_from' => '',
							'_sale_price_dates_to'   => '',
							'_price'                 => 0,
							'_sold_individually'     => 'yes',
							'_manage_stock'          => 'no',
							'_backorders'            => 'no',
							'_stock'                 => '',
						),
					)
				);
				wp_set_object_terms( $product_id, 'simple', 'product_type' );
				wp_set_object_terms( $product_id, array( 'exclude-from-catalog', 'exclude-from-search' ), 'product_visibility' );

				update_post_meta( $trip_id, 'wc_product_id', $product_id );
			}
		}

		return $product_id;
	}


	/**
	 * This runs the WC Checkout.
	 */
	public function add_trip_to_wc_cart( $trip_id, $attrs ) {
		$this->wc_cart = $this->get_wc_cart();
		if ( $this->wc_cart ) {
			$wc_cart_contents = $this->wc_cart->get_cart_contents();
			if ( is_array( $wc_cart_contents ) ) {
				foreach ( $wc_cart_contents as $wc_cart_item ) {
					if ( isset( $wc_cart_item['tripbooking'] ) ) {
						$this->wc_cart->remove_cart_item( $wc_cart_item['key'] );
					}
				}
			}
			$this->wc_cart->add_to_cart(
				$this->get_product_id_of_trip( $trip_id ),
				1,
				'',
				array(),
				array( 'tripbooking' => $attrs )
			);
		}
	}

}
