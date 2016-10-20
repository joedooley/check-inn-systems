<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Beaver_Tunnels_Admin_Notice {

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'current_screen', array( $this, 'is_correct_page' ), 20 );
	}

	public function is_network_active() {
		if ( is_plugin_active_for_network( plugin_basename( BEAVER_TUNNELS_PLUGIN_FILE ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function is_correct_page() {

		$screen = get_current_screen();

		if ( 'settings_page_beaver-tunnels' !== $screen->id && 'fl-builder-template' !== $screen->id && 'settings_page_beaver-tunnels-network' !== $screen->id ) {
			return;
		}

		if ( $this->is_network_active() ) {
			$license_data = get_site_option( 'beaver_tunnels_license_data' );
		} else {
			$license_data = get_option( 'beaver_tunnels_license_data' );
		}

		if ( ! is_object( $license_data ) || ! isset( $license_data->license ) || '' === $license_data->license ) {
			if ( $this->is_network_active() ) {
				add_action( 'network_admin_notices', array( $this, 'license_notice' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'license_notice' ) );
			}
			return;
		}

		if ( is_object( $license_data ) && isset( $license_data->license ) && 'deactivated' === strtolower( $license_data->license ) ) {
			if ( $this->is_network_active() ) {
				add_action( 'network_admin_notices', array( $this, 'deactivated_notice' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'deactivated_notice' ) );
			}
			return;
		}

		if ( is_object( $license_data ) && isset( $license_data->license ) && 'valid' !== strtolower( $license_data->license ) ) {
			if ( $this->is_network_active() ) {
				add_action( 'network_admin_notices', array( $this, 'expiration_notice' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'expiration_notice' ) );
			}
			return;
		}

	}

	public function license_notice() {

		$class = 'notice notice-warning';
		$message = sprintf( '<a href="%1s">%1s <strong>%2s</strong> %3s</a> %3s', admin_url('options-general.php?page=beaver-tunnels'), __('Please enter your', 'beaver-tunnels'), __( 'Beaver Tunnels', 'beaver-tunnels'), __('license', 'beaver-tunnels'), __('in order to receive updates.', 'beaver-tunnels' ) );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

	}

	public function deactivated_notice() {

		$class = 'notice notice-warning';
		$message = sprintf( '%1s <strong>%2s</strong> %3s <a href="%4s">%5s</a>', __('Your', 'beaver-tunnels'), __( 'Beaver Tunnels', 'beaver-tunnels'), __('license has been deactivated.', 'beaver-tunnels' ), admin_url('options-general.php?page=beaver-tunnels'), __('Please reactivate your license in order to receive updates.', 'beaver-tunnels' ) );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

	}

	public function expiration_notice() {

		$class = 'notice notice-warning';
		$message = sprintf( '%1s <strong>%2s</strong> %3s &dash; <a href="https://beavertunnels.com/account/" target="_blank">%4s</a>', __('Your', 'beaver-tunnels'), __( 'Beaver Tunnels', 'beaver-tunnels'), __('license has expired or is inactive!', 'beaver-tunnels' ), __('Visit your account page.', 'beaver-tunnels') );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

	}

}
new Beaver_Tunnels_Admin_Notice();
