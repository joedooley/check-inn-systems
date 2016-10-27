<?php
/**
 * Add support for the ShareThis plugin
 *
 * @package GravityView_Sharing
 * @subpackage services
 */

/**
 * Add support for the ShareThis plugin
 */
class GravityView_ShareThis extends GravityView_Sharing_Service {

	/**
	 * The name of the plugin or sharing service to be used
	 * @var string
	 */
	var $_service_name = 'ShareThis';

	/**
	 * The plugin path ({folder-name/file-name.php}) used to register the plugin
	 * @var string
	 */
	var $_plugin_path = 'share-this/sharethis.php';

	/**
	 * @inheritDoc
	 * @return bool
	 */
	function is_plugin_active() {

		return function_exists('install_ShareThis') && function_exists( 'st_makeEntries');
	}

	/**
	 * Generate the links output
	 *
	 * @return string|null
	 */
	function output() {

		if( !$this->is_plugin_active() ) {
			return NULL;
		}

		// Fix links for single entries
		GravityView_Sharing_Service::getInstance()->add_permalink_filter();

		/**
		 * The no-break code is doing what the st_show_buttons() function would do if it didn't hard-code post types
		 */
		$links = '<p class="no-break">'.st_makeEntries().'</p>';

		// Remove fix for links for single entries
		GravityView_Sharing_Service::getInstance()->remove_permalink_filter();

		return $links;
	}

}

new GravityView_ShareThis;