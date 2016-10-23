<?php
/**
 * Check Inn Systems
 *
 * This file is for registering widgets
 *
 * @package Check Inn Systems
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * Register widgets. Function called in after_theme_setup in genesis.php
 */
function check_inn_systems_register_widgets() {

	genesis_register_sidebar(
		[
		'id'          => 'shop-sidebar',
		'name'        => __( 'Shop Sidebar', 'check-inn-systems' ),
		'description' => __( 'This widget will show up on the shop pages.', 'check-inn-systems' ),
		]
	);

	genesis_register_sidebar(
		[
		'id'          => 'before-header-right',
		'name'        => __( 'Before Header Right', 'check-inn-systems' ),
		'description' => __( 'This is the Before Header Right widget area', 'check-inn-systems' ),
		]
	);

}

