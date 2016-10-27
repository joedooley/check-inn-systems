<?php
/**
 * Plugin Name: Math by GravityView
 * Plugin URI:  https://gravityview.co/extensions/math/
 * Description: Perform calculations inside or outside GravityView using the <code>[gv_math]</code> shortcode. Requires PHP 5.4.
 * Version:     1.0.1
 * Text Domain: gravityview-math
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:	/languages
 * Author:      GravityView
 * Author URI:  https://gravityview.co
 */

add_action( 'plugins_loaded', 'gv_extension_math_load', 20 );

define( 'GRAVITYVIEW_MATH_VERSION', '1.0.1' );

/** @define "GRAVITYVIEW_MATH_FILE" "./gravityview-math.php" Full path to the gravityview-math.php file */
define( 'GRAVITYVIEW_MATH_FILE', __FILE__ );

/** @define "GRAVITYVIEW_MATH_DIR" "./" The absolute path to the plugin directory, with trailing slash */
define( 'GRAVITYVIEW_MATH_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Wrapper function to make sure GravityView_Extension has loaded
 * @return void
 */
function gv_extension_math_load() {

	if ( ! class_exists( 'GravityView_Extension' ) ) {
		if ( class_exists( 'GravityView_Plugin' ) && is_callable( array( 'GravityView_Plugin', 'include_extension_framework' ) ) ) {
			GravityView_Plugin::include_extension_framework();
		} else {
			include_once GRAVITYVIEW_MATH_DIR . 'lib/class-gravityview-extension.php';
		}
	}

	class GravityView_Math extends GravityView_Extension {

		protected $_title = 'Math by GravityView';

		protected $_item_id = 184981;

		protected $_version = GRAVITYVIEW_MATH_VERSION;

		protected $_min_gravityview_version = '1.12';

		protected $_min_php_version = '5.4.0';

		protected $_text_domain = 'gravityview-math';

		protected $_path = __FILE__;

		/**
		 * @var GravityView_Math
		 */
		public static $instance;

		/**
		 * @var GravityView_Math_Engine
		 */
		public $calculator;

		/**
		 * @var GravityView_Math_Report
		 */
		public $reporter;

		/**
		 * @var GravityView_Math_Shortcode
		 */
		public $shortcode;

		function __construct() {

			parent::__construct();

			// Make sure it's able to check for PHP version and
			if ( false === self::$is_compatible ) {
				return;
			}

			$this->setup();
		}

		protected function is_extension_supported() {

			self::$is_compatible = true;

			if( isset( $this->_min_php_version ) && false === version_compare( phpversion(), $this->_min_php_version , ">=") ) {

				$message = sprintf( __('%s requires PHP Version %s or newer. Please ask your host to upgrade your server\'s PHP.', 'gravityview-math' ), $this->_title, '<tt>'.$this->_min_php_version.'</tt>' );

				self::add_notice( $message );

				self::$is_compatible = false;
			}

			return self::$is_compatible;
		}

		/**
		 * Load the constants, include files, populate the variables
		 * @return void
		 */
		private function setup() {

			$this->include_files();

			$this->calculator = GravityView_Math_Engine::get_instance();
			$this->reporter   = GravityView_Math_Report::get_instance();
			$this->shortcode  = GravityView_Math_Shortcode::get_instance( $this->calculator, $this->reporter );
		}

		/**
		 * Include files required by the plugin
		 * @return void
		 */
		private function include_files() {
			require_once GRAVITYVIEW_MATH_DIR . 'vendor/autoload.php';
			require_once GRAVITYVIEW_MATH_DIR . 'includes/class-gravityview-math-report.php';
			require_once GRAVITYVIEW_MATH_DIR . 'includes/class-gravityview-math-engine.php';
			require_once GRAVITYVIEW_MATH_DIR . 'includes/class-gravityview-math-gfaddon.php';
			require_once GRAVITYVIEW_MATH_DIR . 'includes/class-gravityview-math-shortcode.php';
			require_once GRAVITYVIEW_MATH_DIR . 'includes/class-gravityview-math-gravityforms.php';
		}

		/**
		 * @return GravityView_Math
		 */
		public static function get_instance() {

			if ( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Easy access to the GravityView_Math_Report object
		 *
		 * @return GravityView_Math_Report
		 */
		public static function reporter() {
			return self::get_instance()->reporter;
		}

	}

	GravityView_Math::get_instance();
}