<?php
/**
 * Check Inn Systems
 *
 * This file adds functions to the Check Inn Systems Theme.
 *
 * @package Check Inn Systems
 */


add_action( 'genesis_setup', 'check_inn_systems_includes_constants' );
/**
 * Include theme files and declare child theme constants.
 */
function check_inn_systems_includes_constants() {

	if ( ! is_admin() ) {
		include_once __DIR__ . '/includes/output.php';
		require_once __DIR__ . '/includes/customize.php';
		include_once __DIR__ . '/includes/genesis.php';
		include_once __DIR__ . '/includes/scripts-and-styles.php';

		define( 'CHILD_THEME_NAME', 'Check Inn Systems' );
		define( 'CHILD_THEME_VERSION', '1.0.0' );
	}

}
