<?php
/*
Plugin Name: GravityView - Social Sharing & SEO
Plugin URI: https://gravityview.co/extensions/sharing-seo/
Description: Enable sharing in concert with the Jetpack, JP Sharing, and ShareThis plugins. Integrates with WordPress SEO for View and Entry SEO.
Version: 1.0.2
Text Domain:       	gravityview-sharing-seo
License:           	GPLv2 or later
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
Domain Path:			/languages
Author: Katz Web Services, Inc.
Author URI: https://gravityview.co
*/

add_action( 'plugins_loaded', 'gv_extension_sharing_load', 20 );

/**
 * Wrapper function to make sure GravityView_Extension has loaded
 * @return void
 */
function gv_extension_sharing_load() {

	if( !class_exists( 'GravityView_Extension' ) ) {

		if( class_exists('GravityView_Plugin') && is_callable(array('GravityView_Plugin', 'include_extension_framework')) ) {
			GravityView_Plugin::include_extension_framework();
		} else {
			// We prefer to use the one bundled with GravityView, but if it doesn't exist, go here.
			include_once plugin_dir_path( __FILE__ ) . 'lib/class-gravityview-extension.php';
		}
	}


	class GravityView_Sharing extends GravityView_Extension {

		protected $_title = 'Social Sharing & SEO';

		protected $_version = '1.0.2';

		protected $_min_gravityview_version = '1.7.4';

		protected $_text_domain = 'gravityview-sharing-seo';

		protected $_path = __FILE__;

		protected $_dir_path;

		protected $_includes_dir_path;

		/**
		 * @var GravityView_Sharing_Service[]
		 */
		private $_gravityview_sharing_services = array();

		static $instance;

		public static function get_instance() {

			if( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		function __construct() {

			$this->_dir_path = plugin_dir_path( $this->_path );
			$this->_includes_dir_path = trailingslashit( $this->_dir_path . 'includes' );

			parent::__construct();

		}

		function add_hooks() {

			include_once( $this->_dir_path . 'sharing-common-functions.php' );
			include_once( $this->_includes_dir_path . 'class-gravityview-social-register-field.php' );
			include_once( $this->_includes_dir_path . 'class-gravityview-social-meta.php' );

			$this->register_sharing_services();
		}

		public function get_path() {
			return $this->_path;
		}

		public function get_sharing_services() {
			return $this->_gravityview_sharing_services;
		}

		public function register_sharing_services() {

			require_once( $this->_includes_dir_path . '/class-sharing-service.php' );

			// Load Field files automatically
			foreach ( glob( $this->_includes_dir_path . '/services/*.php' ) as $service ) {
				require_once( $service );
			}

			/**
			 * Register your own sharing service.
			 * @param GravityView_Sharing_Service[]
			 */
			$this->_gravityview_sharing_services = apply_filters( 'gravityview_sharing_services', array() );

		}

	}

	GravityView_Sharing::get_instance();
}
