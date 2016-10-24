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
include_once __DIR__ . '/includes/scripts-and-styles.php';
include_once __DIR__ . '/includes/widgets.php';
include_once __DIR__ . '/includes/genesis.php';
include_once __DIR__ . '/includes/theme-functions.php';
include_once __DIR__ . '/includes/theme-options-page.php';
include_once __DIR__ . '/includes/woocommerce.php';
include_once __DIR__ . '/lib/output.php';
require_once __DIR__ . '/lib/customize.php';
include_once __DIR__ . '/includes/layout.php';


/**
 * Define child theme constants
 */
define( 'CHILD_THEME_NAME', 'Check Inn Systems' );
define( 'CHILD_THEME_URL', 'http://www.studiopress.com/' );
define( 'CHILD_THEME_VERSION', '1.0.0' );


/**
 * Add theme support for Genesis Connect WocCommerce
 */
add_theme_support( 'genesis-connect-woocommerce' );
