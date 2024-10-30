<?php
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Handle frontend scripts
 *
 * @class       CDOFWC_Frontend_Scripts
 * @version     1.0
 * @package     Charity_Donation_Offers_For_WooCommerce/Classes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'CDOFWC_Frontend' ) ) :
	/**
	 * Class handling the settings page and onboarding Wizard registration and rendering.
	 */
	class CDOFWC_Frontend {

		public $msgEnable    = '';
		public $showCheckout = '';
		public $showSimgle   = '';
		public $showCart     = '';

		public $showMsgCheckout = '';
		public $showMsgSimgle   = '';
		public $showMsgCart     = '';
		/**
		 * Initialize class
		 */
		public function __construct() {

			$this->msgEnable    = get_option( 'charity_message_show_enable' );
			$this->showCheckout = get_option( 'charity_message_show_on_checkout' );
			$this->showSimgle   = get_option( 'charity_message_show_on_single_product' );
			$this->showCart     = get_option( 'charity_message_show_on_cart' );

			$this->showMsgSimgle   = get_option( 'charity_message_enable_single_product' );
			$this->showMsgCart     = get_option( 'charity_message_enable_cart' );
			$this->showMsgCheckout = get_option( 'charity_message_enable_checkout' );

			if ( $this->msgEnable == 'yes' && $this->showMsgSimgle == 'yes' ) {
				add_action( $this->showSimgle, array( $this, 'cdofwc_show_message_single_page_callback' ) );
			}

			if ( $this->msgEnable == 'yes' && $this->showMsgCart == 'yes' ) {
				add_action( $this->showCart, array( $this, 'cdofwc_show_message_cart_callback' ) );
			}

			if ( $this->msgEnable == 'yes' && $this->showMsgCheckout == 'yes' ) {
				add_action( $this->showCheckout, array( $this, 'cdofwc_show_message_checkout_callback' ) );
			}

			add_filter( 'woocommerce_is_purchasable', array( $this, 'cdofwc_disallow_direct_purchase' ), 10, 2 );
			add_action( 'woocommerce_before_cart', array( $this, 'cdofwc_add_free_product_to_cart' ) );
			add_filter( 'woocommerce_get_availability', array( $this, 'cdofwc_custom_availability_message' ), 10, 2 );
			add_filter( 'woocommerce_get_price_html', array( $this, 'cdofwc_hide_price_for_free_products' ), 10, 2 );

			add_filter( 'woocommerce_cart_item_price', array( $this, 'cdofwc_custom_price_based_on_product_type' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_quantity', array( $this, 'cdofwc_set_quentity_based_on_product_type' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'cdofwc_custom_price_based_on_product_type' ), 10, 3 );

			add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'cdofwc_change_display_price_in_email' ), 10, 3 );

			add_action( 'pre_get_posts', array( $this, 'cdofwc_hide_specific_product_type_from_shop' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'cdofwc_wp_enqueue_scripts' ) );

			add_action( 'woocommerce_checkout_create_order', array( $this, 'cdofwc_before_checkout_create_order' ), 20, 2 );

			// add_action('woocommerce_before_calculate_totals', array( $this, 'cdofwc_before_calculate_totals' ), 1000, 1);
			// add_action('woocommerce_after_calculate_totals', array( $this, 'cdofwc_before_calculate_totals' ), 1000, 1);

			add_action( 'woocommerce_before_calculate_totals', array( $this, 'cdofwc_product_name' ), 99, 1 );

			add_action( 'woocommerce_thankyou', array( $this, 'cdofwc_thankyou' ), 99, 2 );
			add_shortcode( 'charity_message', array( $this, 'cdofwc_message_shortcode_callback' ) );
		}

		public function cdofwc_wp_enqueue_scripts() {
			wp_enqueue_style( 'cdofwc-style', CDOFWC_PLUGIN_DIR_URL . 'assets/frontend/css/cdofwc-style.css', array(), filemtime( CDOFWC_PLUGIN_DIR_PATH . 'assets/frontend/css/cdofwc-style.css' ), '' );
		}

		public function cdofwc_product_name( $cart ) {

			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			// Loop through cart items
			foreach ( $cart->get_cart() as $cart_item ) {

				// Get an instance of the WC_Product object
				$product = $cart_item['data'];

				$product_type = $cart_item['data']->get_type();

				if ( $product_type == 'cdofwc-charity' ) {
					// Get the product name (Added Woocommerce 3+ compatibility)
					$original_name = method_exists( $product, 'get_name' ) ? $product->get_name() : $product->post->post_title;

					// SET THE NEW NAME
					$new_name = 'Our donation to ' . $original_name;

					// Set the new name (WooCommerce versions 2.5.x to 3+)
					if ( method_exists( $product, 'set_name' ) ) {
						$product->set_name( $new_name );
					} else {
						$product->post->post_title = $new_name;
					}
				}
			}
		}
		public function cdofwc_before_calculate_totals( $cart_obj ) {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				$charity_pruduct = $this->cdofwc_get_product_id();
				$pid             = '';

				$charity_rule    = $this->cdofwc_get_charity_rule();
				$cart_subtotal      = WC()->cart->subtotal;
				$donation_amount = 0;

				foreach ( $charity_rule as $key => $value ) {

					if ( isset( $value['condition'] ) && ! empty( $value['condition'] ) && $value['condition'] == 'greater' ) {

						if ( ! empty( $value['amount'] ) && $cart_subtotal > $value['amount'] ) {

							$donation_amount = $value['donation'];
						} else {
							$donation_amount = $charity_rule[0]['donation'];
						}
					}
				}

				if ( ! empty( $charity_pruduct ) ) {
					$pid = $charity_pruduct;
				}

				if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
					return;
				}

				foreach ( $cart_obj->get_cart() as $key => $value ) {
					$id = $value['data'];

					if ( $id->get_id() == $pid ) {

						$price = $donation_amount;
						$value['data']->set_price( $price );

					}
				}
			}

		}

		public function cdofwc_show_message_checkout_callback() {

			// Get message show option.
			$enable  = get_option( 'charity_message_show_enable' );
			$showMsg = get_option( 'charity_message_show_on_checkout' );

			$required_cart_subtotal = $this->cdofwc_get_donation();
			$cart_subtotal          = WC()->cart->subtotal;

			if ( $cart_subtotal >= $required_cart_subtotal && $required_cart_subtotal > 0 ) {

			} else {
				// check message show option
				$this->cdofw_donation_message();
			}

		}

		public function cdofwc_show_message_single_page_callback() {

			// Get message show option.

			$enable  = get_option( 'charity_message_show_enable' );
			$showMsg = get_option( 'charity_message_show_on_single_product' );

			$required_cart_subtotal = $this->cdofwc_get_donation();
			$cart_subtotal          = WC()->cart->subtotal;

			if ( $cart_subtotal >= $required_cart_subtotal && $required_cart_subtotal > 0 ) {

			} else {
				// check message show option
				$this->cdofw_donation_message();
			}
		}

		public function cdofwc_show_message_cart_callback() {

			// Get message show option.

			$enable  = get_option( 'charity_message_show_enable' );
			$showMsg = get_option( 'charity_message_show_on_cart' );

			$required_cart_subtotal = $this->cdofwc_get_donation();
			$cart_subtotal          = WC()->cart->subtotal;

			if ( $cart_subtotal >= $required_cart_subtotal && $required_cart_subtotal > 0 ) {

			} else {
				// check message show option
				$this->cdofw_donation_message();
			}
		}

		/**
		 * Check Charity Setting ( Enable/Disable )
		 */
		public function cdofwc_is_charity_enable() {

			$charity_enable_options = get_option( 'charity_enable_options' );
			return $charity_enable_options;
		}

		/**
		 * Get Charity product ID
		 */
		public function cdofwc_get_product_id() {
			$charity_pruduct = get_option( 'charity_charity_product' );
			$charity_pruduct = ( $charity_pruduct ) ? $charity_pruduct : '';

			return $charity_pruduct;
		}

		/**
		 * Get Charity rules
		 */
		public function cdofwc_get_charity_rule() {
			$charity_rules = get_option( 'charity_donation_rules' );
			$charity_rules = ( $charity_rules ) ? $charity_rules : '';

			return $charity_rules;
		}

		/**
		 * Get Charity Donation Amount
		 */
		public function cdofwc_get_donation() {

			$charity_rule    = $this->cdofwc_get_charity_rule();
			$cart_subtotal      = WC()->cart->subtotal;
			$donation_amount = 0;

			if( !empty( $charity_rule ) ){
				foreach ( $charity_rule as $key => $value ) {

					if ( isset( $value['condition'] ) && ! empty( $value['condition'] ) && $value['condition'] == 'greater' ) {

						if ( ! empty( $value['amount'] ) && $cart_subtotal > $value['amount'] ) {

							$donation_amount = $value['donation'];
						}
					}
				}
			}
			

			return $donation_amount;

		}

		/**
		 * Get Charity Donation Amount
		 */
		public function cdofwc_message_shortcode_callback() {

			ob_start();
			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				$charity_pruduct = $this->cdofwc_get_product_id();

				if ( ! empty( $charity_pruduct ) ) {

					$charity_rule     = $this->cdofwc_get_charity_rule();
					$free_product_id  = $charity_pruduct;// 118;
					$donation_product = wc_get_product( $free_product_id );
					$image_id         = $donation_product->get_image_id();
					$image_url        = wp_get_attachment_image_url( $image_id, 'thumb' );

					$messageTitle   = get_option( 'charity_message_option_title' );
					$charityMessage = get_option( 'charity_message_content' );

					$messageTitle = ( $messageTitle ) ? $messageTitle : '';

					$cart_subtotal = WC()->cart->subtotal;
					$cartTotal  = get_woocommerce_currency_symbol() . $charity_rule[0]['amount'];
					$donation   = get_woocommerce_currency_symbol() . $charity_rule[0]['donation'];
					$prodName   = esc_html( $donation_product->get_name() );

					$cartTotal = ( $cartTotal ) ? $cartTotal : 0;
					$donation  = ( $donation ) ? $donation : 0;

					if ( ! empty( $charityMessage ) ) {
						$charityMessage = str_replace( '{condition}', '%1$s', $charityMessage );
						$charityMessage = str_replace( '{donation}', '%2$s', $charityMessage );
						$charityMessage = str_replace( '{charityName}', '%3$s', $charityMessage );
					} else {
						$charityMessage = '';
					}

					?>
						<div class="cdofwc-content cdofwc-full-width">
							<div class="cdofwc-message"><?php printf( wp_kses_post( $messageTitle ) ) ; ?></div>
							<div class="cdofwc-img-content">
								<?php if ( ! empty( $image_url ) ) { ?>
								<img src="<?php echo esc_url( $image_url ); ?>" />
								<?php } ?>
								<div class="cdofwc-details">
									<div class="cdofwc-title">
										<?php echo esc_html( $donation_product->get_name() ); ?>
									</div>
									<div class="cdofwc-short-description">
									<?php echo esc_html($donation_product->get_short_description()); ?>
									</div>
								</div>
							</div>
							<div class="cdofc-rules">
								<?php

								printf(
									/* translators: 1: Cart Total 2: Donation 3: Product Name */
									wp_kses_post( $charityMessage ),
									esc_attr( $cartTotal ),
									esc_attr( $donation ),
									esc_attr( $prodName ),
								);

								?>
							</div>
						</div>
					<?php
				}
			}

			$content = ob_get_clean();

			return $content;
		}

		/**
		 * Get Charity Donation Amount
		 */
		public function cdofw_donation_message() {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				$charity_pruduct = $this->cdofwc_get_product_id();

				if ( ! empty( $charity_pruduct ) ) {

					$charity_rule     = $this->cdofwc_get_charity_rule();
					$free_product_id  = $charity_pruduct;// 118;
					$donation_product = wc_get_product( $free_product_id );
					$image_id         = $donation_product->get_image_id();
					$image_url        = wp_get_attachment_image_url( $image_id, 'thumb' );

					$messageTitle   = get_option( 'charity_message_option_title' );
					$charityMessage = get_option( 'charity_message_content' );

					$messageTitle = ( $messageTitle ) ? $messageTitle : '';

					$cart_subtotal = WC()->cart->subtotal;
					$cartTotal  = get_woocommerce_currency_symbol() . $charity_rule[0]['amount'];
					$donation   = get_woocommerce_currency_symbol() . $charity_rule[0]['donation'];
					$prodName   = $donation_product->get_name();

					$cartTotal = ( $cartTotal ) ? $cartTotal : 0;
					$donation  = ( $donation ) ? $donation : 0;

					if ( ! empty( $charityMessage ) ) {
						$charityMessage = str_replace( '{condition}', '%1$s', $charityMessage );
						$charityMessage = str_replace( '{donation}', '%2$s', $charityMessage );
						$charityMessage = str_replace( '{charityName}', '%3$s', $charityMessage );
					} else {
						$charityMessage = '';
					}
					?>
						<div class="cdofwc-content cdofwc-full-width">
							<div class="cdofwc-message"><?php printf( wp_kses_post( $messageTitle ) ); ?></div>
							<div class="cdofwc-img-content">
								<?php if ( ! empty( $image_url ) ) { ?>
								<img src="<?php echo esc_url( $image_url ); ?>" />
								<?php } ?>
								<div class="cdofwc-details">
									<div class="cdofwc-title">
										<?php echo esc_html( $donation_product->get_name() ); ?>
									</div>
									<div class="cdofwc-short-description">
									<?php echo esc_html( $donation_product->get_short_description() ); ?>
									</div>
								</div>
							</div>
							<div class="cdofc-rules">
								<?php

								printf(
									/* translators: 1: Cart Total 2: Donation 3: Product Name */
									wp_kses_post( $charityMessage ),
									esc_attr( $cartTotal ),
									esc_attr( $donation ),
									esc_attr( $prodName ),
								);
								
								?>
							</div>
						</div>
					<?php
				}
			}
		}

		/**
		 * Before Add to cart function
		 */
		public function cdofwc_add_free_product_to_cart() {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {

				$charity_pruduct = $this->cdofwc_get_product_id();

				if ( ! empty( $charity_pruduct ) ) {

					// Set your desired cart total and free product ID here
					$required_cart_subtotal = $this->cdofwc_get_donation();
					$free_product_id     = $charity_pruduct;// 118;

					$cart_subtotal = WC()->cart->subtotal;

					// Check if cart total is more than your desired amount
					if ( $cart_subtotal >= $required_cart_subtotal && $required_cart_subtotal > 0 ) {

						$found = false;

						// Check if product already in cart
						foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
							$_product = $values['data'];
							if ( $_product->get_id() == $free_product_id ) {
								$found = true;
								break;
							}
						}

						// If not found, add product to cart
						if ( ! $found ) {
							WC()->cart->add_to_cart( $free_product_id );
						}
					} else {

						// If cart total is below the desired amount and free product is in cart, remove it
						foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
							$_product = $values['data'];
							if ( $_product->get_id() == $free_product_id ) {
								WC()->cart->remove_cart_item( $cart_item_key );
							}
						}
					}
				}
			}
		}

		/**
		 * Make Charity product Purchasable
		 */
		public function cdofwc_disallow_direct_purchase( $purchasable, $product ) {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				// Set the ID of your free product here
				$charity_pruduct = $this->cdofwc_get_product_id();

				if ( ! empty( $charity_pruduct ) ) {
					$free_product_id = $charity_pruduct;

					if ( $product->get_id() == $free_product_id ) {
						$purchasable = true;
					}
				}
			}

			return $purchasable;
		}

		/**
		 * Charity Product Message
		 */
		public function cdofwc_custom_availability_message( $availability, $_product ) {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				// Set the ID of your free product here
				$charity_pruduct = $this->cdofwc_get_product_id();

				if ( ! empty( $charity_pruduct ) ) {
					$free_product_id = $charity_pruduct;

					if ( $_product->get_id() == $free_product_id ) {
						$availability['availability'] = __( 'Sorry, this product cannot be purchased directly.', 'charity-donation-offers-for-woocommerce' );
					}
					return $availability;
				}
			}
		}

		public function cdofwc_hide_price_for_free_products( $price, $product ) {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				if ( $product->get_price() == '' || $product->get_price() == 0 || 'cdofwc-charity' == $product->get_type() ) {
					return wc_price( $this->cdofwc_get_donation() );
				}
			}

			return $price;
		}

		public function cdofwc_set_quentity_based_on_product_type( $product_quantity, $cart_item_key, $cart_item ) {
			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				$product_type = $cart_item['data']->get_type();

				if ( $product_type == 'cdofwc-charity' ) {
					$product_quantity = 1;
				}
			}

			return $product_quantity;
		}

		public function cdofwc_custom_price_based_on_product_type( $price, $cart_item, $cart_item_key ) {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				$product_type = $cart_item['data']->get_type();

				// Check for the specific product type (e.g., 'simple', 'variable', 'grouped', 'external', etc.)
				if ( $product_type == 'cdofwc-charity' ) {
					$price = wc_price( $this->cdofwc_get_donation() ); // Replace with your custom display price
					// return wc_price($custom_price);
				}
			}

			return $price;
		}

		public function cdofwc_change_display_price_in_email( $subtotal, $item, $order ) {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {
				$product_type = $item->get_product()->get_type();

				if ( $product_type == 'cdofwc-charity' ) { // Change 'simple' to your desired product type.

					$display_price = $this->cdofwc_get_charity_amount_from_order( $order->get_order_number() );

					if ( ! empty( $display_price ) ) {
						return wc_price( $display_price );
					}
				}
			}

			return $subtotal;
		}

		public function cdofwc_before_checkout_create_order( $order, $data ) {

			if ( $this->cdofwc_is_charity_enable() == 'yes' ) {

				foreach ( $order->get_items() as $item_id => $item ) {

					$get_product_id = $item->get_product_id();
					$product_name   = $item->get_name();
					$product_type   = $item->get_product()->get_type();

					if ( $product_type == 'cdofwc-charity' ) {

						$charity_pruduct = $this->cdofwc_get_product_id();
						$amount          = $this->cdofwc_get_donation();

						$order->update_meta_data( '_charity_product_id', $get_product_id );
						$order->update_meta_data( '_charity_product_name', $product_name );
						$order->update_meta_data( '_charity_product_amount', $amount );
						$order->update_meta_data( '_is_charity_order', 'yes' );

					}
				}
			}

		}

		public function cdofwc_hide_specific_product_type_from_shop( $query ) {

			if ( ! $query->is_main_query() || ! is_post_type_archive( 'product' ) || ! is_shop() ) {
				return;
			}

			// Define the product type you want to exclude
			$product_type_to_exclude = 'cdofwc-charity';

			// Get the product type terms
			$grouped_term = get_term_by( 'slug', $product_type_to_exclude, 'product_type' );

			// If the term exists, modify the query
			if ( $grouped_term ) {
				$tax_query = array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => $product_type_to_exclude,
						'operator' => 'NOT IN',
					),
				);

				$query->set( 'tax_query', $tax_query );
			}
		}

		public function cdofwc_thankyou( $order_id ) {

			$order = wc_get_order( $order_id );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS is enabled.
				$order          = wc_get_order( $order_id );
				$cdofwc_total_amount = $order->update_meta_data( '_cdofwc_order_total_amount', $order->get_total() );
			} else {
				// CPT-based orders are in use.
				$cdofwc_total_amount = update_post_meta( $order_id, '_cdofwc_order_total_amount', $order->get_total() );
			}


			foreach ( $order->get_items() as $item_key => $item ) {
				$product_type = $item->get_product()->get_type();

				if ( $product_type == 'cdofwc-charity' ) {
					$item_data       = $item->get_data();
					$order_item_id   = $item_data['id'];
					$charity_pruduct = $this->cdofwc_get_product_id();
					$amount          = $this->cdofwc_get_donation();

					// $charity_amount = get_post_meta( $order_id, '_charity_product_amount', true);
					$charity_amount = $this->cdofwc_get_charity_amount_from_order( $order_id );

					/*
					 wc_update_order_item_meta($order_item_id, '_line_total', $charity_amount);
					wc_update_order_item_meta($order_item_id, '_line_subtotal', $charity_amount); */

					wc_update_order_item_meta( $order_item_id, '_line_total', '-' . $charity_amount );
					wc_update_order_item_meta( $order_item_id, '_line_subtotal', '-' . $charity_amount );
				}
			}
			$order->calculate_totals();
		}

		public function cdofwc_get_charity_amount_from_order( $order_id ) {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS is enabled.
				$order          = wc_get_order( $order_id );
				$charity_amount = $order->get_meta( '_charity_product_amount', true );
			} else {
				// CPT-based orders are in use.
				$charity_amount = get_post_meta( $order_id, '_charity_product_amount', true );
			}
			return $charity_amount;
		}


	}
endif;

new CDOFWC_Frontend();