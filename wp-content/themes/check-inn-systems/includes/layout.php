<?php
/**
 * Check Inn Systems
 *
 * This file is for functions that affect
 * the front end layout.
 *
 * @package Check Inn Systems
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Output Free Shipping Notification into before header hook
 *
 * @since   1.0.0
 *
 * @return  null if the free_shipping_notification is empty
 */
add_action( 'genesis_before', function () {

	echo '<section class="before-header"><div class="wrap">';

	genesis_widget_area( 'before-header-left', array(
		'before' => '<div class="before-header-left-container"><div class="one-third first before-header-widget before-header-left">',
		'after'  => '</div></div>',
	) );

	if ( get_field( 'site_notification', 'option' ) ) {
		$free_shipping = get_field( 'site_notification', 'option' );

		echo '<div class="before-header-middle-container"><div class="before-header-widget one-third before-header-middle">';

		echo '<h5 class="free-shipping-notification">' . $free_shipping . '</h5>';

		echo '</div></div>';

	}

	genesis_widget_area( 'before-header-right', array(
		'before' => '<div class="before-header-right-container"><div class="one-third before-header-widget before-header-right">',
		'after'  => '</div></div>',
	) );

	echo '</div></section>';

} );


/**
 * Personalize the copyright output in the footer
 *
 * @param $output
 *
 */
add_filter( 'genesis_footer_creds_text', function( $output ) {
	if ( get_field( 'footer_copyright', 'option' ) ) {
		$footer_copyright = get_field( 'footer_copyright', 'option' );

		echo '<div class="footer-copyright">' . $footer_copyright . '</div>';

	}
} );
