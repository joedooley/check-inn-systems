<?php
/**
 * Plugin Name:  WooCommerce Custom Endpoints
 * Plugin URI:   http://www.checkinn.com.au/
 * Description:  Adds a new endpoint to WooCommerce My Account section.
 * Author:       Joe Dooley
 * Author URI:   http://www.developingdesigns.com/
 * Version:      1.0.0
 * Text Domain:  woocommerce-custom-endpoint
 * Domain Path:  languages
 * Requires PHP: 5.4
 *
 * WooCommerce Custom Endpoints is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Publicicense as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WooCommerce Custom Endpoints is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooCommerce Custom Endpoints. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    JD_WooCommerce_Custom_Endpoints
 * @author     Joe Dooley <hello@developingdesigns.com>
 * @license    GPL-2.0+
 * @copyright  2015 Joe Dooley, Developing Designs
 */



/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}


add_action( 'init', 'dd_custom_endpoints' );
/**
 * Register new endpoint to use inside My Account page.
 *
 * @see   https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
 * @since 1.0.0
 */
function dd_custom_endpoints() {
	add_rewrite_endpoint( 'support-tickets', EP_ROOT | EP_PAGES );
}


add_filter( 'query_vars', 'dd_custom_query_vars', 0 );
/**
 * Add new query var.
 *
 * @param array $vars
 *
 * @return array
 * @since 1.0.0
 */
function dd_custom_query_vars( $vars ) {
	$vars[] = 'support-tickets';

	return $vars;
}


register_activation_hook( __FILE__, 'dd_flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'dd_flush_rewrite_rules' );
/**
 * Flush rewrite rules on plugin activation.
 *
 * @since 1.0.0
 */
function dd_flush_rewrite_rules() {
	add_rewrite_endpoint( 'support-tickets', EP_ROOT | EP_PAGES );
	flush_rewrite_rules();
}


add_filter( 'woocommerce_account_menu_items', 'dd_add_menu_items' );
/**
 * Insert the new endpoint into the My Account menu. Removes the
 * Logout menu item, then add Support Tickets and finally adds
 * the Logout menu item back at the bottom of the menu.
 *
 * @param array $items
 *
 * @return array
 * @since 1.0.0
 */
function dd_add_menu_items( $items ) {
	$logout = $items['customer-logout'];
	unset( $items['customer-logout'] );

	$items['support-tickets'] = __( 'Support Tickets', 'woocommerce' );

	$items['customer-logout'] = $logout;

	return $items;
}


add_action( 'woocommerce_account_support_tickets_endpoint', 'dd_endpoint_content' );
/**
 * Endpoint HTML content.
 */
function dd_endpoint_content() {
	echo do_shortcode( '[gravityview id="145"]' );
}


