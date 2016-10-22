<?php
/**
 * Check Inn Systems
 *
 * This file is for the theme settings menu
 *
 * @package Check Inn Systems
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'genesis_before', 'check_inn_systems_google_tag_manager' );
/**
 * Add required Google Tag Manager script
 * after the opening <body> tag. Needed for
 * Google Tag Manager for WordPress plugin.
 *
 * @return        void
 * @author        Joe Dooley
 *
 */
function check_inn_systems_google_tag_manager() {
	if ( function_exists( 'gtm4wp_the_gtm_tag' ) ) {
		gtm4wp_the_gtm_tag();
	}
}

// ACF Theme Options Page
if ( function_exists( 'acf_add_options_page' ) || is_admin() ) {

	$acf_theme_settings = acf_add_options_page( array(
		'page_title' => 'Theme General Settings',
		'menu_title' => 'Theme Settings',
		'menu_slug'  => 'theme-general-settings',
		'capability' => 'edit_posts',
		'redirect'   => false,
	) );

}

