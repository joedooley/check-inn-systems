<?php

/**
 * Template data exporter for the builder.
 *
 * @since 1.8
 */
final class FLBuilderTemplateDataExporter {
	
	/** 
	 * Initializes the exporter.
	 *
	 * @since 1.8
	 * @return void
	 */
	static public function init()
	{
		add_action( 'plugins_loaded',                              __CLASS__ . '::init_hooks' );
		add_action( 'fl_builder_admin_settings_templates_form',    __CLASS__ . '::render_admin_settings', 9 );
		add_action( 'fl_builder_admin_settings_save',              __CLASS__ . '::save_admin_settings' );
	}
	
	/** 
	 * Init actions and filters.
	 *
	 * @since 1.8
	 * @return void
	 */
	static public function init_hooks()
	{
		if ( ! is_admin() ) {
			return;
		}
		
		add_action( 'admin_menu', __CLASS__ . '::menu' );
		
		if ( isset( $_REQUEST['page'] ) && 'fl-builder-template-data-exporter' == $_REQUEST['page'] ) {
			add_action( 'admin_enqueue_scripts', __CLASS__ . '::styles_scripts' );
			add_action( 'init',                  __CLASS__ . '::export' );
		}
	}

	/** 
	 * Renders the admin settings.
	 *
	 * @since 1.8
	 * @return void
	 */
	static public function render_admin_settings()
	{
		include FL_BUILDER_TEMPLATE_DATA_EXPORTER_DIR . 'includes/admin-settings-templates.php';
	}

	/** 
	 * Saves the admin settings.
	 *
	 * @since 1.8
	 * @return void
	 */
	static public function save_admin_settings()
	{
		if ( isset( $_POST['fl-templates-nonce'] ) && wp_verify_nonce( $_POST['fl-templates-nonce'], 'templates' ) ) {
		
			$admin_ui_enabled = isset( $_POST['fl-template-data-exporter'] ) ? 1 : 0;
			
			FLBuilderModel::update_admin_settings_option( '_fl_builder_template_data_exporter', $admin_ui_enabled, true );
		}
	}

	/** 
	 * Checks to see whether the exporter is enabled or not.
	 *
	 * @since 1.8
	 * @return void
	 */
	static public function is_enabled()
	{
		return FLBuilderModel::get_admin_settings_option( '_fl_builder_template_data_exporter', true );
	}
	
	/** 
	 * Enqueues scripts and styles for the exporter.
	 *
	 * @since 1.8
	 * @return void
	 */
	static public function styles_scripts()
	{
		wp_enqueue_script( 
			'fl-builder-template-data-exporter', 
			FL_BUILDER_TEMPLATE_DATA_EXPORTER_URL . 'js/fl-builder-template-data-exporter.js', 
			array(), 
			FL_BUILDER_VERSION 
		);
	}
	
	/** 
	 * Renders the admin settings menu.
	 *
	 * @since 1.8
	 * @return void
	 */
	static public function menu() 
	{
		if ( self::is_enabled() && current_user_can( 'delete_users' ) ) {
			
			$title = __( 'Template Exporter', 'fl-builder' );
			$cap   = 'delete_users';
			$slug  = 'fl-builder-template-data-exporter';
			$func  = __CLASS__ . '::render';
			
			add_submenu_page( 'tools.php', $title, $title, $cap, $slug, $func );
		}
	}
	
	/** 
	 * Renders the exporter UI.
	 *
	 * @since 1.8
	 * @return void
	 */
	static public function render() 
	{
		$layouts = self::get_ui_data();
		$rows    = self::get_ui_data( 'row' );
		$modules = self::get_ui_data( 'module' );
		
		include FL_BUILDER_TEMPLATE_DATA_EXPORTER_DIR . 'includes/template-data-exporter.php';
	}
	
	/** 
	 * Run the exporter.
	 *
	 * @since 1.8
	 * @return void
	 */	 
	static public function export()
	{
		if ( ! current_user_can( 'delete_users' ) ) {
			return;
		}
		if ( ! isset( $_POST['fl-builder-template-data-exporter-nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['fl-builder-template-data-exporter-nonce'], 'fl-builder-template-data-exporter' ) ) {
			return;
		}
		
		$templates = array(
			'layout' => array(),
			'row'    => array(),
			'module' => array()
		);
		
		if ( isset( $_POST['fl-builder-export-layout'] ) && is_array( $_POST['fl-builder-export-layout'] ) ) {
			$templates['layout'] = self::get_export_data( $_POST['fl-builder-export-layout'] );
		}
		if ( isset( $_POST['fl-builder-export-row'] ) && is_array( $_POST['fl-builder-export-row'] ) ) {
			$templates['row'] = self::get_export_data( $_POST['fl-builder-export-row'] );
		}
		if ( isset( $_POST['fl-builder-export-module'] ) && is_array( $_POST['fl-builder-export-module'] ) ) {
			$templates['module'] = self::get_export_data( $_POST['fl-builder-export-module'] );
		}
		
		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename="templates.dat";' );
		header( 'Content-Transfer-Encoding: binary' );
		echo serialize( $templates );
		die();
	}
	
	/** 
	 * Returns user template data of a certain type for the UI.
	 *
	 * @since 1.8
	 * @access private
	 * @param string $type
	 * @return array
	 */
	static private function get_ui_data( $type = 'layout' ) 
	{
		$templates = array();
		
		foreach( get_posts( array(
			'post_type'       => 'fl-builder-template',
			'orderby'         => 'title',
			'order'           => 'ASC',
			'posts_per_page'  => '-1',
			'tax_query'       => array(
				array(
					'taxonomy'  => 'fl-builder-template-type',
					'field'     => 'slug',
					'terms'     => $type
				)
			)
		) ) as $post ) {
			$templates[] = array(
				'id'   => $post->ID,
				'name' => $post->post_title
			);
		}
		
		return $templates;
	}
	
	/** 
	 * Returns user template data for the specified posts.
	 *
	 * @since 1.8
	 * @access private
	 * @param array $post_ids
	 * @return array
	 */
	static private function get_export_data( $post_ids = array() ) 
	{
		if ( empty( $post_ids ) ) {
			return array();
		}
		
		$templates = array();
		$index     = 0;
		
		foreach( get_posts( array(
			'post_type'       => 'fl-builder-template',
			'orderby'         => 'menu_order title',
			'order'           => 'ASC',
			'posts_per_page'  => '-1',
			'post__in'        => $post_ids
		) ) as $post ) {
			
			// Build the template object.
			$template             = new StdClass();
			$template->name       = $post->post_title;
			$template->index      = $index++;
			$template->type       = FLBuilderModel::get_user_template_type( $post->ID );
			$template->global     = false;
			$template->image      = '';
			$template->categories = array();
			$template->nodes      = FLBuilderModel::generate_new_node_ids( FLBuilderModel::get_layout_data( 'published', $post->ID ) );
			$template->settings   = FLBuilderModel::get_layout_settings( 'published', $post->ID );
			
			// Get the template thumbnail.
			if ( has_post_thumbnail( $post->ID ) ) {
				$attachment_image_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );
				$image_src            = apply_filters( 'fl_builder_exporter_template_thumb_src', $attachment_image_src[0] );
				$template->image      = $image_src;
			}
			
			// Get the template categories.
			$categories = wp_get_post_terms( $post->ID, 'fl-builder-template-category' );
			
			if ( 0 === count( $categories ) || is_wp_error( $categories ) ) {
				$template->categories['uncategorized'] = 'Uncategorized';
			}
			else {
				
				foreach ( $categories as $category ) {
					$template->categories[ $category->slug ] = $category->name;
				}
			}
			
			// Add the template to the templates array.
			$templates[] = $template;
		}
		
		return $templates;
	}
}

FLBuilderTemplateDataExporter::init();