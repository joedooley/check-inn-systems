<?php

/**
 * Handle registering the `social` field in GravityView
 */
class GravityView_Social_Register_Field {

	static $instance;

	public static function get_instance() {

		if( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		$this->add_hooks();
	}

	function add_hooks() {

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field'), 10, 3 );

		add_filter( 'gravityview_template_sharing_options', array( $this, 'field_options'), 10, 5 );

		add_filter( 'gravityview_blacklist_field_types', array( $this, 'add_field_to_blacklist'), 10, 2 );

		add_filter( 'gravityview/sortable/field_blacklist', array( $this, 'add_field_to_blacklist') );

	}

	/**
	 * Add Sharing Field as a default field, outside those set in the Gravity Form form
	 * @param array $entry_default_fields Existing fields
	 * @param  string|array $form form_ID or form object
	 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
	 */
	function add_default_field( $entry_default_fields, $form = array(), $zone = '' ) {

		$services = GravityView_Sharing::get_instance()->get_sharing_services();

		if( ! empty( $services ) ) {
			$entry_default_fields['sharing'] = array(
				'label' => __( 'Sharing', 'gravityview-sharing-seo' ),
				'type'  => 'sharing',
				'desc'  => __( 'Add social sharing links from active sharing plugins.', 'gravityview-sharing-seo' ),
			);
		}

		return $entry_default_fields;
	}

	/**
	 * Prevent users from being able to edit the field
	 * @param array $fields Array of field types not editable by users
	 */
	function add_field_to_blacklist( $fields, $context = NULL ) {

		if( $context === NULL || $context === 'edit' ) {
			$fields[] = 'sharing';
		}

		return $fields;
	}

	/**
	 * Add "Edit Link Text" setting to the edit_link field settings
	 * @param  [type] $field_options [description]
	 * @param  [type] $template_id   [description]
	 * @param  [type] $field_id      [description]
	 * @param  [type] $context       [description]
	 * @param  [type] $input_type    [description]
	 * @return [type]                [description]
	 */
	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		if( $field_id !== 'sharing' ) { return $field_options; }

		// Always a link, never a filter
		unset( $field_options['show_as_link'], $field_options['search_filter'], $field_options['wpautop'] );

		$new_options = array();

		$services = array();
		foreach ( GravityView_Sharing::get_instance()->get_sharing_services() as $key => $value) {
			$services[ get_class( $value ) ] = $key;
		}

		$new_options['sharing_service'] = array(
			'type' => 'select',
			'label' => __( 'Sharing Plugin', 'gravityview-sharing-seo' ),
			'desc' => __('Choose the plugin you would like to display the social sharing links for.', 'gravityview-sharing-seo'),
			'value' => '',
			// Set the key and the value to be the same
			'choices' => $services
		);

		return $new_options + $field_options;
	}

	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		$index = is_callable( array( 'GVCommon', 'get_next_open_index' ) ) ? GVCommon::get_next_open_index( $file_paths, 111 ) : 111;

		// Index 100 is the default GravityView template path.
		$file_paths[ $index ] = plugin_dir_path( GravityView_Sharing::get_instance()->get_path() ) .'/templates';

		return $file_paths;
	}

}

GravityView_Social_Register_Field::get_instance();