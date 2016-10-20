<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Beaver_Tunnels_Shortcodes {

	public $post_id;

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( ! is_admin() ) {
			add_action( 'wp', array( $this, 'set_post_id' ) );
		}
		add_shortcode( 'beaver_tunnels', array( $this, 'beaver_tunnels_shortcode' ) );

	}

	public function set_post_id() {

		global $post;
		$this->post_id = $post->ID;

	}

	public function beaver_tunnels_shortcode( $atts = array(), $content = '' ) {

		if ( 0 === strlen( trim( $content ) ) ) {
			return;
		}

		global $wp_query;
		global $post;

		$wp_query_backup	= $wp_query;
		$post_backup		= $post;

		$wp_query_args = array(
			'p'			=> $this->post_id,
			'post_type'	=> 'any'
		);
		$wp_query = new WP_Query( $wp_query_args );

		$post = get_post( $this->post_id, OBJECT );

		$content = do_shortcode( $content );

		$wp_query	= $wp_query_backup;
		$post		= $post_backup;

		return $content;

	}

}
new Beaver_Tunnels_Shortcodes();
