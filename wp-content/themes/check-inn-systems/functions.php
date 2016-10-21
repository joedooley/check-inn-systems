<?php
/**
 * Check Inn Systems
 *
 * This file adds functions to the Check Inn Systems Theme.
 *
 * @package Check Inn Systems
 */


/**
 * Include theme files and declare child theme constants.
 */
include_once( get_stylesheet_directory() . '/includes/scripts-and-styles.php' );
include_once( get_stylesheet_directory() . '/includes/widgets.php' );
include_once( get_stylesheet_directory() . '/includes/genesis.php' );
include_once( get_stylesheet_directory() . '/includes/theme-functions.php' );
include_once( get_stylesheet_directory() . '/includes/woocommerce.php' );
include_once( get_stylesheet_directory() . '/lib/output.php' );
require_once( get_stylesheet_directory() . '/lib/customize.php' );

define( 'CHILD_THEME_NAME', 'Check Inn Systems' );
define( 'CHILD_THEME_URL', 'http://www.studiopress.com/' );
define( 'CHILD_THEME_VERSION', '1.0.0' );
