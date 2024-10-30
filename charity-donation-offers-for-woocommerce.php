<?php

/**
 * Plugin Name:       Charity Donation Offers for WooCommerce
 * Description:       Charity Donation Offers for Woocommerce is a simple, lightweight plugin that allows store owners to easily offer charity donations as an alternative to discounts or free shipping.
 * Plugin URI:        https://purelyplugins.com/charity-donation-offers-plugin/
 * Version:           1.1
 * Author:            Purely Plugins
 * Author URI:        https://purelyplugins.com/
 * Text Domain:       charity-donation-offers-for-woocommerce
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Requires at least: 6.4
 * Tested up to: 6.6.2
 * Requires PHP: 7.4
 *
 * WC requires at least: 5.3
 * WC tested up to: 9.3.1
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CDOFWC_PLUGIN_FILE', __FILE__ );
define( 'CDOFWC_VERSION', '1.1' ); // CDOFWC: DEFINED_VERSION.
define( 'CDOFWC_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'CDOFWC_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

require_once 'class/class-cdofwc.php';
require_once 'class/class-cdofwc-template.php';
require_once 'class/class-cdofwc-helper.php';

/**
 * Main instance of CDOFWC.
 *
 * Returns the main instance of CDOFWC to prevent the need to use globals.
 *
 * @since  1.1
 * @return CDOFWC
 */
function CDOFWC() { 
	return CDOFWC::instance();
}

add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Initiate the plugin.
CDOFWC();