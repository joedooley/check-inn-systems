<?php
/**
 * Check Inn Systems
 *
 * This file is for theme shortcodes
 *
 * @package Check Inn Systems
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



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
