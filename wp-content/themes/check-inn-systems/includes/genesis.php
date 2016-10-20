<?php
/**
 * Check Inn Systems
 *
 * This file is for Genesis functions
 *
 * @package Check Inn Systems
 */

add_action( 'after_setup_theme', 'spa_add_theme_support' );
/**
 * Add theme support features on after-theme-setup hook
 *
 * @author Joe Dooley
 *
 */
function spa_add_theme_support() {

	add_theme_support( 'custom-background' );
	add_theme_support( 'genesis-responsive-viewport' );
	add_theme_support( 'genesis-after-entry-widget-area' );
	add_theme_support( 'genesis-connect-woocommerce' );

	add_theme_support(
		'html5',
		[
			'caption',
			'comment-form',
			'comment-list',
			'gallery',
			'search-form',
		]
	);

	add_theme_support(
		'genesis-accessibility',
		[
			'404-page',
			'drop-down-menu',
			'headings',
			'rems',
			'search-form',
			'skip-links',
		]
	);

	add_theme_support(
		'genesis-menus',
		[
			'primary'   => __( 'After Header Menu', 'check-inn-systems' ),
			'secondary' => __( 'Footer Menu', 'check-inn-systems' ),
		]
	);

	add_theme_support(
		'custom-header',
		array(
			'width'           => 600,
			'height'          => 160,
			'header-selector' => '.site-title a',
			'header-text'     => false,
			'flex-height'     => true,
			)
	);

	add_theme_support(
		'genesis-structural-wraps',
		[
			'header',
			'nav',
			'subnav',
			'inner',
			'footer-widgets',
			'footer',
		]
	);

	/**
	 * Load child theme text domain
	 */
	load_child_theme_textdomain( 'check-inn-systems', apply_filters( 'child_theme_textdomain', get_stylesheet_directory() . '/languages', 'check-inn-systems' ) );

	/**
	 * Enqueue scripts and styles in scripts-and-styles.php
	 */
	add_action( 'wp_enqueue_scripts', 'check_inn_systems_enqueue_scripts_styles' );

	/**
	 * Reposition the secondary navigation menu
	 */
	remove_action( 'genesis_after_header', 'genesis_do_subnav' );
	add_action( 'genesis_footer', 'genesis_do_subnav', 5 );

	/**
	 * Reduce the secondary navigation menu to one level depth
	 */
	add_filter( 'wp_nav_menu_args', 'check_inn_systems_secondary_menu_args' );

	/**
	 * Remove site description
	 */
	remove_action( 'genesis_site_description', 'genesis_seo_site_description' );

	/**
	 * Unregistered Header-Right Widget Area
	 */
	unregister_sidebar( 'header-right' );

}


/**
 * Reduce the secondary navigation menu to one level depth
 *
 * @param $args
 *
 * @return mixed
 * @uses spa_add_theme_support()
 */
function check_inn_systems_secondary_menu_args( $args ) {

	if ( 'secondary' !== $args['theme_location'] ) {
		return $args;
	}

	$args['depth'] = 1;

	return $args;

}


/**
 * Register custom image sizes
 */
add_action( 'init', function () {
	add_image_size( 'featured-image', 720, 400, true );
} );


/**
 * Modify size of the Gravatar in the author box
 *
 * @param $size
 *
 * @return int
 */
add_filter( 'genesis_author_box_gravatar_size', function( $size ) {
	return 90;
} );


/**
 * Modify size of the Gravatar in the entry comments
 *
 * @param $args
 *
 * @return mixed
 */
add_filter( 'genesis_comment_list_args', function( $args ) {
	$args['avatar_size'] = 60;
	return $args;
} );

