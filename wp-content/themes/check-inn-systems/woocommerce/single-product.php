<?php
/**
 * This template displays the single Product
 *
 * @package genesis_connect_woocommerce
 * @version 0.9.8
 *
 * Note for customisers/users: Do not edit this file!
 * ==================================================
 * If you want to customise this template, copy this file (keep same name) and place the
 * copy in the child theme's woocommerce folder, ie themes/my-child-theme/woocommerce
 * (Your theme may not have a 'woocommerce' folder, in which case create one.)
 * The version in the child theme's woocommerce folder will override this template, and
 *
 *
 * any future updates to this plugin won't wipe out your customisations.
 *
 */

/** Remove default Genesis loop */
remove_action( 'genesis_loop', 'genesis_do_loop' );

remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );
add_action( 'woocommerce_single_product_summary', 'genesis_do_breadcrumbs', 4 );

/** Remove Woo #container and #content divs */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

/** Switch Add To Cart and Summary sections */
remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 10 );
remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );



/**
 * Enqueue single page script accordion.js
 *
 * @return void
 * @todo Find JavaScript bug in scroll-to-fixed.js tomorrow
 */
add_action( 'wp_enqueue_scripts', function() {

	if ( is_product() ) {
		wp_enqueue_script(
			'accordion-js',
			get_stylesheet_directory_uri() . '/assets/js/custom/single/accordion.js',
			array( 'jquery' ),
			CHILD_THEME_VERSION,
			true
		);

		wp_enqueue_script(
			'slick-js',
			get_stylesheet_directory_uri() . '/assets/js/custom/single/slick.js',
			array( 'jquery' ),
			CHILD_THEME_VERSION,
			true
		);


		wp_enqueue_script(
			'slick-init-js',
			get_stylesheet_directory_uri() . '/assets/js/custom/single/slick-init.js',
			array( 'jquery', 'slick-js' ),
			CHILD_THEME_VERSION,
			true
		);

		wp_enqueue_script(
			'increment-decrement-js',
			get_stylesheet_directory_uri() . '/assets/js/custom/input-increment-decrement.js',
			array( 'jquery' ),
			CHILD_THEME_VERSION,
			true
		);

		wp_enqueue_style(
			'slick-css',
			get_stylesheet_directory_uri() . '/assets/css/slick.css',
			CHILD_THEME_VERSION
		);

//		if ( ! wp_is_mobile() ) {
//
//			wp_enqueue_script(
//				'scrolltofixed-init',
//				get_stylesheet_directory_uri() . '/assets/js/custom/single/scrolltofixed-init.js',
//				array( 'jquery' ),
//				CHILD_THEME_VERSION,
//				true
//			);
//
//		}
	}
});


add_action( 'woocommerce_single_product_summary', 'check_inn_systems_acf_accordion' );
/**
 * Outputs ACF Accordion Repeator on single product pages.
 */
function check_inn_systems_acf_accordion() {

	if ( have_rows( 'accordion' ) && is_product() ) {

		echo '<div id="accordion">';

		while ( have_rows( 'accordion' ) ) : the_row();

			$heading = get_sub_field( 'header' );
			$content = get_sub_field( 'hidden_content' );

			echo '<div class="accordion-item">';

			if ( $heading ) {
				echo '<h2 class = "accordion-heading heading">' . $heading . '</h2>';
			}

			if ( $content ) {
				echo '<div class = "accordion-content">' . $content . '</div>';
			}
			
			echo '</div>';

		endwhile;

		echo '</div>';

	}

}


add_action( 'genesis_loop', 'gencwooc_single_product_loop' );
/**
 * Displays single product loop
 *
 * Uses WooCommerce structure and contains all existing WooCommerce hooks
 *
 * Code based on WooCommerce 1.5.5 woocommerce_single_product_content()
 * @see woocommerce/woocommerce-template.php
 *
 * @since 0.9.0
 */
function gencwooc_single_product_loop() {

	do_action( 'woocommerce_before_main_content' );

	// Let developers override the query used, in case they want to use this function for their own loop/wp_query
	$wc_query = false;

	// Added a hook for developers in case they need to modify the query
	$wc_query = apply_filters( 'gencwooc_custom_query', $wc_query );

	if ( ! $wc_query) {

		global $wp_query;

		$wc_query = $wp_query;
	}

	if ( $wc_query->have_posts() ) while ( $wc_query->have_posts() ) : $wc_query->the_post(); ?>

		<?php do_action( 'woocommerce_before_single_product' ); ?>

		<div itemscope itemtype="http://schema.org/Product" id="product-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php do_action( 'woocommerce_before_single_product_summary' ); ?>

			<div class="summary">
				<div class="product-essential">

					<?php

					do_action( 'woocommerce_single_product_summary' );
					do_action( 'woocommerce_after_single_product_summary' );

					?>

				</div>
			</div>
		</div>

		<?php do_action( 'woocommerce_after_single_product' );

	endwhile;

	do_action( 'woocommerce_after_main_content' );
}

genesis();
