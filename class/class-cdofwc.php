<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CDOFWC' ) ) :

	/**
	 * Base Plugin class holding generic functionality
	 */
	final class CDOFWC {


		/**
		 * Set the minimum required versions for the plugin.
		 */
		const PLUGIN_REQUIREMENTS = array(
			'php_version' => '7.4',
			'wp_version'  => '6.4',
			'wc_version'  => '5.3',
			// 'action_scheduler' => '3.3.0',
		);

		/**
		 * CDOFWC version.
		 *
		 * @var string
		 */
		public $version;

		/**
		 * The single instance of the class.
		 *
		 * @var CDOFWC
		 * @since 1.0
		 */
		protected static $instance = null;

		/**
		 * The initialized state of the class.
		 *
		 * @var CDOFWC
		 * @since 1.0
		 */
		protected static $initialized = false;

		// For testing purposes
		public function get_cdofwc_version() {
			return CDOFWC_VERSION;
		}

		/**
		 * Main CDOFWC Instance.
		 *
		 * Ensures only one instance of CDOFWC is loaded or can be loaded.
		 *
		 * @since 1.0
		 * @static
		 * @see CDOFWC()
		 * @return CDOFWC - Main instance.
		 */
		public static function instance() {

			if ( is_null( self::$instance ) ) {

				self::$instance = new self();
				self::$instance->cdofwc_init_plugin();
			}
			return self::$instance;
		}

		/**
		 * CDOFWC Initializer.
		 */
		public function cdofwc_init_plugin() {

			if ( self::$initialized ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'Only a single instance of this class is allowed.', 'charity-donation-offers-for-woocommerce' ), '1.0' );
				return;
			}

			self::$initialized = true;

			$this->define_constants();

			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'cdofw_plugin_add_settings' ) );

			register_activation_hook( CDOFWC_PLUGIN_FILE, array( $this, 'cdofw_create_template_page' ) );
			register_deactivation_hook( CDOFWC_PLUGIN_FILE, array( $this, 'cdofw_remove_template_page' ) );
		}

		public function cdofw_get_post_id_by_name( $post_name ) {

		    global $wpdb;
		    $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s", $post_name ) );
		    return $post_id;
		}

		/**
		 * Delete Thank you template  dofw_remove_template_page Constants.
		 */

		public function cdofw_remove_template_page() {


			$cancel_id = $this->cdofw_get_post_id_by_name( 'donation-cancel' );
			if ( $cancel_id ) {
			    wp_delete_post( $cancel_id, true ); // true to permanently delete
			}

			$thankyou_id = $this->cdofw_get_post_id_by_name( 'donation-thankyou' );
			if ( $thankyou_id ) {
			    wp_delete_post( $thankyou_id, true ); // true to permanently delete
			}

		}

		/**
		 * Create Thank you template  cdofw_create_template_page Constants.
		 */
		public function cdofw_create_template_page() {

			wp_insert_term(
				'Charity Donation',
				'product_cat',
				array(
					'description' => 'Description for category', // optional
					'parent'      => 0, // optional
					'slug'        => 'charity-donation', // optional
				)
			);

			$page_titles = array(
				'Donation Thankyou' => 'template-charity-thankyou.php',
				'Donation Cancel'   => 'template-charity-cancel.php',
			);

			foreach ( $page_titles as  $title => $page_template ) {

				   // Create post object
					$new_page_args = array(
						'post_title'    => wp_strip_all_tags( $title ),
						'post_content'  => '',
						'post_status'   => 'publish',
						'post_author'   => 1,
						'post_type'     => 'page',
						'page_template' => $page_template,

					);

					// Insert the post into the database
					wp_insert_post( $new_page_args );

			}

		}


		/**
		 * Define Pinterest_For_Woocommerce Constants.
		 */
		private function define_constants() {

			define( 'CDOFWC_PREFIX', 'cdofwc-prefix' );
			define( 'CDOFWC_PLUGIN_BASENAME', plugin_basename( CDOFWC_PLUGIN_FILE ) );
			define( 'CDOFWC_OPTION_NAME', 'cdofwc_option' );
			define( 'CDOFWC_DATA_NAME', 'cdofwc_data' );
			define( 'CDOFWC_LOG_PREFIX', 'cdofwc-log' );

		}

		/**
		 * Include plugins files and hook into actions and filters.
		 *
		 * @since  1.0
		 */
		public function init_plugin() {

			if ( ! $this->check_plugin_requirements() ) {
				return;
			}

			$this->includes();

		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		private function includes() {

			if ( $this->is_request( 'admin' ) ) {
				include_once CDOFWC_PLUGIN_DIR_PATH . 'includes/admin/class-cdofwc-admin.php';

			}

			if ( $this->is_request( 'frontend' ) ) {
				include_once CDOFWC_PLUGIN_DIR_PATH . 'includes/frontend/class-cdofwc-frontend.php';
			}

			include CDOFWC_PLUGIN_DIR_PATH . 'includes/admin/class-cdofwc-cpt.php';
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function cdofw_plugin_add_settings( $settings ) {

			$settings[] = include CDOFWC_PLUGIN_DIR_PATH . 'includes/admin/class-cdofwc-setting.php';

			return $settings;
		}

		/**
		 * What type of request is this?
		 *
		 * @param  string $type admin, ajax, cron or frontend.
		 * @return bool
		 */
		private function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin();
				case 'ajax':
					return defined( 'DOING_AJAX' );
				case 'cron':
					return defined( 'DOING_CRON' );
				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}


		/**
		 * Checks all plugin requirements. If run in admin context also adds a notice.
		 *
		 * @return boolean
		 */
		public function check_plugin_requirements() {

			$errors = array();
			global $wp_version;

			if ( ! version_compare( PHP_VERSION, self::PLUGIN_REQUIREMENTS['php_version'], '>=' ) ) {
				/* Translators: The minimum PHP version */
				$errors[] = sprintf( esc_html__( 'Charity Donation Offers requires a minimum PHP version of %s or higher.', 'charity-donation-offers-for-woocommerce' ), self::PLUGIN_REQUIREMENTS['php_version'] );
			}

			if ( ! version_compare( $wp_version, self::PLUGIN_REQUIREMENTS['wp_version'], '>=' ) ) {
				/* Translators: The minimum WP version */
				$errors[] = sprintf( esc_html__( 'Charity Donation Offers requires a minimum WordPress version of %s or higher.', 'charity-donation-offers-for-woocommerce' ), self::PLUGIN_REQUIREMENTS['wp_version'] );
			}

			if ( ! defined( 'WC_VERSION' ) || ! version_compare( WC_VERSION, self::PLUGIN_REQUIREMENTS['wc_version'], '>=' ) ) {
				/* Translators: The minimum WC version */
				$errors[] = sprintf( esc_html__( 'Charity Donation Offers requires a minimum WooCommerce version of %s or higher.', 'charity-donation-offers-for-woocommerce' ), self::PLUGIN_REQUIREMENTS['wc_version'] );
			}

			/**
			 * Check if WooCommerce Admin is enabled.
			 * phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
			 */
			if ( apply_filters( 'woocommerce_admin_disabled', false ) ) {
				$errors[] = esc_html__( 'Charity Donation Offers requires WooCommerce Admin to be enabled.', 'charity-donation-offers-for-woocommerce' );
			}

			if ( empty( $errors ) ) {
				return true;
			}

			if ( $this->is_request( 'admin' ) ) {
				add_action(
					'admin_notices',
					function() use ( $errors ) {
						?>
						<div class="notice notice-error">
							<?php
							foreach ( $errors as $error ) {
								echo '<p>' . esc_html( $error ) . '</p>';
							}
							?>
						</div>
						<?php
					}
				);
				return;
			}

			return false;
		}

		/**
		 * Load Localisation files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 *
		 * Locales found in:
		 *      - WP_LANG_DIR/charity-donation-offers-for-woocommerce/charity-donation-offers-for-woocommerce-LOCALE.mo
		 *      - WP_LANG_DIR/plugins/charity-donation-offers-for-woocommerce-LOCALE.mo
		 */
		private function load_plugin_textdomain() {
			/**
			 * Get plugin locale.
			 * phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
			 */
			$locale = apply_filters( 'plugin_locale', get_locale(), 'charity-donation-offers-for-woocommerce' );

			load_textdomain( 'charity-donation-offers-for-woocommerce', WP_LANG_DIR . '/ccdofwc/cdofwc-' . $locale . '.mo' );
			load_plugin_textdomain( 'charity-donation-offers-for-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
		}

		/**
		 * Get the plugin url.
		 *
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Get Ajax URL.
		 *
		 * @return string
		 */
		public function ajax_url() {
			return admin_url( 'admin-ajax.php', 'relative' );
		}


	}

endif;