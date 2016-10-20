<?php

/**
 * Manage Gravity Forms.
 *
 * @class    GF_CLI
 * @version  1.0
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GF_CLI_Root extends WP_CLI_Command {

	/**
	 * Installs Gravity Forms or a Gravity Forms official add-on.
	 *
	 * A valid key is required either in the GF_LICENSE_KEY constant or the --key option.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * [<slug>]
	 * : The slug of the add-on. Default: gravityforms
	 *
	 * [--key=<key>]
	 * : The license key if not already available in the GF_LICENSE_KEY constant.
	 *
	 * [--force]
	 * : If set, the command will overwrite any installed version of the plugin, without prompting for confirmation.
	 *
	 * [--activate]
	 * : If set, the plugin will be activated immediately after install.
	 *
	 * [--network-activate]
	 * : If set, the plugin will be network activated immediately after install
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf install
	 *     wp gf install --force
	 *     wp gf install --key=[A valid Gravity Forms License Key]
	 *     wp gf install gravityformspolls key=[1234ABC]
	 *
	 */
	public function install( $args, $assoc_args ) {
		$slug = isset( $args[0] ) ? $args[0] : 'gravityforms';

		$key = isset( $assoc_args['key'] ) ? $assoc_args['key'] : $key = $this->get_key();

		if ( empty( $key ) ) {
			WP_CLI::error( 'A valid license key must be specified either in the GF_LICENSE_KEY constant or the --key option.' );
		}

		$this->save_key( $key );

		$plugin_info = $this->get_plugin_info( $slug, $key );

		if ( $plugin_info && ! empty( $plugin_info['download_url'] ) ) {

			$download_url = $plugin_info['download_url'];

			$force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

			$activate = WP_CLI\Utils\get_flag_value( $assoc_args, 'activate', false );

			$network_activate = WP_CLI\Utils\get_flag_value( $assoc_args, 'network-activate', false );

			$install_args = array( 'plugin', 'install', $download_url );

			$install_assoc_args = array();

			if ( $force ) {
				$install_assoc_args['force'] = true;
			}

			if ( $activate ) {
				$install_assoc_args['activate'] = true;
			}

			if ( $network_activate ) {
				$install_assoc_args['network-activate'] = true;
			}

			WP_CLI::run_command( $install_args, $install_assoc_args );
		} else {
			WP_CLI::error( 'There was a problem retrieving the download URL, please check the key.' );
		}
	}

	private function save_key( $key ) {
		$current_key = get_option( 'rg_gforms_key' );
		if ( empty( $key ) ) {
			delete_option( 'rg_gforms_key' );
		} else if ( $current_key != $key ) {
			$key = trim( $key );
			update_option( 'rg_gforms_key', md5( $key ) );
		}
	}

	private function get_key() {
		global $gf_license_key;
		$license_key = defined( 'GF_LICENSE_KEY' ) && empty( $gf_license_key ) ? GF_LICENSE_KEY : $gf_license_key;
		return $license_key;
	}

	private function get_plugin_info( $slug, $key ) {

		$gravity_manager_url = defined( 'GRAVITY_MANAGER_URL' ) && GRAVITY_MANAGER_URL ? GRAVITY_MANAGER_URL : 'https://www.gravityhelp.com/wp-content/plugins/gravitymanager';

		$url      = $gravity_manager_url . "/api.php?op=get_plugin&slug={$slug}&key={$key}";
		$options  = array(
			'timeout' => 30,
		);
		$headers  = array(
			'Accept' => 'text/plain',
		);
		$response = \WP_CLI\Utils\http_request( 'GET', $url, null, $headers, $options );
		if ( ! $response->success || 200 != $response->status_code ) {
			WP_CLI::error( 'There was a problem getting the download URL' );
		}
		$body      = trim( $response->body );
		$plugin_info = unserialize( $body );
		return $plugin_info;
	}
}
