<?php
/**
 * Plugin Name: Beaver Tunnels
 * Plugin URI: http://beavertunnels.com/
 * Description: Allows you to assign Beaver Builder Templates to action hooks from popular themes and plugins.
 * Version: 1.2.2
 * Author: FireTree Design, LLC <info@firetreedesign.com>
 * Author URI: https://firetreedesign.com/
 * Text Domain: beaver-tunnels
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Beaver_Tunnels' ) ) :

define( 'BEAVER_TUNNELS_VERSION', '1.2.2' );

/**
 * Beaver Hooks class
 */
class Beaver_Tunnels {

    /**
     * @var Beaver_Tunnels The one true Beaver_Tunnels
     *
     * @since 1.0.0
     */
    private static $instance;

    /**
     * Main Beaver_Tunnels Instance
     *
     * Insures that only one instance of Beaver_Tunnels exists in memory at any
     * one time.
     *
     * @since 1.0
     * @static
     * @staticvar array $instance
     * @uses Beaver_Tunnels::includes() Include the required files
     * @see Beaver_Tunnels()
     * @return The one true Beaver_Tunnels
     */
    public static function instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Beaver_Tunnels ) ) {

			self::$instance = new Beaver_Tunnels;

			self::$instance->setup_constants();
			self::$instance->standalone_includes();
			self::$instance->license();

			if ( class_exists( 'FLBuilder' ) ) {
				self::$instance->includes();
			}

    	}

        return self::$instance;

    }

	public function is_network_active() {

		if ( ! function_exists('is_plugin_active_for_network') ) {
			return false;
		}

		if ( is_plugin_active_for_network( plugin_basename( BEAVER_TUNNELS_PLUGIN_FILE ) ) ) {
			return true;
		} else {
			return false;
		}
	}

     /**
      * Setup plugin constants
      *
      * @access private
      *
      * @since 1.0.0
      *
      * @return void
      */
     private function setup_constants() {

         // Plugin File
         if ( ! defined( 'BEAVER_TUNNELS_PLUGIN_FILE' ) ) {
             define( 'BEAVER_TUNNELS_PLUGIN_FILE', __FILE__ );
         }

         // Plugin Folder Path
         if ( ! defined( 'BEAVER_TUNNELS_PLUGIN_DIR' ) ) {
             define( 'BEAVER_TUNNELS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
         }

         // Plugin Folder URL
		if ( ! defined( 'BEAVER_TUNNELS_PLUGIN_URL' ) ) {
			define( 'BEAVER_TUNNELS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

     }

     /**
      * Include required files
      *
      * @access private
      *
      * @since 1.0
      *
      * @return void
      */
     private function includes() {

		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/registered-actions.php';
		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/admin-columns.php';
		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/admin-notice.php';
		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/meta-box.php';
		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/output.php';
		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/admin-bar-menu.php';
		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/visual-guides.php';
		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/template-override.php';
		require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/shortcodes.php';

     }

	 private function standalone_includes() {
		 require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/license.php';
		 require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/settings.php';
		 require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/white-label.php';
		 require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/upgrades.php';
	 }

	 private function license() {
		$license = new Beaver_Tunnels_License( __FILE__, 'beaver-tunnels', 'Beaver Tunnels', BEAVER_TUNNELS_VERSION );
	 }

}

endif; // End if class_exists check

/**
 * Initialize the Beaver_Tunnels class
 *
 * @since 1.0.0
 *
 * @return void
 */
function Beaver_Tunnels() {
	return Beaver_Tunnels::instance();
 }
add_action( 'plugins_loaded', 'Beaver_Tunnels');

// Turn on the Template Admin
register_activation_hook( __FILE__, 'beaver_tunnels_activation' );
function beaver_tunnels_activation() {

	if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {

		$blog_list = get_blog_list( 0, 'all' );
		foreach ( $blog_list as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			update_option( '_fl_builder_user_templates_admin', '1' );
			restore_current_blog();
		}

	} else {
		update_option( '_fl_builder_user_templates_admin', '1' );
	}

}
