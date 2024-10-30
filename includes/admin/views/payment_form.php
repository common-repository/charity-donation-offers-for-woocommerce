<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$show_form = false;
$error     = false;

if ( ! isset( $_GET['my_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['my_nonce'] ) ), 'charity-nonce' ) ) {
	wp_send_json_error( 'bad_nonce' );
	wp_die();
} else {

	if ( isset( $_GET['order_id'] ) && ! empty( $_GET['order_id'] ) ) {
		
		$number = ! empty( $_GET[ 'order_id' ] ) ? absint( $_GET[ 'order_id' ] ) : '';
		$currentOrderId = $number;

		$order      = wc_get_order( $currentOrderId );
		$is_charity = $order->get_meta( '_is_charity_order', true );

		if ( ! empty( $is_charity ) ) {

			$amount             = $order->get_meta( '_charity_product_amount', true );
			$charity_product_id = $order->get_meta( '_charity_product_id', true );
			$charity_email      = get_post_meta( $charity_product_id, 'charity_paypal_email', true );

			$product = wc_get_product( $charity_product_id );

			$donateAmount = '';
			$paypal_email = '';

			if ( ! empty( $amount ) && $amount > 0 ) {
				$donateAmount = $amount;
				$show_form    = true;
			} else {
				$error = true;
			}

			if ( $charity_email != '' && ! empty( $charity_email ) ) {
				$paypal_email = $charity_email;
				$show_form    = true;
			} else {
				$error = true;
			}
		}
	}
}


	$mode = get_option( 'charity_paypal_mode_option' );

	$enableSandbox = true;
	$paypalUrl     = ( $mode == 'test_mode' ) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
if ( $show_form && ! $error ) {

	$symbols = get_woocommerce_currency_symbols();
	$currency = get_woocommerce_currency();

	$currencySign = $symbols[ $currency ];

	$od_number = ! empty( $_GET[ 'order_id' ] ) ? absint( $_GET[ 'order_id' ] ) : '';

	$return = wp_nonce_url( site_url( 'donation-thankyou?order_id=' . $od_number , 'https' ), 'charity-nonce', 'payment_nonce' );
	$cancel = wp_nonce_url( site_url( 'donation-cancel?order_id=' . $od_number , 'https' ), 'charity-nonce', 'payment_nonce' );

	/*$return = site_url( 'donation-thankyou', 'https' );
	$cancel = site_url( 'donation-cancel', 'https' );*/
	?>

	<div class="wrap">
		<h1 class=""><?php esc_html_e( 'Charity Donation', 'charity-donation-offers-for-woocommerce' ); ?></h1>
		
		<div class="donate_form">

		<form action="<?php echo esc_url( $paypalUrl ); ?>" method="post" id="once">
			<!-- Identify your business so that you can collect the payments. -->
			<input type="hidden" name="business" value="<?php echo  esc_attr( sanitize_email( $paypal_email ) ); ?>" / >

			<!-- Specify a Donate button. -->
			<input type="hidden" name="cmd" value="_xclick"> <!-- // _xclick // _donations -->
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="lc" value="UK" />
			<input type="hidden" name="tx" value="">
			<input type='hidden' name='rm' value='2'>
			<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest" />
			<!-- Specify details about the contribution -->
			
			<input type="hidden" name="payer_email" value="customer@example.com" />
			<input type="hidden" name="currency_code" value="<?php echo esc_html( get_woocommerce_currency() ); ?>">
			<input type="hidden" name="charset" value="utf-8">

			<input type="hidden" name="amount" value="<?php echo absint( $amount ); ?>" / >
			<input type="hidden" name="custom" value="<?php echo absint( $currentOrderId ); ?>" / >
			
			<input type="hidden" name="return" value="<?php echo esc_url( $return ); ?>" / >
			<input type="hidden" name="cancel_return" value="<?php echo esc_url( $cancel ); ?>" / >
			<input type="hidden" name="notify_url" value="" / >
				

			<div class="row">
				<div class="col-xs-12">
					<div class="description"><?php esc_html_e( 'Thank you for your donation!', 'charity-donation-offers-for-woocommerce' ); ?></div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="body">

						<div class="input">
							<input type="number" name="amount" value="<?php echo absint( $amount ); ?>">
							<div class="addition"><?php echo esc_html( get_woocommerce_currency() ); ?></div>
						</div>
						<div class="info"><?php echo esc_html( $product->get_name() ); ?></div>
						<div class="donate_buttons">
							<!-- Display the payment button. -->
							<input type="submit" value="Donate by Paypal" alt="Donate by Paypal" class="btn_styles">
						</div>
					</div>
				</div>
			</div>
		</form>
		</div>

	</div>
	<?php
} else {

	?>
		<div class="error notice is-dismissable">
			<p><?php esc_html_e( 'Please check charity paypal email and donation amount.', 'charity-donation-offers-for-woocommerce' ); ?></p>
		</div>
		<?php

		$slug = 'payment';
		$url  = 'edit.php?post_type=shop_order';

		echo '<p><a class="button wc-action-button wc-action-button' . esc_attr( $slug ) . ' ' .  esc_attr( $slug ) . '" href="' . esc_url( $url ) . '" aria-label="' .  esc_attr( $slug ) . '"> Return To Order </a></p>';
}
?>