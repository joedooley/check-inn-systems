<?php
/**
 * Check Inn Systems
 *
 * This file is for WooCommerce functions
 *
 * @package Check Inn Systems
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_action( 'wp_enqueue_scripts', 'spa_conditionally_load_woc_js_css' );
/**
 * Dequeue WooCommerce Scripts and Styles for pages that don't need them.
 */
function spa_conditionally_load_woc_js_css() {

	if ( function_exists( 'spa_conditionally_load_woc_js_css' ) ) {

		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {

			wp_dequeue_script( 'woocommerce' );
			wp_dequeue_script( 'wc-add-to-cart' );
			wp_dequeue_script( 'wc-cart-fragments' );

			wp_dequeue_style( 'woocommerce-general' );
			wp_dequeue_style( 'woocommerce-layout' );
			wp_dequeue_style( 'woocommerce-smallscreen' );
		}

		if ( is_product() ) {
			wp_dequeue_style( 'pac-styles-css' );
			wp_dequeue_style( 'pac-layout-styles-css' );
		}
	}

}


/**
 * Replace primary sidebar with shop-sidebar on WooCommerce archives.
 * Remove Genesis breadcrumbs.
 *
 * @uses spa_do_shop_sidebar()
 */
add_action( 'get_header', function () {
	if ( is_shop() || is_product_taxonomy() ) {
		remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );

		remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
		add_action( 'genesis_sidebar', 'spa_do_shop_sidebar' );
	}

	/**
	 * Output shop-sidebar.
	 */
	function spa_do_shop_sidebar() {
		dynamic_sidebar( 'shop-sidebar' );
	}
} );


add_filter( 'genesis_site_layout', 'check_inn_systems_wc_force_full_width' );
/**
 * Force full width layout on WooCommerce pages
 * @return string
 */
function check_inn_systems_wc_force_full_width() {
	if ( is_shop() || is_product_taxonomy() ) {
		return 'sidebar-content';
	}
}


add_filter( 'genesis_attr_content', 'check_inn_systems_add_facetwp_class' );
/**
 * Add the class needed for FacetWP to main element.
 *
 * Context: Posts page, all Archives and Search results page.
 *
 * @param $attributes
 *
 * @return mixed
 */
function check_inn_systems_add_facetwp_class( $attributes ) {
	if ( is_shop() || is_product_taxonomy() ) {
		$attributes['class'] .= ' facetwp-template';
	}

	return $attributes;

}


add_filter( 'woocommerce_show_page_title', 'check_inn_systems_remove_shop_title' );
/**
 * Removes the "shop" title on the main shop page
 */
function check_inn_systems_remove_shop_title() {
	if ( is_shop() || is_product_taxonomy() ) {
		return false;
	}
}


/**
 * Remove WooCommerce orderby dropdown and showing all results.
 */
add_action( 'get_header', function () {
	if ( is_woocommerce() ) {
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	}
} );


/**
 * Move product price to just before add to cart button.
 */
add_action( 'get_header', function () {
	if ( is_front_page() || is_shop() || is_product_taxonomy() || is_product() ) {
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_price', 6 );
	}
} );


/**
 * Remove WooCommerce breadcrumbs, using Genesis crumbs instead.
 */
add_action( 'get_header', function () {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
} );


add_filter( 'woocommerce_product_tabs', 'check_inn_systems_woo_remove_product_tabs', 98 );
/**
 * Delete WooCommerce Product Tabs.
 *
 * @param $tabs
 *
 * @return mixed
 */
function check_inn_systems_woo_remove_product_tabs( $tabs ) {
	unset( $tabs['description'] );
	unset( $tabs['reviews'] );
	unset( $tabs['additional_information'] );

	return $tabs;
}


/**
 * Set shop page products to 18 per page
 */
add_filter( 'loop_shop_per_page', create_function( '$cols', 'return 18;' ), 20 );



add_shortcode('cis_product_categories', 'check_inn_systems_product_categories');
/**
 * Custom shortcode for product categories with descriptions
 * @param $atts
 * @return string
 */
function check_inn_systems_product_categories( $atts ) {
        global $woocommerce_loop;

        $atts = shortcode_atts( array(
            'number'     => null,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'columns'    => '4',
            'hide_empty' => 1,
            'parent'     => '',
            'ids'        => ''
        ), $atts );

        if ( isset( $atts['ids'] ) ) {
            $ids = explode( ',', $atts['ids'] );
            $ids = array_map( 'trim', $ids );
        } else {
            $ids = array();
        }

        $hide_empty = ( $atts['hide_empty'] == true || $atts['hide_empty'] == 1 ) ? 1 : 0;

        // get terms and workaround WP bug with parents/pad counts
        $args = array(
            'orderby'    => $atts['orderby'],
            'order'      => $atts['order'],
            'hide_empty' => $hide_empty,
            'include'    => $ids,
            'pad_counts' => true,
            'child_of'   => $atts['parent']
        );

        $product_categories = get_terms( 'product_cat', $args );

        if ( '' !== $atts['parent'] ) {
            $product_categories = wp_list_filter( $product_categories, [ 'parent' => $atts['parent'] ] );
        }

        if ( $hide_empty ) {
            foreach ( $product_categories as $key => $category ) {
                if ( $category->count == 0 ) {
                    unset( $product_categories[ $key ] );
                }
            }
        }

        if ( $atts['number'] ) {
            $product_categories = array_slice( $product_categories, 0, $atts['number'] );
        }

        $columns = absint( $atts['columns'] );
        $woocommerce_loop['columns'] = $columns;

        ob_start();

        if ( $product_categories ) {
            ?>
            <div class="woocommerce columns-<?php echo $columns;?>">
                <ul class="products alternating-list">
            <?php
            foreach ( $product_categories as $category ) {
                ?>
                <li class="product-category product">
                    <a href="<?php echo get_category_link($category); ?>">
                        <?php

                        echo '<h3>' . $category->name . '</h3>';

                        woocommerce_subcategory_thumbnail( $category );

                        ?>
                     </a>

                     <?php
                     echo '<p class="pcat-description">'.$category->description.'</p>';
                     ?>
                </li>

                <?php
            }

            woocommerce_product_loop_end();
        }

        woocommerce_reset_loop();

        return '<div class="woocommerce columns-' . $columns . '">' . ob_get_clean() . '</div>';
}
