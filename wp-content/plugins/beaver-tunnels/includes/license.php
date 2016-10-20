<?php
/**
 * Beaver Tunnels License Handler
 *
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Beaver_Tunnels_License {

	private $file;
	private $license;
	private $item_name;
	private $item_id;
	private $item_shortname;
	private $version;
	private $author = 'Beaver Tunnels';
	private $api_url = 'https://beavertunnels.com/';

    /**
     * Create a new instance
     */
    function __construct( $_file, $_item_id, $_item_name, $_version ) {

        $this->file				= $_file;
		$this->item_name		= $_item_name;
		$this->item_id			= $_item_id;
		$this->version			= $_version;
		$this->license			= trim( $this->get_license_key() );

		$this->includes();
		$this->hooks();
		$this->schedule_license_check();

    }

	/**
	 * Include the EDD Sofitware Licensing updater class
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function includes() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			require BEAVER_TUNNELS_PLUGIN_DIR . 'lib/EDD_SL_Plugin_Updater.php';
		}
	}

	/**
	 * Setup our hooks
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function hooks() {

		// Activate the license key when settings are saved
		add_action( 'admin_init', array( $this, 'activate_license' ) );

		// Deactivate the license key
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );

		// Register the auto updater
		add_action( 'admin_init', array( $this, 'auto_updater' ), 0 );

	}

	private function get_license_key() {
		if ( Beaver_Tunnels()->is_network_active() ) {
			$beaver_tunnels = get_site_option( 'beaver_tunnels', array() );
		} else {
			$beaver_tunnels = get_option( 'beaver_tunnels', array() );
		}
		if ( isset( $beaver_tunnels[ 'license_key' ] ) ) {
			return $beaver_tunnels[ 'license_key' ];
		}
		return false;
	}

	/**
	 * Auto updater
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 *
	 * @return void
	 */
	public function auto_updater() {

		if ( Beaver_Tunnels()->is_network_active() ) {
			$license_data = get_site_option( 'beaver_tunnels_license_data' );
		} else {
			$license_data = get_option( 'beaver_tunnels_license_data' );
		}

		if ( is_object( $license_data ) && isset( $license_data->license ) && 'valid' !== $license_data->license ) {
			return;
		}

		$edd_updater = new EDD_SL_Plugin_Updater(
			$this->api_url,
			$this->file,
			array(
				'version'	=> $this->version,
				'license'	=> $this->license,
				'item_name'	=> $this->item_name,
				'author'	=> $this->author,
				'url'		=> home_url()
			)
		);

	}

	/**
	 * Activate the license key
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function activate_license() {

		if ( ! isset( $_POST['beaver_tunnels'] ) ) {
			return;
		}

		if ( ! isset( $_POST['beaver_tunnels']['license_key'] ) || '' === $_POST['beaver_tunnels']['license_key'] ) {
			return;
		}

		foreach( $_POST as $key => $value ) {
			if ( false !== strpos( $key, 'beaver_tunnels_license_deactivate' ) ) {
				return;
			}
		}

		if ( ! wp_verify_nonce( $_POST[ 'beaver_tunnels-nonce'], 'beaver_tunnels-nonce' ) ) {
			wp_die( __('Beaver Tunnels licensing nonce verification failed.', 'beaver-tunnels'), __('Error', 'beaver-tunnels'), array( 'response' => 403 ) );
		}

		$license_data = get_option( 'beaver_tunnels_license_data' );

		if ( is_object( $license_data ) && isset( $license_data->license ) && 'valid' === $license_data->license ) {
			return;
		}

		$license = sanitize_text_field( $_POST['beaver_tunnels'][ 'license_key' ] );

		if ( empty( $license ) ) {
			return;
		}

		$body = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( $this->item_name ),
			'url'        => home_url(),
		);

		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout'	=> 15,
				'sslverify'	=> false,
				'body'      => $body,
			)
		);

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return;
		}

		// Make WordPress look for updates
		set_site_transient( 'update_plugins', null );

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( Beaver_Tunnels()->is_network_active() ) {
			update_site_option( 'beaver_tunnels_license_data', $license_data );
		} else {
			update_option( 'beaver_tunnels_license_data', $license_data );
		}

	}

	/**
	 * Deactivate the license key
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function deactivate_license() {

		if ( ! isset( $_POST['beaver_tunnels'] ) ) {
			return;
		}

		if ( ! isset( $_POST['beaver_tunnels'][ 'license_key' ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST[ 'beaver_tunnels-nonce'], 'beaver_tunnels-nonce' ) ) {
			wp_die( __('Nonce verification failed', 'beaver-tunnels'), __('Error', 'beaver-tunnels'), array( 'response' => 403 ) );
		}

		if ( isset( $_POST[ 'beaver_tunnels_license_deactivate' ] ) ) {

			$body = array(
				'edd_action' => 'deactivate_license',
				'license'    => $this->license,
				'item_name'  => urlencode( $this->item_name ),
				'url'        => home_url(),
			);

			$response = wp_remote_post(
				$this->api_url,
				array(
					'timeout'	=> 15,
					'sslverify'	=> false,
					'body'      => $body,
				)
			);

			// Check for errors
			if ( is_wp_error( $response ) ) {
				return;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $license_data->success ) && true === $license_data->success ) {
				if ( $this->is_network_active() ) {
					update_site_option( 'beaver_tunnels_license_data', $license_data );
					delete_site_option( 'beaver_tunnels_license_status' );
				} else {
					update_option( 'beaver_tunnels_license_data', $license_data );
					delete_option( 'beaver_tunnels_license_status' );
				}
			}



		}

	}

	private function schedule_license_check() {

		if ( Beaver_Tunnels()->is_network_active() ) {
			$beaver_tunnels = get_site_option( 'beaver_tunnels', array() );
		} else {
			$beaver_tunnels = get_option( 'beaver_tunnels', array() );
		}

		if ( ! isset( $beaver_tunnels[ 'license_key' ] ) ) {
			return;
		}

		if ( FALSE === wp_next_scheduled( 'beaver_tunnels_license_check' ) ) {
			 wp_schedule_event( time(), 'daily', 'beaver_tunnels_license_check' );
		}
		add_action( 'beaver_tunnels_license_check', array( $this, 'check_license' ) );

	}

	public function check_license() {

		if ( Beaver_Tunnels()->is_network_active() ) {
			$beaver_tunnels = get_site_option( 'beaver_tunnels', array() );
		} else {
			$beaver_tunnels = get_option( 'beaver_tunnels', array() );
		}

		if ( ! isset( $beaver_tunnels[ 'license_key' ] ) ) {
			return;
		}

		$body = array(
			'edd_action' => 'check_license',
			'license'    => $this->license,
			'item_name'  => urlencode( $this->item_name ),
			'url'        => home_url(),
		);

		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout'	=> 15,
				'sslverify'	=> false,
				'body'      => $body,
			)
		);

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( Beaver_Tunnels()->is_network_active() ) {
			update_site_option( 'beaver_tunnels_license_data', $license_data );
		} else {
			update_option( 'beaver_tunnels_license_data', $license_data );
		}

	}

}
