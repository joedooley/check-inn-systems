<?php

/**
 * Display the landing page for the Import/Export Gravity Forms page.
 * It just points users to the import screen for each form.
 */
class GravityView_Import_Export_Page {

	function __construct() {
		add_action( 'admin_init', array( $this, 'init_admin' ) );
	}

	/**
	 * Add the actions and filters
	 */
	function init_admin() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'gform_export_menu', array( $this, 'export_menu' ) );
		add_action( 'gform_export_page_import_entries', array( $this, 'export_page_import_entries' ) );
	}

	/**
	 * Enqueue the style on the Import tab
	 */
	function enqueue_scripts() {
		if ( rgget( 'page' ) === 'gf_export' && rgget( 'view' ) === 'import_entries' ) {
			wp_enqueue_style( 'gravityview-importer-admin' );
		}
	}

	/**
	 * Render the Import Entries page
	 */
	public function export_page_import_entries() {

		GFExport::page_header( __( 'Import Entries', 'gravityview-importer' ) );

		include( gravityview_importer()->get_base_path() . '/partials/export-page-import-entries.php' );

		GFExport::page_footer();
	}

	/**
	 * Add the menu item to the Import/Export tabs
	 *
	 * @param array $setting_tabs Existing tabs
	 *
	 * @return array modified tabs
	 */
	public function export_menu( $setting_tabs = array() ) {

		// TODO: Permissions
		if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {

			// Find an open slot
			$key = isset( $setting_tabs[11] ) ? ( isset( $setting_tabs[12] ) ? 13 : 12 ) : 11;

			$setting_tabs[ $key ] = array(
				'name'  => 'import_entries',
				'label' => __( 'Import Entries', 'gravityview-importer' )
			);
		}

		return $setting_tabs;
	}

}

new GravityView_Import_Export_Page;