<?php
/**
 * Template name: Charity Thankyou
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

get_header(); 

if ( ! isset( $_GET['payment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['payment_nonce'] ) ), 'charity-nonce' ) ) {
	wp_send_json_error( 'bad_nonce' );
	wp_die();
}else{
	if( isset( $_GET['PayerID'] ) && !empty( $_GET['PayerID'] ) ){


			$payment_status = sanitize_text_field( $_POST['payment_status'] );
			$orderId 		= sanitize_text_field( $_POST['custom'] );
			$txn_id 		= sanitize_text_field( $_POST['txn_id'] );

			if( !empty( $payment_status ) && $payment_status == 'Completed'){

				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS is enabled.
					$order          = wc_get_order( $order_id );
					$order->update_meta_data( '_is_donation_done', 'yes' );
					$order->update_meta_data( '_txn_id', $txn_id );
					$order->update_meta_data( '_payment_status', $payment_status );
					
				} else {
					// CPT-based orders are in use.
					update_post_meta( $orderId, '_is_donation_done', 'yes' );
					update_post_meta( $orderId, '_txn_id', $txn_id );
					update_post_meta( $orderId, '_payment_status', $payment_status );
				}

				
			}

			echo '<center><h2>Thank you</h2></center>';
	}
}
	


?>

<?php
get_footer();