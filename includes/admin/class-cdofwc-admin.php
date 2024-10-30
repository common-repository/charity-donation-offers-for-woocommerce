<?php
/**
 * Handle admin scripts
 *
 * @class       Charity_Donation_Offers_For_WooCommerce_admin_Scripts
 * @version     1.0
 * @package     Charity_Donation_Offers_For_WooCommerce/Classes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CDOFWC_Admin' ) ) :
	/**
	 * Class handling the settings page and onboarding Wizard registration and rendering.
	 */
	class CDOFWC_Admin {

		/**
		 * Initialize class
		 */
		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'cdofwc_admin_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'cdofwc_scripts_callback' ) );

			add_filter( 'woocommerce_order_amount_item_subtotal', array( $this, 'cdofwc_change_price_regular_member' ), 99, 3 );

			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'cdofwc_order_items_column' ) );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'cdofwc_order_items_column_count' ), 25, 2 );

			add_action( 'woocommerce_after_product_object_save', array( $this, 'cdofwc_update_product_attributes_func' ), 10, 2 );

			add_filter( 'woocommerce_order_get_subtotal', array( $this, 'cdofwc_wc_order_get_subtotal' ), 10, 2 );
			add_filter( 'woocommerce_order_subtotal_to_display', array( $this, 'cdofwc_wc_order_subtotal_to_display' ), 10, 3 );

			add_action( 'admin_menu', array( $this, 'cdofwc_dashboard_payment' ), 99 );
			add_action( 'admin_menu', array( $this, 'cdofwc_add_custom_class_adminMenu' ), 100 );
		}

		public function cdofwc_add_custom_class_adminMenu() {
			global $menu, $submenu;

			$main_items = isset( $submenu['woocommerce'] ) ? $submenu['woocommerce'] : array();
			foreach ( $main_items as $key => $menu_item ) {

				if ( 'Charity Payment' == $menu_item[0] ) {
					$submenu['woocommerce'][ $key ][] .= ' hide-if-js';
				}
			}

		}

		public function cdofwc_wc_order_subtotal_to_display( $subtotal, $compound, $order ) {
			$order_id      = $order->get_id();
			$charity_order = get_post_meta( $order_id, '_is_charity_order', true );
			if ( $charity_order ) {
				$charity_amount = get_post_meta( $order_id, '_charity_product_amount', true );
				if ( $charity_amount > 0 ) {
					$subtotal = floatval( $subtotal ) - floatval( $charity_amount );
				}
			}
			return $subtotal;
		}

		public function cdofwc_wc_order_get_subtotal( $subtotal, $order ) {
			$order_id      = $order->get_id();
			$charity_order = get_post_meta( $order_id, '_is_charity_order', true );
			if ( $charity_order ) {
				$charity_amount = get_post_meta( $order_id, '_charity_product_amount', true );
				if ( $charity_amount > 0 ) {
					$subtotal = $subtotal - $charity_amount;
				}
			}
			return $subtotal;
		}

		public function cdofwc_update_product_attributes_func( $product, $data_store ) {

			$product_type = $product->get_type();
			$prod_id      = $product->get_id();
			$prod_name    = $product->get_name();

			if ( $product->is_type( 'cdofwc-charity' ) ) {

				$cat    = get_term_by( 'name', 'Charity Donation', 'product_cat' );
				$cat_id = $cat->term_id;

				wp_set_object_terms( $prod_id, $cat_id, 'product_cat', true );

			}
		}

		public function cdofwc_dashboard_payment() {
			add_submenu_page(
				'woocommerce',
				'Payment',
				'Charity Payment',
				'manage_options',
				'charity_payment',
				array( $this, 'cdofwc_payment_page_callback' ),
				9999
			);
		}

		public function cdofwc_payment_page_callback() {

			include __DIR__ . '/views/payment_form.php';

		}

		public function cdofwc_admin_page() {

			wp_enqueue_script( 'my_custom_script', CDOFWC_PLUGIN_DIR_URL . 'assets/admin/js/cdofwc-admin-script.js', array(), filemtime( CDOFWC_PLUGIN_DIR_PATH . 'assets/admin/js/cdofwc-admin-script.js' ), true );
			wp_enqueue_style( 'my_custom_css', CDOFWC_PLUGIN_DIR_URL . 'assets/admin/css/cdofwc-admin.css', array(), filemtime( CDOFWC_PLUGIN_DIR_PATH . 'assets/admin/css/cdofwc-admin.css' ), '' );

		}

		public function cdofwc_scripts_callback() {

			// Add the Select2 CSS file.
			wp_enqueue_style( 'select2-css', CDOFWC_PLUGIN_DIR_URL . 'assets/admin/css/select2.min.css', array(), '4.0.13' );

			// Add the Select2 JavaScript file.
			wp_enqueue_script( 'select2-js', CDOFWC_PLUGIN_DIR_URL . 'assets/admin/js/select2.min.js', 'jquery', '4.0.13', true );

		}

		/**
		 * A handy utility function to insert a key/value pair into an associative array.
		 *
		 * @param array  $source_array The array you want to inject something into.
		 * @param string $key          The key of the element you want to inject your
		 *                             new data after.
		 * @param array  $new_element  The associative array you want to inject.
		 *
		 * @return array               The original $source_array with $new_element
		 *                             injected into it, after $key.
		 */
		public function cdofwc_insert_into_array_after_key( array $source_array, string $key, array $new_element ) {
			if ( array_key_exists( $key, $source_array ) ) {
				$position = array_search( $key, array_keys( $source_array ) ) + 1;
			} else {
				$position = count( $source_array );
			}
			$before = array_slice( $source_array, 0, $position, true );
			$after  = array_slice( $source_array, $position, null, true );
			return array_merge( $before, $new_element, $after );
		}

		public function cdofwc_change_price_regular_member( $subtotal, $order, $item ) {

			$charity_enable_options = get_option( 'charity_enable_options' );

			if ( $charity_enable_options == 'yes' ) {
				$product_type = $item->get_product()->get_type();

				if ( $product_type == 'cdofwc-charity' ) {

					$display_price = get_post_meta( $order->get_order_number(), '_charity_product_amount', true );

					if ( ! empty( $display_price ) ) {

						return $display_price;
					}
				}
			}

			return $subtotal;
		}

		public function cdofwc_order_items_column( $order_columns ) {

			$order_columns = $this->cdofwc_insert_into_array_after_key(
				$order_columns,
				'order_status',
				array(
					'is_charity'      => __( 'Charity', 'charity-donation-offers-for-woocommerce' ),
					'charity_amt'     => __( 'Charity Amount', 'charity-donation-offers-for-woocommerce' ),
					'charity_payment' => __( 'Payment', 'charity-donation-offers-for-woocommerce' ),
					'charity_status'  => __( 'Donation Status', 'charity-donation-offers-for-woocommerce' ),
				)
			);

			return $order_columns;

		}

		public function cdofwc_order_items_column_count( $colname, $order ) {
			// global $order; // the global order object

			if ( $colname == 'is_charity' ) {

				$order_items = $order->get_order_number();
				$is_charity  = $order->get_meta( '_is_charity_order', true );

				if ( ! empty( $is_charity ) && $is_charity == 'yes' ) {
					echo wp_kses_post( '<span class="dashicons dashicons-yes-alt wdgk_right_icon"></span>' );
				}
			}

			if ( $colname == 'charity_amt' ) {

				$order_items = $order->get_order_number();
				$is_charity  = $order->get_meta( '_charity_product_amount', true );
				if ( ! empty( $is_charity ) && $is_charity > 0 ) {
					echo wp_kses_post( wc_price( $is_charity ) );
				}
			}

			if ( $colname == 'charity_payment' ) {

				$order_items = $order->get_order_number();
				$is_charity  = $order->get_meta( '_is_charity_order', true );
				$is_donation = $order->get_meta( '_is_donation_done', true );

				if ( ! empty( $is_charity ) ) {

					// $is_donation = 'yes';
					if ( ! empty( $is_donation ) && $is_donation == 'yes' ) {

						$slug = 'charity-donation';
						$url  = '#';

						// Output the button
						echo 'Done';
					} else {

						$slug = 'charity-donation';
						$url = wp_nonce_url( admin_url( 'admin.php?page=charity_payment&order_id=' . $order->get_order_number() ), 'charity-nonce', 'my_nonce' );

						// Output the button
						echo '<p><a class="button wc-action-button wc-action-button' . esc_html( $slug ) .  '" href="' . esc_url( $url ) . '" aria-label="' . esc_html($slug) . '" target="_blank"> Donate </a></p>';
					}
				}
			}

			if ( $colname == 'charity_status' ) {
				$order_items = $order->get_order_number();
				$is_donation = $order->get_meta( '_is_donation_done', true );
				$is_charity  = $order->get_meta( '_is_charity_order', true );

				if ( ! empty( $is_charity ) ) {
					if ( ! empty( $is_donation ) && $is_donation == 'yes' ) {

						$status = 'Donation completed';
						
					} else {

						$status = 'Donation outstanding';
						
					}
					// esc_html( printf( '%s',	 $status ) );
					echo wp_kses_post( $status );
				}
			}
		}
	}

endif;

new CDOFWC_Admin();