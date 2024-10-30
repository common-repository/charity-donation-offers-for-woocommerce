<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'cdofwc_register_product_type' );
function cdofwc_register_product_type() {
	class Charity_CDOFWC_Product extends WC_Product {
		public function __construct( $product ) {
			$this->product_type = 'cdofwc-charity';
			parent::__construct( $product );
		}
	}
}

add_filter( 'product_type_selector', 'cdofwc_add_product_type' );
function cdofwc_add_product_type( $types ) {

	unset( $types['grouped'] );
	unset( $types['external'] );

	$types['cdofwc-charity'] = __( 'Charity Donation', 'charity-donation-offers-for-woocommerce' );
	return $types;
}

add_filter( 'woocommerce_product_data_tabs', 'cdofwc_product_tab' );
function cdofwc_product_tab( $tabs ) {

	global $post;
	$charity_product_id = $post->ID;

	$product = wc_get_product( $charity_product_id );

	// Get type
	$product_type = $product->get_type();

	// Compare
	if ( $product_type == 'cdofwc-charity' ) {

		$tabs['cdofwc-charity'] = array(
			'label'  => __( 'Paypal Setting', 'charity-donation-offers-for-woocommerce' ),
			'target' => 'charity_product_options',
			'class'  => 'show_if_charity_product',
		);

	}

	return $tabs;
}

// Adding the two custom fileds
add_action( 'woocommerce_product_data_panels', 'cdofwc_product_data_panels' );
function cdofwc_product_data_panels() {

	echo '<div id="charity_product_options" class="panel woocommerce_options_panel hidden">';

	woocommerce_wp_text_input(
		array(
			'id'          => 'charity_paypal_email',
			'value'       => get_post_meta( get_the_ID(), 'charity_paypal_email', true ),
			'label'       => __( 'Paypal Email', 'charity-donation-offers-for-woocommerce' ),
			'description' => __( 'Set a paypal email for charity donation for charity.', 'charity-donation-offers-for-woocommerce' ),
		)
	);

	echo '</div>';

}

// Save data
add_action( 'woocommerce_process_product_meta', 'cdofwc_save_custom_tab_data' );
function cdofwc_save_custom_tab_data( $post_id ) {

	if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
		return;
	}

	$charity_paypal_email = sanitize_email( $_POST['charity_paypal_email'] );

	if ( ! empty( $charity_paypal_email ) ) {
		update_post_meta( $post_id, 'charity_paypal_email', esc_html( $charity_paypal_email ) );
	}

}

add_filter( 'woocommerce_product_class', 'cdofwc_product_class', 10, 2 );
function cdofwc_product_class( $classname, $product_type ) {
	if ( $product_type == 'cdofwc-charity' ) {
		$classname = 'Charity_CDOFWC_Product';
	}
	return $classname;
}

add_filter( 'woocommerce_product_filters', 'cdofwc_product_filters' );
function cdofwc_product_filters( $filters ) {
	$filters = str_replace( 'cdofwc-charity', esc_html__( 'cdofwc-charity', 'charity-donation-offers-for-woocommerce' ), $filters );
	return $filters;
}

add_filter( 'woocommerce_product_data_tabs', 'cdofwc_remove_tab', 10, 1 );
function cdofwc_remove_tab( $tabs ) {

	unset( $tabs['linked_product'] ); // Removes the tab section for cross & upsells
	unset( $tabs['additional_information'] );
	unset( $tabs['attribute'] );
	unset( $tabs['advanced'] );

	return( $tabs );
}