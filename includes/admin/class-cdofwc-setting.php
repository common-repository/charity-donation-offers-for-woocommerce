<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'CDOFWC_Setting' ) ) :

/**
 * Settings class
 *
 * @since 1.0
 */
class CDOFWC_Setting extends WC_Settings_Page {

    /**
	 * Setup settings class
	 *
	 * @since  1.0
	 */
	public function __construct() {
	        
	    $this->id    = 'charity';
	    $this->label = __( 'Charity Donation', 'charity-donation-offers-for-woocommerce' );
	            
	    add_filter( 'woocommerce_settings_tabs_array',        array( $this, 'add_settings_page' ), 20 );
	    add_action( 'woocommerce_settings_' . $this->id,      array( $this, 'output' ) );
	    add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	    
	    // only add this if you need to add sections for your settings tab
	    add_action( 'woocommerce_sections_' . $this->id,      array( $this, 'output_sections' ) );
	}

	public function get_sections() {
        
	    $sections = array(
	        ''         => __( 'Charity options', 'charity-donation-offers-for-woocommerce' ),
	        'rule' => __( 'Charity Rule', 'charity-donation-offers-for-woocommerce' ),
	        'message_setting' => __( 'Message', 'charity-donation-offers-for-woocommerce' ),
	        //'payment_setting' => __( 'Payment', 'charity-donation-offers-for-woocommerce' ),
	    );
	            
	    return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array
	 *
	 * @since 1.0
	 * @param string $current_section Optional. Defaults to empty string.
	 * @return array Array of settings
	 */
	public function get_settings( $current_section = '' ) {
			
		if ( 'rule' == $current_section ) {
					
				include __DIR__ . '/views/settings-rule.php';

			/**
			 * Filter Plugin Section 2 Settings
			 *
			 * @since 1.0
			 * @param array $settings Array of the plugin settings
			 */
			
			$settings = array();
					
		} elseif( 'message_setting' == $current_section ){

			/**
			 * Filter Plugin Section 1 Settings
			 *
			 * @since 1.0
			 * @param array $settings Array of the plugin settings
			 */
			$settings = apply_filters( 'charity_message_option_settings', array(
				
				array(
					'title' => __( 'Message Setting', 'charity-donation-offers-for-woocommerce' ),
					'type'  => 'title',
					'id'    => 'charity_message_options',
				),

				array(
					'title'       => __( 'Title', 'charity-donation-offers-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'charity-donation-offers-for-woocommerce' ),
					'default'     => __( 'Charity Title', 'charity-donation-offers-for-woocommerce' ),
					'desc_tip'    => true,
					'id'   	      => 'charity_message_option_title',
				),

				array(
					'title'       => __( 'Message', 'charity-donation-offers-for-woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Text to appear below the main email content.', 'charity-donation-offers-for-woocommerce' ),
					'default'     => __( 'If you spend more than {condition} then we will donate {donation} to {charityName}.', 'charity-donation-offers-for-woocommerce' ),
					'css'         => 'width:400px; height: 75px;',
					'desc_tip'    => true,
					'placeholder' => __( 'N/A', 'charity-donation-offers-for-woocommerce' ),
					'id'   	      => 'charity_message_content',
				),

				array(
					'title'           => __( 'Display Message', 'charity-donation-offers-for-woocommerce' ),
					'desc'            => __( 'Enable/Disable', 'charity-donation-offers-for-woocommerce' ),
					'id'              => 'charity_message_show_enable',
					'default'         => 'yes',
					'type'            => 'checkbox',
					'checkboxgroup'   => 'start',
					'show_if_checked' => 'option',
				),

				array(
					'title'           => __( 'Display on Single Product', 'charity-donation-offers-for-woocommerce' ),
					'desc'            => __( 'Enable/Disable', 'charity-donation-offers-for-woocommerce' ),
					'id'              => 'charity_message_enable_single_product',
					'default'         => 'yes',
					'type'            => 'checkbox',
				),

				array(
					'title'    => __( 'Single Product', 'charity-donation-offers-for-woocommerce' ),
					'desc'     => __( 'This controls the position of the message on single page.', 'charity-donation-offers-for-woocommerce' ),
					'id'       => 'charity_message_show_on_single_product',
					'class'    => 'wc-enhanced-select manage_stock_field',
					'default'  => 'left',
					'type'     => 'select',
					'options'  => array(
						'woocommerce_before_add_to_cart_button' => __( 'Before Add to Cart', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_after_add_to_cart_button' => __( 'After Add to Cart', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_before_single_product' => __( 'Before Single Product', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_before_single_product_summary'  => __( 'Before Product Summary', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_after_single_product_summary'=> __( 'After Product Summary', 'charity-donation-offers-for-woocommerce' ),
						//'woocommerce_after_single_product' => __( 'After Single Product', 'charity-donation-offers-for-woocommerce' ),
					),
					'desc_tip' => true,
				),

				array(
					'title'           => __( 'Display On Cart', 'charity-donation-offers-for-woocommerce' ),
					'desc'            => __( 'Enable/Disable', 'charity-donation-offers-for-woocommerce' ),
					'id'              => 'charity_message_enable_cart',
					'default'         => 'yes',
					'type'            => 'checkbox',
				),

				array(
					'title'    => __( 'Cart Page', 'charity-donation-offers-for-woocommerce' ),
					'desc'     => __( 'This controls the position of the message on cart page.', 'charity-donation-offers-for-woocommerce' ),
					'id'       => 'charity_message_show_on_cart',
					'class'    => 'wc-enhanced-select',
					'default'  => 'left',
					'type'     => 'select',
					'options'  => array(
						'woocommerce_before_cart_table' => __( 'Before Cart Table', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_after_cart_table'  => __( 'After Cart Table', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_before_cart_totals'=> __( 'Before Cart Total', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_after_cart_totals' => __( 'After Cart Total', 'charity-donation-offers-for-woocommerce' ),
						// 'woocommerce_after_cart' => __( 'After Cart', 'charity-donation-offers-for-woocommerce' ),
					),
					'desc_tip' => true,
				),

				array(
					'title'           => __( 'Display On Checkout', 'charity-donation-offers-for-woocommerce' ),
					'desc'            => __( 'Enable/Disable', 'charity-donation-offers-for-woocommerce' ),
					'id'              => 'charity_message_enable_checkout',
					'default'         => 'yes',
					'type'            => 'checkbox',
				),

				array(
					'title'    => __( 'Checkout Page', 'charity-donation-offers-for-woocommerce' ),
					'desc'     => __( 'This controls the position of the message on cart page.', 'charity-donation-offers-for-woocommerce' ),
					'id'       => 'charity_message_show_on_checkout',
					'class'    => 'wc-enhanced-select',
					'default'  => 'left',
					'type'     => 'select',
					'options'  => array(
						'woocommerce_before_checkout_form' => __( 'Before Checkout Form', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_checkout_after_customer_details'  => __( 'After Customer Details', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_checkout_before_order_review'=> __( 'Before Order Review', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_review_order_before_payment' => __( 'Before Payment', 'charity-donation-offers-for-woocommerce' ),
						'woocommerce_checkout_after_order_review' => __( 'After Order Review', 'charity-donation-offers-for-woocommerce' ),
					),
					'desc_tip' => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'charity_message_options'
				),

			) );

		}elseif( 'payment_setting' == $current_section ){


		}else {
					
			/**
			 * Filter Plugin Section 1 Settings
			 *
			 * @since 1.0
			 * @param array $settings Array of the plugin settings
			 */
			$settings = apply_filters( 'charity_option_settings', array(
				
				array(
					'title' => __( 'Charity options', 'charity-donation-offers-for-woocommerce' ),
					'type'  => 'title',
					'id'    => 'charity_options',
				),

				array(
					'title'   => __( 'Enable/Disable', 'charity-donation-offers-for-woocommerce' ),
					'type'    => 'checkbox',
					'class'   => 'wppd-ui-toggle',
					'label'   => __( 'Enable Charity Donation', 'charity-donation-offers-for-woocommerce' ),
					'default' => 'no',
					'id'   	  => 'charity_enable_options',
				),

				array(
					'title'    => __( 'Paypal Mode', 'charity-donation-offers-for-woocommerce' ),
					'desc'     => __( 'This controls the position of the message on single page.', 'charity-donation-offers-for-woocommerce' ),
					'id'       => 'charity_paypal_mode_option',
					'class'    => 'wc-enhanced-select manage_stock_field',
					'default'  => 'test_mode',
					'type'     => 'select',
					'options'  => array(
						'test_mode' => __( 'SandBox Mode( Test )', 'charity-donation-offers-for-woocommerce' ),
						'live_mode'  => __( 'Live Mode', 'charity-donation-offers-for-woocommerce' ),
					),
					'desc_tip' => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'charity_options'
				),
				
			) );
				
		}


		/**
		 * Filter MyPlugin Settings
		 *
		 * @since 1.0
		 * @param array $settings Array of the plugin settings
		 */
		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
				
	}

	/**
	 * Output the settings
	 *
	 * @since 1.0
	 */
	public function output() {
	        
	    global $current_section;
	    
	    if ( 'rule' === $current_section ) {
			
			include __DIR__ . '/views/settings-rule.php';

		} elseif( 'message_setting' === $current_section ) {
			
			$settings = $this->get_settings( $current_section );
	    	WC_Admin_Settings::output_fields( $settings );

		} else {
			$settings = $this->get_settings( $current_section );
	    	WC_Admin_Settings::output_fields( $settings );
		}

	    


	}

	/**
	 * Save settings
	 *
	 * @since 1.0
	 */
	public function save() {
	        
	    global $current_section;
	    
	    if ( 'rule' === $current_section ) {
			
			$this->save_charity_rule();
			
		} elseif( 'message_setting' === $current_section ) {
			$settings = $this->get_settings( $current_section );
	    	WC_Admin_Settings::save_fields( $settings );
		}else {
			$settings = $this->get_settings( $current_section );
	    	WC_Admin_Settings::save_fields( $settings );
		}
	    
	}
	
	public function save_charity_rule(){


		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-settings' ) ) {
			return;
		}

		if( isset( $_POST['charity-product'] ) && !empty( $_POST['charity-product'] ) ){
			$option_name = 'charity_charity_product' ;
        	$new_donation = sanitize_text_field($_POST['charity-product']);

        	if ( get_option( $option_name ) !== false  ) {

        		update_option( $option_name, $new_donation );
        	}else{

        		$deprecated = null;
	            $autoload = 'no';
	            add_option( $option_name, $new_donation, "", $autoload );

        	}
		}
		if( isset( $_POST['rule'])){
			
			if ( isset( $_POST['rule'] ) && is_array( $_POST['rule'] ) ) {
				$option_name = 'charity_donation_rules' ;
	        	// $new_rule = array_values( array_filter( $_POST['rule']) );
	        	$new_rule = $this->cdofw_sanitize_multidimensional_array($_POST['rule']);

		        if ( get_option( $option_name ) !== false  ) {

	        		update_option( $option_name, $new_rule );
	        	}else{

	        		$deprecated = null;
		            $autoload = 'no';
		            add_option( $option_name, $new_rule, "", $autoload );

	        	}
			}
			
		}
	}

	// Function to sanitize and validate the multidimensional array
	public function cdofw_sanitize_multidimensional_array($array) {

	    $sanitized_array = array();

	    foreach ($array as $inner_array) {
	        $sanitized_inner_array = array();
	        foreach ($inner_array as $key => $value) {
	            switch ($key) {
	                case 'condition':
	                    $sanitized_inner_array[$key] = sanitize_text_field($value); // Sanitize string
	                    break;
	                case 'amount':
	                    $sanitized_inner_array[$key] = absint($value); // Sanitize email
	                    break;
	                case 'donation':
	                    $sanitized_inner_array[$key] = absint($value); // Sanitize integer
	                    break;
	                // Add more cases for additional fields if needed
	                default:
	                    // By default, just keep the original value
	                    $sanitized_inner_array[$key] = $value;
	                    break;
	            }
	        }
	        $sanitized_array[] = $sanitized_inner_array;
	    }
	    return $sanitized_array;
	}

}

endif;

new CDOFWC_Setting();