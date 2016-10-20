<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Beaver_Tunnels_Admin_Columns {

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter('manage_fl-builder-template_posts_columns', array( $this, 'add_columns_head' ) );
		add_action('manage_fl-builder-template_posts_custom_column', array( $this, 'add_columns_content' ), 10, 2 );
	}

	/**
	 * Add the column header
	 *
	 * @since 1.0.0
	 *
	 * @param array $defaults Current column headers
	 */
	public function add_columns_head( $defaults ) {

		$new_defaults = array();
		foreach ( $defaults as $key => $title ) {

			if ( 'date' == $key ) {
				$new_defaults[ 'beaver_tunnels' ] = Beaver_Tunnels_White_Label::get_branding();
			}

			$new_defaults[$key] = $title;

		}

		return $new_defaults;

	}

	/**
	 * Add the column header content
	 *
	 * @since 1.0.0
	 *
	 * @param string $column_name The slug/id for the column
	 * @param string $post_ID     The post ID
	 */
	public function add_columns_content( $column_name, $post_ID ) {

		if ( 'beaver_tunnels' == $column_name ) {
			$action = get_post_meta( $post_ID, '_beavertunnels_action', true );
			if ( strlen( $action ) > 0 ) {
				echo $action;
			} else {
				_e( 'Not set', 'beaver-tunnels' );
			}
		}

	}

}
new Beaver_Tunnels_Admin_Columns();
