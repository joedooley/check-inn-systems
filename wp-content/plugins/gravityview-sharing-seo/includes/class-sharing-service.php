<?php

abstract class GravityView_Sharing_Service {

	/**
	 * The name of the plugin or sharing service to be used
	 * @var string
	 */
	protected $_service_name;

	/**
	 * The plugin path ({folder-name/file-name.php}) used to register the plugin
	 * @var string
	 */
	protected $_plugin_path;

	/**
	 * Is the plugin installed?
	 * @var boolean
	 */
	private $_is_plugin_installed;

	/**
	 * Is the plugin active?
	 * @var boolean
	 */
	private $_is_plugin_active;

	/**
	 * @var GravityView_Sharing_Service
	 */
	static public $_instance;

	function __construct() {

		if ( false === $this->is_plugin_active() ) {
			return;
		}

		add_filter( 'gravityview_sharing_services', array( $this, '_register_service' ) );

		// Don't show Sharing metabox
		if ( class_exists( 'GravityView_Admin' ) && GravityView_Admin::is_admin_page() ) {
			$this->admin_view_hooks();
		}

		// Run frontend hooks
		if( !is_admin() || ( defined('DOING_AJAX') && DOING_AJAX ) ) {
			add_action( 'wp', array( $this, 'frontend_view_hooks' ), 12 );
		}

	}

	function add_permalink_filter() {
		add_filter('post_link', 'gravityview_social_get_permalink', 10, 3 );
		add_filter('page_link', 'gravityview_social_get_permalink', 10, 3  );
		add_filter('attachment_link', 'gravityview_social_get_permalink', 10, 3 );
		add_filter('post_type_link', 'gravityview_social_get_permalink', 10, 4  );
	}

	function remove_permalink_filter() {
		remove_filter('post_link', 'gravityview_social_get_permalink', 10, 3 );
		remove_filter('page_link', 'gravityview_social_get_permalink', 10, 3  );
		remove_filter('attachment_link', 'gravityview_social_get_permalink', 10, 3 );
		remove_filter('post_type_link', 'gravityview_social_get_permalink', 10, 4  );
	}

	static function getInstance() {

		if( empty( self::$_instance ) ) {
			$class = get_called_class();
			self::$_instance = new $class;
		}

		return self::$_instance;
	}

	function _register_service( $services = array() ) {

		$services[ $this->_service_name ] = &$this;

		return $services;
	}

	/**
	 * Check whether the sharing service plugin is installed and activated
	 */
	function is_plugin_installed() {

		// Don't show Sharing metabox
		if( class_exists('GravityView_Admin') ) {
			$this->_is_plugin_installed = GravityView_Admin::get_plugin_status( $this->_plugin_path );
		}

	}

	/**
	 * Is the current shared plugin active?
	 */
	function is_plugin_active() {}

	function admin_view_hooks() {}

	function frontend_view_hooks() {}

	function output() {}

}