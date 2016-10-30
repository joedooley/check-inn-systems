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


class My_Custom_My_Account_Endpoint {


	/**
	 * Custom endpoint name.
	 *
	 * @var string
	 */
	public static $endpoint = 'quotes';

	/**
	 * Plugin actions.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoints' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_filter( 'the_title', array( $this, 'endpoint_title' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'new_menu_items' ) );
		add_action( 'woocommerce_account_' . self::$endpoint . '_endpoint', array( $this, 'endpoint_content' ) );
	}

	/**
	 * Register new endpoint to use inside My Account page.
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function add_endpoints() {
		add_rewrite_endpoint( self::$endpoint, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add new query var.
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = self::$endpoint;

		return $vars;
	}

	/**
	 * Set endpoint title.
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	public function endpoint_title( $title ) {
		global $wp_query;

		$is_endpoint = isset( $wp_query->query_vars[ self::$endpoint ] );
		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {

			$title = __( 'Quotes', 'woocommerce' );
			remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
		}

		return $title;
	}

	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	public function new_menu_items( $items ) {

		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );

		$items[ self::$endpoint ] = __( 'Quotes', 'woocommerce' );

		$items['customer-logout'] = $logout;

		return $items;
	}

	/**
	 * Endpoint HTML content.
	 */
	public function endpoint_content() {
		wc_get_template( 'myaccount/navigation.php' );

		echo do_shortcode( '[gravityview id="145"]' );
	}

	/**
	 * Plugin install action.
	 * Flush rewrite rules to make our custom endpoint available.
	 */
	public static function install() {
		flush_rewrite_rules();
	}
}


new My_Custom_My_Account_Endpoint();

register_activation_hook( __FILE__, array( 'My_Custom_My_Account_Endpoint', 'install' ) );

