<?php
/**
 * Check Inn Systems
 *
 * This file is for WooCommerce functions
 *
 * @package Check Inn Systems
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * Replace primary sidebar with shop-sidebar on WooCommerce archives.
 * Remove Genesis breadcrumbs.
 *
 * @uses spa_do_shop_sidebar()
 */
add_action( 'get_header', function () {
	if ( is_shop() || is_product_taxonomy() ) {
		remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );

		remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
		add_action( 'genesis_sidebar', 'spa_do_shop_sidebar' );
	}

	/**
	 * Output shop-sidebar.
	 */
	function spa_do_shop_sidebar() {
		dynamic_sidebar( 'shop-sidebar' );
	}
} );
