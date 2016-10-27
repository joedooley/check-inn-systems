<?php
/**
 * Add support for the JP Sharing plugin
 *
 * @package GravityView_Sharing
 * @subpackage services
 */

/**
 * Add support for the JP Sharing plugin - clone of Jetpack Sharing
 * @see GravityView_Sharing_Jetpack
 */
class GravityView_Sharing_JP_Sharing extends GravityView_Sharing_Jetpack {

	var $_service_name = 'JP Sharing';
	var $_plugin_path = 'jetpack-sharing/sharedaddy.php';

	function is_plugin_active() {

		$active = defined('JETPACK_SHARING_VERSION') || function_exists('jetpack_sharing_load_textdomain');

		return $active;
	}

}

new GravityView_Sharing_JP_Sharing;