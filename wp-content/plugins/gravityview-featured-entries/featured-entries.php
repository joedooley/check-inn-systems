<?php
/*
Plugin Name: GravityView - Featured Entries Extension
Plugin URI: https://gravityview.co/extensions/featured-entries/
Description: Promote entries as Featured in Views
Version: 1.1.1
Author: Katz Web Services, Inc.
Author URI: https://gravityview.co
Text Domain: gravityview-featured-entries
Domain Path: /languages/
*/

add_action( 'plugins_loaded', 'gv_extension_featured_entries_load' );

/**
 * Wrapper function to make sure GravityView_Extension has loaded
 * @return void
 */
function gv_extension_featured_entries_load() {

	if( !class_exists( 'GravityView_Extension' ) ) {

		if( class_exists('GravityView_Plugin') && is_callable(array('GravityView_Plugin', 'include_extension_framework')) ) {
			GravityView_Plugin::include_extension_framework();
		} else {
			// We prefer to use the one bundled with GravityView, but if it doesn't exist, go here.
			include_once plugin_dir_path( __FILE__ ) . 'lib/class-gravityview-extension.php';
		}
	}

	/**
	 * Load the plugin class
	 */
	include_once plugin_dir_path( __FILE__ ) . 'class-gravityview-featured-entries.php';

}
