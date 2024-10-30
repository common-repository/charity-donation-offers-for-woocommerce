<?php
use Automattic\WooCommerce\Utilities\OrderUtil;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 
 */
class CDOFWC_Helper
{
	
	public function __construct()
	{
		add_filter( 'woocommerce_reports_get_order_report_data', array($this, 'cdofwc_get_order_report_data_cb' ), 99, 2 );
		add_filter('woocommerce_order_get_subtotal', array($this, 'cdofwc_order_get_subtotal_cb' ), 99, 2 );
		add_filter('woocommerce_order_get_subtotal', array($this, 'cdofwc_order_get_subtotal_cb' ), 1, 2 );
		add_action( 'admin_init', array($this, 'cdofwc_admin_init_cb' ) );
		add_action( 'woocommerce_order_status_changed', array($this, 'cdofwc_get_order_old_status' ), 10, 4 );
		
		/* Add In Admin Email */
		// add_filter( 'woocommerce_get_order_item_totals', array($this, 'cdofwc_get_order_item_totals_cb' ) , 10 , 3 );

		/* Analytics Revenue */
		add_filter( 'woocommerce_analytics_revenue_select_query', array($this, 'cdofwc_get_order_report_revenue_data_cb' ), 99, 2 );
	}
	
	public function cdofwc_get_order_report_revenue_data_cb( $results , $args ){
		global $wpdb;
		$table_name = $wpdb->prefix.'wc_order_stats';
		$date_column_name = 'date_created';
		$sql_datetime_format = 'Y-m-d H:i:s';

		$order_stats_sql = "SELECT * FROM $table_name WHERE ";

		$before_date = $args['before'];
		$after_date = $args['after'];
		$interval_date = $args['interval'];

		if ( isset( $before_date ) && '' !== $before_date ) {
			$datetime_str = gmdate( $sql_datetime_format, strtotime($before_date));
			$order_stats_sql .= $wpdb->prepare(" %i <= %s", $date_column_name, $datetime_str);
		}

		if ( isset( $after_date ) && '' !== $after_date ) {
			
			$datetime_str = gmdate( $sql_datetime_format, strtotime($after_date));
			if($before_date){
				$order_stats_sql .= " AND";	
			}
			 
			$order_stats_sql .= $wpdb->prepare(" %i >= %s", $date_column_name, $datetime_str);
		}

		$order_stats_data = $wpdb->get_results( $wpdb->prepare( "%s" , $order_stats_sql ) );
			

		if($order_stats_data){
			$order_net_total = [];
			foreach($order_stats_data as $order_stats){
				$order_id = $order_stats->order_id;
				$is_charity_amount = get_post_meta($order_id, '_charity_product_amount', true);
				if($is_charity_amount){
					$order_stats_net_amount = $order_stats->net_total - $is_charity_amount;
					$order_net_total[] = $order_stats_net_amount;
				}else{
					$order_net_total[] = $order_stats->net_total;
				}
			}
		}

		if($order_net_total){
			$order_net_total_sum = array_sum($order_net_total);
			$results->totals->net_revenue = $order_net_total_sum;
		}

		// $expected_interval_count = TimeInterval::intervals_between( $query_args['after'], $query_args['before'], $query_args['interval'] );

		return $results;
	}

	

	public function cdofwc_get_order_report_data_cb( $results , $data ){
		if( isset( $data['_line_total']['name'] ) && 'order_item_amount' == $data['_line_total']['name'] ){
			if( isset( $data['_line_total']['product_id'] ) ){
				$product_id = $data['_line_total']['product_id'];
				$product = wc_get_product( $product_id );
				$product_type = $product->get_type();
				
				if( "cdofwc-charity" == $product_type && !is_array( $results ) ){
					$results = $results*-1;
				}
			}
		}
		//die;
		return $results;
	}


	public function cdofwc_order_get_subtotal_cb( $subtotal, $order  ){

		$charity_amount = $order->get_meta( '_charity_product_amount', true );
		if( !empty( $charity_amount ) && $charity_amount > 0 ){
			$subtotal = $subtotal + $charity_amount;
			// $subtotal = $order->get_total();
		}
		return $subtotal;
	}

	public function cdofwc_admin_init_cb() {

		add_filter( 'woocommerce_bulk_action_ids',  array( $this, 'cdofwc_bulk_process_custom_status' ), 10, 2 );

		add_filter('woocommerce_order_get_total', array( $this, 'cdofwc_order_get_total_cb' ), 99, 2 );

		add_filter( 'woocommerce_get_order_item_totals', array($this, 'cdofwc_get_order_item_totals_cb' ) , 10 , 3 );
	}

	public function cdofwc_bulk_process_custom_status( $object_ids, $doaction ){

		if( 'mark_processing' === $doaction ) {

			foreach ( $object_ids as $order_id ) {
				$order = wc_get_order( $order_id );

				$charity_amount = $order->get_meta( '_charity_product_amount', true );
				$cdofwc_total_amount = $order->get_meta( '_cdofwc_order_total_amount', true );

				if( !empty( $cdofwc_total_amount ) && $cdofwc_total_amount > 0 ){
					// Set the new calculated total
    				$order->set_total( $cdofwc_total_amount );
    				$order->update_status( 'wc-processing' );
				}

			}
		}

		if( 'mark_completed' === $doaction ) {

			foreach ( $object_ids as $order_id ) {
				$order = wc_get_order( $order_id );

				$charity_amount = $order->get_meta( '_charity_product_amount', true );
				$cdofwc_total_amount = $order->get_meta( '_cdofwc_order_total_amount', true );

				if( !empty( $cdofwc_total_amount ) && $cdofwc_total_amount > 0 ){
					// Set the new calculated total
    				$order->set_total( $cdofwc_total_amount );
    				$order->update_status( 'wc-completed' );
				}

			}

		}

		if( 'mark_on-hold' === $doaction ) {
			
			foreach ( $object_ids as $order_id ) {
				$order = wc_get_order( $order_id );

				$charity_amount = $order->get_meta( '_charity_product_amount', true );
				$cdofwc_total_amount = $order->get_meta( '_cdofwc_order_total_amount', true );

				if( !empty( $cdofwc_total_amount ) && $cdofwc_total_amount > 0 ){
					// Set the new calculated total
    				$order->set_total( $cdofwc_total_amount );
    				$order->update_status( 'wc-on-hold' );
				}

			}

		}

		// wp_safe_redirect( $redirect_to );
		return $object_ids;
	}

	public function cdofwc_order_get_total_cb( $total, $order ){

		if( $order->get_meta('_is_charity_order') == 'yes' ){

			$cdofwc_total_amount = $order->get_meta( '_cdofwc_order_total_amount', true );
			$charity_amount 	 = $order->get_meta( '_charity_product_amount', true );
			$order->set_total( $cdofwc_total_amount );

			if( $order->get_status() == 'completed' ){
				
				if( !empty( $charity_amount ) && $charity_amount > 0 ){
					$total = $cdofwc_total_amount;
					return $total;
				}
			}

			if( $order->get_status() == 'refunded' ){

				if( !empty( $charity_amount ) && $charity_amount > 0 ){
					$total = $cdofwc_total_amount;
					return $total;
				}
			}

			$orderStatus = get_post_meta( $order->get_id(), '_old_status', true);
			if( $orderStatus == 'on-hold' ) { 
				
				if( !empty( $charity_amount ) && $charity_amount > 0 ){
					$total = $cdofwc_total_amount;
					return $total;
				}
			}
			
			if( $orderStatus == 'pending' && $order->get_status() == 'processing' &&  $order->get_payment_method() == 'bacs' ) { 
				
				if( !empty( $charity_amount ) && $charity_amount > 0 ){
					$total = $cdofwc_total_amount;
					return $total;
				}
			}

			if( $orderStatus == 'pending' && $order->get_status() == 'processing' &&  $order->get_payment_method() == 'cheque' ) { 
				
				if( !empty( $charity_amount ) && $charity_amount > 0 ){
					$total = $cdofwc_total_amount;
					return $total;
				}
			}

			if( $orderStatus == 'on-hold' && $order->get_status() == 'processing' ) { 
				
				if( !empty( $charity_amount ) && $charity_amount > 0 ){
					$total = $cdofwc_total_amount;
					return $total;
				}
			}
		}

		return $total;
	}



	public function cdofwc_get_order_old_status( $order_id, $status_from, $status_to, $order ) {

	   if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS is enabled.
			$order          = wc_get_order( $order_id );
			$order->update_meta_data( '_old_status', $status_from );
		} else {
			// CPT-based orders are in use.
			update_post_meta( $order_id, '_old_status', $status_from );
		}
	}


	public function cdofwc_get_order_item_totals_cb($total_rows, $order, $tax_display ){

		if( $order->get_meta('_is_charity_order') == 'yes' ){

			$subtotal 			= $order->get_subtotal();
			$display_subtotal 	= $order->get_subtotal_to_display();
			$total 				= $order->get_total();
			$formated_total 	= $order->get_formatted_order_total();
			$charity_amount 	= $order->get_meta( '_charity_product_amount', true );

			if( $order->get_shipping_total() == 0 ){
				$total_rows['cart_subtotal']['value'] =  $total_rows['order_total']['value'];
			}else{

				if( $order->get_status() == 'completed' ){
					
					if( !empty( $charity_amount ) && $charity_amount > 0 ){
						$total_rows['cart_subtotal']['value'] = wc_price( $subtotal );
						return $total_rows;
					}
				}

				if( $order->get_status() == 'refunded' ){
					
					if( !empty( $charity_amount ) && $charity_amount > 0 ){
						$total_rows['cart_subtotal']['value'] = wc_price( $subtotal );
						return $total_rows;
					}
				}

				$orderStatus = get_post_meta( $order->get_id(), '_old_status', true);

				if( $orderStatus == 'on-hold' ) { 
					
					if( !empty( $charity_amount ) && $charity_amount > 0 ){
						$total_rows['cart_subtotal']['value'] = wc_price( $subtotal );
						return $total_rows;
					}
				}

				if( $orderStatus == 'pending' && $order->get_status() == 'processing' &&  $order->get_payment_method() == 'cheque' ) { 
					
					if( !empty( $charity_amount ) && $charity_amount > 0 ){
						$total_rows['cart_subtotal']['value'] = wc_price( $subtotal );
						return $total_rows;
					}
				}

				if( $orderStatus == 'pending' && $order->get_status() == 'processing' &&  $order->get_payment_method() == 'bacs' ) { 
					
					if( !empty( $charity_amount ) && $charity_amount > 0 ){
						$total_rows['cart_subtotal']['value'] = wc_price( $subtotal );
						return $total_rows;
					}
				}

				if( $orderStatus == 'on-hold' && $order->get_status() == 'processing' ) { 
					
					if( !empty( $charity_amount ) && $charity_amount > 0 ){
						$total_rows['cart_subtotal']['value'] = wc_price( $subtotal );
						return $total_rows;
					}
				}
			}
			

		}

		return $total_rows;
	}
}


$CDOFWC_Helper = new CDOFWC_Helper();