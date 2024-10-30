<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	$cdofwc_class_columns = array(
		'charity-class-condition'      	=> __( 'Condition' , 'charity-donation-offers-for-woocommerce' ),
		'charity-class-amount'        	=> __( 'Amount', 'charity-donation-offers-for-woocommerce' ),
		'charity-class-donation'       	=> __( 'Donation', 'charity-donation-offers-for-woocommerce' ),
		'charity-class-action'        	=> __( 'Action', 'charity-donation-offers-for-woocommerce' ),
	);

	$charity_pruduct = get_option('charity_charity_product');
	$charity_pruduct = ( $charity_pruduct ) ? $charity_pruduct : '';

	$charity_rules = get_option('charity_donation_rules');

	// Default arguments
	$args = array(
	    'status'            => array( 'private', 'publish' ),
	    'type'              => array(  'cdofwc-charity' ),
	    'page'              => -1,
	    'orderby'           => 'date',
	    'order'             => 'DESC',
	    'return'            => 'objects',
	);

	// Array of product objects
	$products = wc_get_products( $args );

	

?>

<table class="form-table">
	<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Select Charity Product', 'charity-donation-offers-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox ">
				<fieldset>
					<label for="charity_enable_options">
						<select id="charity-product" name="charity-product" required>
							<?php 
							// Loop through list of products
							foreach( $products as $product ) {

							    // Collect product variables
							    $product_id   = $product->get_id();
							    $product_name = $product->get_name();

							    // Output product ID and name
							    echo '<option value="' . esc_attr( $product_id ) . '" '.selected( $charity_pruduct, $product_id, false ).'>' . esc_attr( $product_name ) . '</option>';

							}
							?>
						</select>
					</label>
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>


<h2>
	<?php esc_html_e( 'Charity Rule', 'charity-donation-offers-for-woocommerce' ); ?>
	<?php echo wc_help_tip( __( 'Add rules for charity', 'charity-donation-offers-for-woocommerce' ) ); // @codingStandardsIgnoreLine ?>
</h2>


<table class="wc-shipping-classes widefat" id="charity_dynamic_field">
	<thead>
		<tr>
			<?php foreach ( $cdofwc_class_columns as $class => $heading ) : ?>
				<th class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $heading ); ?></th>
			<?php endforeach; ?>
		</tr>
	</thead>
	
	<tbody class="wc-shipping-class-rows">

		<?php  if(!empty($charity_rules)) { 

			$itr = 1;
			foreach ($charity_rules as $key => $value) :
				?>
				<tr class="rules-items" id="row<?php echo sanitize_key( $key ); ?>">
					<td><select id="rules-select" name="rule[<?php echo sanitize_key( $key ); ?>][condition]" required>
					    <!-- <option hidden disabled selected>Please select condition</option> -->
					    <option value="greater" <?php selected( $value['condition'], 'greater', false ); ?>><?php esc_html_e( 'Cart Subtotal Greater Then', 'charity-donation-offers-for-woocommerce' ); ?></option>
					</select>
		            <td><input type="number" name="rule[<?php echo sanitize_key( $key ); ?>][amount]" placeholder="Enter your amount" class="form-control amount_list" value="<?php echo esc_html( $value['amount'] ); ?>" required/></td>
		            <td><input type="number" name="rule[<?php echo sanitize_key( $key ); ?>][donation]" placeholder="Enter your donation" class="form-control amount_list padding-8 margin-none" value="<?php echo esc_html( $value['donation'] ); ?>" required/></td>
		            <?php if($itr > 1){ ?>
		            	<td><button type="button" name="remove" id="<?php echo sanitize_key( $key ); ?>" class="button-link-delete btn_remove"><span class="dashicons dashicons-no-alt"></span></button></td>
		        <?php }else{ echo ' <td></td>';} ?>
		        </tr>

		 		<?php
			$itr++;
            endforeach;
			}else{ 
				?>
				<tr class="rules-items" id="row0>">
					<td><select id="rules-select" name="rule[0][condition]" required>
					    <!-- <option hidden disabled selected>Please select condition</option> -->
					    <option value="greater"><?php esc_html_e( 'Cart Subtotal Greater Then', 'charity-donation-offers-for-woocommerce' ); ?></option>
					</select>
		            <td><input type="number" name="rule[0][amount]" placeholder="Enter your amount" class="form-control amount_list" required/></td>
		            <td><input type="number" name="rule[0][donation]" placeholder="Enter your donation" class="form-control amount_list" required/></td>
		            <td></td>
		        </tr>
    	<?php  } ?>
	</tbody>

	<tfoot>
		<tr>
			<td colspan="<?php echo absint( count( $cdofwc_class_columns ) ); ?>">
				<button type="button" name="add" id="add" class="button button-secondary "><?php esc_html_e( 'Add Rule', 'charity-donation-offers-for-woocommerce' ); ?></button>
			</td>
		</tr>
	</tfoot>

</table>