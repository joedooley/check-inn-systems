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
	genesis_register_sidebar( array(
		'id'          => 'shop-sidebar',
		'name'        => __( 'Shop Sidebar', 'epik' ),
		'description' => __( 'This widget will show up on the shop pages.', 'epik' ),
	) );
}

