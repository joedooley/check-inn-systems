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


add_filter( 'genesis_site_layout', 'check_inn_systems_wc_force_full_width' );
/**
 * Force full width layout on WooCommerce pages
 * @return string
 */
function check_inn_systems_wc_force_full_width() {
	if ( is_shop() || is_product_taxonomy() ) {
		return 'sidebar-content';
	}
}


add_filter( 'genesis_attr_content', 'check_inn_systems_add_facetwp_class' );
/**
 * Add the class needed for FacetWP to main element.
 *
 * Context: Posts page, all Archives and Search results page.
 *
 * @param $attributes
 *
 * @return mixed
 */
function check_inn_systems_add_facetwp_class( $attributes ) {
	if ( is_shop() || is_product_taxonomy() ) {
		echo 'im a dick';
		$attributes['class'] .= ' facetwp-template';
	}

	return $attributes;

}
