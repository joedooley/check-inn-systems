<?php
/**
 * Visual Guidesw class
 *
 * @package Beaver_Tunnels
 * @since 0.9.5
 */
class Beaver_Tunnels_Visual_Guides {

    public function __construct() {
        add_action( 'wp', array( $this, 'check_url' ) );
    }

    public function check_url() {

		if ( ! is_user_logged_in() || is_admin() ) {
			return;
		}

		$enqueue_styles = false;

		$actions = apply_filters('beaver_tunnels', array() );
		if ( empty( $actions ) ) {
			return;
		}
		foreach( $actions as $platform ) {

			$safe_name = 'bt-' . preg_replace( '/\W+/', '', strtolower( strip_tags( $platform['title'] ) ) );

			if ( ! isset( $_GET[ $safe_name ] ) ) {
				continue;
			}

			if ( empty( $platform['actions'] ) || ! is_array( $platform['actions'] ) ) {
				continue;
			}

			$enqueue_styles = true;

			foreach ( $platform['actions'] as $action ) {

				add_action( $action, function() use( $action ) {
					echo '<div class="bt-visual-guides">' . $action . '</div>';
				}, '1' );

			}

		}

		if ( true === $enqueue_styles ) {
			$this->styles();
		}

    }

	public function styles() {
		wp_enqueue_style( 'beaver-tunnels-visual-guides', BEAVER_TUNNELS_PLUGIN_URL . 'assets/css/visual-guides.css', array(), '1.0.0' );
	}

}
new Beaver_Tunnels_Visual_Guides();
