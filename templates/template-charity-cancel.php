<?php
/**
 * Template name: Charity Cancel
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
get_header(); 


if ( ! isset( $_GET['payment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['payment_nonce'] ) ), 'charity-nonce' ) ) {
	wp_send_json_error( 'bad_nonce' );
	wp_die();
} else {
	if( isset( $_GET['PayerID'] ) && !empty( $_GET['PayerID'] ) ){

			
	}
}
?>

<?php
get_footer();