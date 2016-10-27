<?php
/**
 * Add support for the Jetpack Sharing plugin
 *
 * @package GravityView_Sharing
 * @subpackage services
 */

class GravityView_Sharing_Jetpack extends GravityView_Sharing_Service {

	var $_service_name = 'Jetpack';
	var $_plugin_path = 'jetpack/jetpack.php';

	function is_plugin_active() {

		if( !class_exists('Jetpack' ) )  {
			return false;
		}

		if( !Jetpack::is_active() && !Jetpack::is_development_mode() ) {
			return false;
		}

		$jetpack_active_modules = (array)get_option('jetpack_active_modules');

		if ( in_array( 'sharing', $jetpack_active_modules ) || in_array( 'sharedaddy', $jetpack_active_modules ) ) {
		      return true;
		}

		return false;

	}

	/**
	 * @return string|null
	 */
	function output() {

		if( !$this->is_plugin_active() ) {
			return null;
		}

		$this->add_permalink_filter();

		$this->setup_metadata_filter('add');

		$output = sharing_display();

		$this->remove_permalink_filter();

		$this->setup_metadata_filter('remove');

		return $output;

	}

	/**
	 * Add or remove the metadata filter
	 *
	 * @since 1.0.1
	 *
	 * @param string $add_or_remove "add": Add the metadata filter; "remove": remove the filter
	 *
	 * @return boolean|void True/False to show if filter was successfully added (if running WP 4.3+); void otherwise
	 */
	private function setup_metadata_filter( $add_or_remove = 'add' ) {
		if( 'add' === $add_or_remove ) {
			$setup = add_filter( 'get_post_metadata', array( $this, 'filter_sharing_disabled' ), 10, 4 );
		} else {
			$setup = remove_filter( 'get_post_metadata', array( $this, 'filter_sharing_disabled' ), 10, 4 );
		}
		return $setup;
	}

	/**
	 * Override the 'sharing_disabled' metadata used by Jetpack to determine whether to display the sharing links.
	 *
	 * We add the filter to force the display of the links, even if the View's global setting is to not display them.
	 *
	 * @since 1.0.1
	 *
	 * @param null $value
	 *
	 * @param null|array|string $value     The value get_metadata() should return - a single metadata value, or an array of values.
	 * @param int               $object_id Object ID.
	 * @param string            $meta_key  Meta key.
	 * @param string|array      $single    Meta value, or an array of values.
	 */
	public function filter_sharing_disabled( $value = null, $object_id, $meta_key, $single ) {

		$post_id = get_queried_object_id();

		// Disable sharing disabling...
		if( $post_id === $object_id && 'sharing_disabled' === $meta_key ) {
			return false;
		}

		return $value;
	}

	function frontend_view_hooks() {

		// If the current page has Views
		if( gravityview_get_current_views() ) {

			add_filter( 'sharing_permalink', array( $this, 'filter_sharing_permalink' ), 10, 3 );
			add_filter( 'sharing_title', array( $this, 'modify_sharing_title' ), 10, 2 );

			$sharing_display_priority = 19;
			add_filter( 'the_content', array( $this, 'maybe_add_the_content_filter' ), ( $sharing_display_priority - 1 ) );
			add_filter( 'the_excerpt', array( $this, 'maybe_add_the_content_filter' ), ( $sharing_display_priority - 1 ) );

			add_filter( 'the_content', array( $this, 'remove_the_content_filter' ), ( $sharing_display_priority + 1 ) );
			add_filter( 'the_excerpt', array( $this, 'remove_the_content_filter' ), ( $sharing_display_priority + 1 ) );
		}

		parent::frontend_view_hooks();
	}

	function modify_sharing_title( $post_title, $post_id = 0 ) {
		$return = $post_title;

		if( class_exists('GravityView_frontend') ) {
			$return = gravityview_social_get_title( $post_title, $post_id );
		}

		return $return;
	}

	function maybe_add_the_content_filter( $the_content ) {
		global $post;

		$view_id = GravityView_View_Data::getInstance( )->maybe_get_view_id( $post );

		if( empty( $view_id ) ) {
			return $the_content;
		}

		$this->add_permalink_filter();

		return $the_content;
	}

	/**
	 * Remove the permalink filter
	 * @param string $the_content
	 *
	 * @return string
	 */
	function remove_the_content_filter( $the_content = '' ) {
		$this->remove_permalink_filter();
		return $the_content;
	}

	/**
	 * Modify the redirect URL used for the sharing screens
	 *
	 * @param $permalink
	 * @param $post_id
	 * @param $sharing_source Name of the sharing class triggering the request
	 *
	 * @return string
	 */
	function filter_sharing_permalink( $permalink, $post_id = 0, $sharing_source = '' ) {
		return gravityview_social_get_permalink( $permalink, $post_id );
	}

}

new GravityView_Sharing_Jetpack;