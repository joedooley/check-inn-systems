<?php
/**
 * This file contains markup for the homepage
 *
 * @package    SportPort Active
 * @author     Developing Designs - Joe Dooley
 * @link       https://www.developingdesigns.com
 * @copyright  Joe Dooley, Developing Designs
 * @license    GPL-2.0+
 */

if ( ! function_exists( 'check_inn_systems_fc_check' ) ) {

	add_action( 'get_header', 'check_inn_systems_fc_check' );
	/**
	 * Outputs ACF flexible content fields. See
	 * '/assets/functions/theme-functions.php'
	 * for details.
	 */
	function check_inn_systems_fc_check() {

		if ( have_rows( 'flexible_content' ) ) {

			add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
			remove_action( 'genesis_loop', 'genesis_do_loop' );
			add_action( 'genesis_loop', 'check_inn_systems_acf_flexible_content' );

			add_theme_support(
			    'genesis-structural-wraps',
                [
                    'header',
                    //'nav',
                    //'subnav',
                    // 'site-inner',
                    'footer-widgets',
                    'footer'
                ]
            );

		}
	}
}


add_action( 'wp_enqueue_scripts', function() {

	wp_enqueue_script(
		'backstretch',
		get_stylesheet_directory_uri() . '/dist/js/packages/jquery.backstretch.min.js',
		['jquery'],
		'2.0.4',
		true
	);

	wp_enqueue_script(
		'backstretch-set-front-page',
		get_stylesheet_directory_uri() . '/dist/js/single/backstretch-set-front-page.js',
		['jquery', 'backstretch'],
		CHILD_THEME_VERSION,
		true
	);

});

//add_action( 'woocommerce_after_subcategory_title', 'my_theme_woocommerce_taxonomy_archive_description');
function my_theme_woocommerce_taxonomy_archive_description() {
    if ( is_front_page() ) {
        global $post, $product;
        $categ = $product->get_categories();
        $term = get_term_by ( 'name' , strip_tags($categ), 'product_cat' );
        echo $term->description;

    }
}


genesis();
