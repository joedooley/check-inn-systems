<?php
/**
 * Check Inn Systems
 *
 * This file adds functions to the Check Inn Systems Theme.
 *
 * @package Check Inn Systems
 */


/**
 * Bootstrap Genesis and include theme files.
 */
include_once get_template_directory() . '/lib/init.php';
include_once __DIR__ . '/includes/output.php';
require_once __DIR__ . '/includes/customize.php';
include_once __DIR__ . '/includes/genesis.php';
include_once __DIR__ . '/includes/scripts-and-styles.php';


/**
 * Define Child Theme Constants
 */
define( 'CHILD_THEME_NAME', 'Check Inn Systems' );
define( 'CHILD_THEME_URL', 'http://www.checkinn.com.au/' );
define( 'CHILD_THEME_VERSION', '1.0.0' );

