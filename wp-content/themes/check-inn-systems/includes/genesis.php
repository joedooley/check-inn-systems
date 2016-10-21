<?php
/**
 * Check Inn Systems
 *
 * This file is for Genesis functions
 *
 * @package Check Inn Systems
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'check_inn_systems_add_theme_support' );
/**
 * Add theme support features on after-theme-setup hook
 *
 * @author Joe Dooley
 *
 */
function check_inn_systems_add_theme_support() {

	add_theme_support( 'custom-background' );
	add_theme_support( 'genesis-responsive-viewport' );
	add_theme_support( 'genesis-after-entry-widget-area' );
	add_theme_support( 'genesis-connect-woocommerce' );

	add_theme_support( 'html5', [
			'caption',
			'comment-form',
			'comment-list',
			'gallery',
			'search-form',
	] );

	add_theme_support( 'genesis-accessibility', [
			'404-page',
			'drop-down-menu',
			'headings',
			'rems',
			'search-form',
			'skip-links',
	] );

	add_theme_support( 'genesis-menus', [
			'primary'   => __( 'After Header Menu', 'check-inn-systems' ),
			'secondary' => __( 'Footer Menu', 'check-inn-systems' ),
	] );

	add_theme_support( 'custom-header', [
			'width'           => 206,
			'height'          => 222,
			'header-selector' => '.site-title a',
			'header-text'     => false,
			'flex-height'     => true,
	] );

	add_theme_support( 'genesis-structural-wraps', [
			'header',
			'nav',
			'subnav',
			'inner',
			'footer-widgets',
			'footer',
	] );

	/**
	 * Load child theme text domain
	 */
	load_child_theme_textdomain( 'check-inn-systems', apply_filters( 'child_theme_textdomain', get_stylesheet_directory() . '/languages', 'check-inn-systems' ) );

	/**
	 * Enqueue scripts and styles in scripts-and-styles.php
	 */
	add_action( 'wp_enqueue_scripts', 'check_inn_systems_enqueue_scripts_styles' );

	/**
	 * Register widgets in widgets.php
	 */
	add_action( 'widgets_init', 'check_inn_systems_register_widgets' );

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


// if header image is set, remove Header Right widget area and inject CSS to apply the header image as background image for home menu item and more
add_action( 'wp_head', 'check_inn_systems_home_menu_item_background_image' );
function check_inn_systems_home_menu_item_background_image() {

	if ( get_header_image() ) {
		// Remove the header right widget area
		unregister_sidebar( 'header-right' ); ?>

		<style type = "text/css">
			.nav-primary li.menu-item-home a {
				background-image: url(<?php echo get_header_image(); ?>);
				text-indent: -9999em;
				width: 100px;
				height: 100px;
			}

			@media only screen and (min-width: 1024px) {
				.site-header > .wrap {
					padding: 0;
				}

				.title-area {
					display: none;
				}

				.nav-primary {
					padding: 20px 0;
				}

				.menu-primary {
					display: -webkit-box;
					display: -webkit-flex;
					display: -ms-flexbox;
					display: flex;
					-webkit-box-pack: center;
					-webkit-justify-content: center;
					-ms-flex-pack: center;
					justify-content: center; /* center flex items horizontally */
					-webkit-box-align: center;
					-webkit-align-items: center;
					-ms-flex-align: center;
					align-items: center; /* center flex items vertically */
				}
			}
		</style>
	<?php }

}
