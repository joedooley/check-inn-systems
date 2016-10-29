<?php
/**
 * Check Inn Systems
 *
 * This file is for theme functions
 *
 * @package Check Inn Systems
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * Set it and Forget it! Fix Gravity Form Tabindex Conflicts.
 * Assign the $starting_index variable to a high number.
 *
 * @link     http://gravitywiz.com/fix-gravity-form-tabindex-conflicts/
 *
 * @param        $tab_index
 * @param   bool $form
 *
 * @return  int
 */
add_filter( 'gform_tabindex', 'check_inn_systems_forget_about_tabindex', 10, 2 );
function check_inn_systems_forget_about_tabindex( $tab_index, $form = false ) {

	$starting_index = 1000;

	if ( $form ) {
		add_filter( 'gform_tabindex_' . $form['id'], 'check_inn_systems_forget_about_tabindex' );
	}

	return GFCommon::$tab_index >= $starting_index ? GFCommon::$tab_index : $starting_index;
}


// Enable shortcodes in widgets
add_filter( 'widget_text', 'do_shortcode' );


// Enable PHP in widgets
add_filter( 'widget_text', 'check_inn_systems_execute_php', 100 );
function check_inn_systems_execute_php( $html ) {
	if ( strpos( $html, "<" . "?php" ) !== false ) {
		ob_start();
		eval( "?" . ">" . $html );
		$html = ob_get_contents();
		ob_end_clean();
	}

	return $html;
}


add_filter( 'upload_mimes', 'check_inn_systems_svg_mime_types' );
/**
 * Allow SVG's in the WordPress uploader.
 * @author Joe Dooley
 *
 * @param $mimetypes
 *
 * @return mixed
 */
function check_inn_systems_svg_mime_types( $mimetypes ) {
	$mimetypes['svg'] = 'image/svg+xml';

	return $mimetypes;
}


add_action( 'admin_head', 'check_inn_systems_svg_size' );
/**
 * Hack to make SVG's look normal in the WordPress media library.
 * @author Joe Dooley
 */
function check_inn_systems_svg_size() {
	echo '<style>
    svg, img[src*=".svg"] {
      max-width: 150px !important;
      max-height: 150px !important;
    }
  </style>';
}


/**
 * Function displaying Flexible Content Fields on homepage.
 */
function check_inn_systems_acf_flexible_content() {

	while ( have_rows( 'flexible_content' ) ) : the_row();

		if ( get_row_layout() === 'hero' ) {

			get_template_part( 'partials/acf', 'primary-hero' );

		} elseif ( get_row_layout() === 'full_row' ) {

			get_template_part( 'partials/acf', 'full-row' );

		} elseif ( get_row_layout() === 'faq' ) {

			get_template_part( 'partials/acf', 'faq-row' );

		}

	endwhile;

}

