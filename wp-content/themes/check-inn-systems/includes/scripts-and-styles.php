<?php
/**
 * Check Inn Systems
 *
 * This file is for enqueueing script and styles
 *
 * @package Check Inn Systems
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue global theme scripts and styles. Includes
 * localization script for accessibility mobile navigation.
 */
function check_inn_systems_enqueue_scripts_styles() {

	wp_enqueue_style(
		'check-inn-systems-fonts',
		'//fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700',
		CHILD_THEME_VERSION
	);

	wp_enqueue_style(
		'dashicons'
	);

	wp_enqueue_script(
		'check-inn-systems-responsive-menu',
		get_stylesheet_directory_uri() . '/dist/js/site.min.js',
        ['jquery'],
		'1.0.0',
		true
	);

	$output = [
		'mainMenu' => __( 'Menu', 'check-inn-systems' ),
		'subMenu'  => __( 'Menu', 'check-inn-systems' ),
	];

	wp_localize_script(
		'check-inn-systems-responsive-menu',
		'checkInnSystemsL10n',
		$output
	);

}


