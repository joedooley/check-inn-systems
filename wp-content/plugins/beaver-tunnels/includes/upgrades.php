<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Beaver_Tunnels_Upgrade {

	function __construct() {
		$this->upgrade_check();
	}

	/**
	 * Triggers the upgrade functions
	 *
	 * @since 1.3.5
	*/
	public function upgrade_check() {

		// Get the database vesrion
		$beaver_tunnels_version = get_option( 'beaver_tunnels_version' );

		// If it's empty, assign it a version number
		if ( ! $beaver_tunnels_version ) {
			// 1.2.0 is the first version to use this option, so we add the previous version
			$beaver_tunnels_version = '1.1.1';
		}

		// Check if we've already run this
		if ( version_compare( $beaver_tunnels_version, BEAVER_TUNNELS_VERSION, '=' ) ) {
			return;
		}

		// Database is before 1.2.0
		if ( version_compare( $beaver_tunnels_version, '1.2.0', '<' ) ) {
			$this->v1_2_0_upgrade();
		}

		update_option( 'beaver_tunnels_version', BEAVER_TUNNELS_VERSION );

	}

	/**
	 * Upgrade routine for Beaver Tunnels 1.2.0
	 *
	 * @since 1.2.0
	 */
	private function v1_2_0_upgrade() {

		if ( ! is_plugin_active_for_network( plugin_basename( BEAVER_TUNNELS_PLUGIN_FILE ) ) ) {
			return;
		}

		$blog_list = get_blog_list( 0, 'all' );
		foreach ($blog_list AS $blog) {

			switch_to_blog( $blog['blog_id'] );
			$beaver_tunnels = get_option( 'beaver_tunnels', array() );
			if ( isset( $beaver_tunnels[ 'license_key' ] ) ) {
				$beaver_tunnels_license_data = get_option('beaver_tunnels_license_data');
				update_site_option( 'beaver_tunnels', $beaver_tunnels );
				update_site_option( 'beaver_tunnels_license_data', $beaver_tunnels_license_data );
				delete_option( 'beaver_tunnels_license_data' );
				restore_current_blog();
				break;
			}
			restore_current_blog();

		}

	}

}
new Beaver_Tunnels_Upgrade();
