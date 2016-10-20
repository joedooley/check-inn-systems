<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Beaver_Tunnels_Registered_Actions {

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		$this->add_filters();
	}

	/**
	 * Add the filters for the available action hooks
	 *
	 * @since 1.0.0
	 */
	public function add_filters() {

		add_filter( 'beaver_tunnels', array( $this, 'bb_theme' ) );
		add_filter( 'beaver_tunnels', array( $this, 'genesis' ) );
		add_filter( 'beaver_tunnels', array( $this, 'edd' ) );
		add_filter( 'beaver_tunnels', array( $this, 'givewp' ) );
		add_filter( 'beaver_tunnels', array( $this, 'woocommerce' ) );

	}

	public function bb_theme( $actions = array() ) {

		$bb_theme = wp_get_theme('bb-theme');
		if ( ! $bb_theme->exists() ) {
			return $actions;
		}

		$actions[] = array(
			'title'   => __( 'Beaver Builder Theme', 'beaver-tunnels' ),
			'actions' => array(
				'fl_body_open',
				'fl_page_open',
				'fl_before_top_bar',
				'fl_top_bar_col1_open',
				'fl_top_bar_col1_close',
				'fl_top_bar_col2_open',
				'fl_top_bar_col2_close',
				'fl_after_top_bar',
				'fl_before_header',
				'fl_header_content_open',
				'fl_header_content_close',
				'fl_after_header',
				'fl_before_content',
				'fl_content_open',
				'fl_post_top_meta_open',
				'fl_post_top_meta_close',
				'fl_post_bottom_meta_open',
				'fl_post_bottom_meta_close',
				'fl_comments_open',
				'fl_comments_close',
				'fl_sidebar_open',
				'fl_sidebar_close',
				'fl_content_close',
				'fl_after_content',
				'fl_footer_wrap_open',
				'fl_before_footer_widgets',
				'fl_after_footer_widgets',
				'fl_before_footer',
				'fl_after_footer',
				'fl_footer_col1_open',
				'fl_footer_col1_close',
				'fl_footer_col2_open',
				'fl_footer_col2_close',
				'fl_footer_wrap_close',
				'fl_page_close',
				'fl_body_close',
			)
		);

		return $actions;

	}

	/**
	 * Genesis Framework Actions
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $actions Available action hooks
	 *
	 * @return array        New array of action hooks
	 */
	public function genesis( $actions = array() ) {

		$genesis_theme = wp_get_theme('genesis');
		if ( ! $genesis_theme->exists() || ! is_child_theme() ) {
			return $actions;
		}

		$current_theme = wp_get_theme();
		if ( 'genesis' !== $current_theme->parent()->template ) {
			return $actions;
		}

		$actions[] = array(
			'title'   => __( 'Genesis Framework', 'beaver-tunnels' ),
			'actions' => array(
				'genesis_before',
				'genesis_after',
				'genesis_header',
				'genesis_before_header',
				'genesis_after_header',
				'genesis_site_title',
				'genesis_site_description',
				'genesis_header_right',
				'genesis_before_content_sidebar_wrap',
				'genesis_after_content_sidebar_wrap',
				'genesis_before_content',
				'genesis_after_content',
				'genesis_sidebar',
				'genesis_before_sidebar_widget_area',
				'genesis_after_sidebar_widget_area',
				'genesis_sidebar_alt',
				'genesis_before_sidebar_alt_widget_area',
				'genesis_after_sidebar_alt_widget_area',
				'genesis_before_footer',
				'genesis_footer',
				'genesis_after_footer',
				'genesis_before_loop',
				'genesis_loop',
				'genesis_after_loop',
				'genesis_after_endwhile',
				'genesis_loop_else',
				'genesis_before_entry',
				'genesis_after_entry',
				'genesis_entry_header',
				'genesis_before_entry_content',
				'genesis_entry_content',
				'genesis_after_entry_content',
				'genesis_entry_footer',
				'genesis_before_post',
				'genesis_after_post',
				'genesis_before_post_title',
				'genesis_post_title',
				'genesis_after_post_title',
				'genesis_before_post_content',
				'genesis_post_content',
				'genesis_after_post_content',
				'genesis_before_comments',
				'genesis_comments',
				'genesis_after_comments',
				'genesis_list_comments',
				'genesis_before_pings',
				'genesis_pings',
				'genesis_after_pings',
				'genesis_list_pings',
				'genesis_before_comment',
				'genesis_after_comment',
				'genesis_before_comment_form',
				'genesis_comment_form',
				'genesis_after_comment_form',
			)
		);

		return $actions;

	}

	/**
	 * Easy Digital Downloads Actions
	 *
	 * @since 1.0.0
	 *
	 * @param  array $actions Available action hooks
	 *
	 * @return array        New array of action hooks
	 */
	public function edd( $actions = array() ) {

		if ( ! is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {
			return $actions;
		}

		$actions[] = array(
			'title'   => __( 'Easy Digital Downloads', 'beaver-tunnels' ),
			'actions' => array(
				'edd_before_download_content',
				'edd_after_download_content',
				'edd_purchase_form_register_fields',
				'edd_purchase_form_user_info',
				'edd_after_checkout_cart',
				'edd_purchase_link_top',
				'edd_purchase_link_end',
				'edd_empty_cart',
				'edd_after_price_option',
				'edd_after_purchase_form',
				'edd_checkout_login_fields_after',
				'edd_after_price_options',
				'edd_checkout_login_fields_before',
				'edd_after_cart',
				'edd_purchase_form_after_submit',
				'edd_purchase_form_before_submit',
				'edd_before_cart',
				'edd_before_price_options',
				'edd_before_checkout_cart',
				'edd_before_purchase_form',
				'edd_purchase_form_top',
				'edd_after_cc_fields',
				'edd_purchase_form_bottom',
				'edd_render_receipt_in_browser_after',
				'edd_render_receipt_in_browser_before',
				'edd_before_cc_fields',
				'edd_payment_mode_after_gateways',
				'edd_payment_mode_top',
				'edd_payment_mode_bottom',
				'edd_payment_mode_before_gateways',
				'edd_checkout_form_top',
			)
		);

		return $actions;

	}

	/**
	 * Give Actions
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $actions Available action hooks
	 *
	 * @return [type]        New array of action hooks
	 */
	public function givewp( $actions = array() ) {

		if ( ! is_plugin_active( 'give/give.php' ) ) {
			return $actions;
		}

		$actions[] = array(
			'title'   => __( 'Give', 'beaver-tunnels' ),
			'actions' => array(
				'give_after_cc_expiration',
				'give_after_cc_fields',
				'give_after_donation_levels',
				'give_after_forms_widget',
				'give_after_graph',
				'give_after_main_content',
				'give_after_single_form',
				'give_after_single_form_summary',
				'give_after_terms',
				'give_before_cc_fields',
				'give_before_donation_levels',
				'give_before_forms_widget',
				'give_before_graph',
				'give_before_main_content',
				'give_before_single_form',
				'give_before_single_form_summary',
				'give_before_terms',
				'give_checkout_before_gateway',
				'give_checkout_form_bottom',
				'give_checkout_form_top',
				'give_checkout_login_fields_after',
				'give_checkout_login_fields_before',
				'give_payment_mode_bottom',
				'give_payment_mode_top',
				'give_payment_receipt_after',
				'give_payment_receipt_before',
				'give_payments_page_bottom',
				'give_payments_page_top',
				'give_purchase_form_before_submit',
				'give_purchase_form_bottom',
				'give_purchase_form_top',
				'give_purchase_history_header_after',
				'give_purchase_history_header_before',
				'give_register_fields_after',
				'give_register_fields_before',
				'give_render_receipt_in_browser_after',
				'give_render_receipt_in_browser_before',
				'give_reports_page_bottom',
				'give_reports_page_top',
			)
		);

		return $actions;

	}

	public function woocommerce( $actions = array() ) {

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return $actions;
		}

		$actions[] = array(
			'title'   => __( 'WooCommerce', 'beaver-tunnels' ),
			'actions' => array(
				'pre_get_product_search_form',
				'woocommerce_after_available_downloads',
				'woocommerce_after_cart',
				'woocommerce_after_cart_table',
				'woocommerce_after_cart_totals',
				'woocommerce_after_checkout_billing_form',
				'woocommerce_after_checkout_form',
				'woocommerce_after_checkout_registration_form',
				'woocommerce_after_checkout_shipping_form',
				'woocommerce_after_main_content',
				'woocommerce_after_mini_cart',
				'woocommerce_after_order_notes',
				'woocommerce_after_shipping_calculator',
				'woocommerce_after_shop_loop',
				'woocommerce_after_shop_loop_item',
				'woocommerce_after_shop_loop_item_title',
				'woocommerce_after_single_product',
				'woocommerce_after_single_product_summary',
				'woocommerce_after_subcategory',
				'woocommerce_after_subcategory_title',
				'woocommerce_archive_description',
				'woocommerce_auth_page_footer',
				'woocommerce_auth_page_header',
				'woocommerce_available_download_end',
				'woocommerce_available_download_start',
				'woocommerce_before_available_downloads',
				'woocommerce_before_cart',
				'woocommerce_before_cart_table',
				'woocommerce_before_cart_totals',
				'woocommerce_before_checkout_billing_form',
				'woocommerce_before_checkout_form',
				'woocommerce_before_checkout_registration_form',
				'woocommerce_before_checkout_shipping_form',
				'woocommerce_before_main_content',
				'woocommerce_before_mini_cart',
				'woocommerce_before_order_notes',
				'woocommerce_before_shipping_calculator',
				'woocommerce_before_shop_loop',
				'woocommerce_before_shop_loop_item',
				'woocommerce_before_shop_loop_item_title',
				'woocommerce_before_single_product',
				'woocommerce_before_single_product_summary',
				'woocommerce_cart_actions',
				'woocommerce_cart_collaterals',
				'woocommerce_cart_coupon',
				'woocommerce_cart_has_errors',
				'woocommerce_cart_is_empty',
				'woocommerce_checkout_after_customer_details',
				'woocommerce_checkout_after_order_review',
				'woocommerce_checkout_before_customer_details',
				'woocommerce_checkout_before_order_review',
				'woocommerce_checkout_billing',
				'woocommerce_checkout_order_review',
				'woocommerce_checkout_shipping',
				'woocommerce_edit_account_form',
				'woocommerce_edit_account_form_end',
				'woocommerce_edit_account_form_start',
				'woocommerce_lostpassword_form',
				'woocommerce_order_details_after_order_table',
				'woocommerce_pay_order_after_submit',
				'woocommerce_pay_order_before_submit',
				'woocommerce_proceed_to_checkout',
				'woocommerce_product_meta_end',
				'woocommerce_product_meta_start',
				'woocommerce_product_thumbnails',
				'woocommerce_review_after_comment_text',
				'woocommerce_review_before_comment_meta',
				'woocommerce_review_before_comment_text',
				'woocommerce_review_order_after_payment',
				'woocommerce_review_order_after_submit',
				'woocommerce_review_order_before_payment',
				'woocommerce_review_order_before_submit',
				'woocommerce_sidebar',
				'woocommerce_single_product_summary',
				'woocommerce_thankyou',
				'woocommerce_view_order',
				'woocommerce_widget_shopping_cart_before_buttons',
			)
		);

		return $actions;

	}

}
new Beaver_Tunnels_Registered_Actions();
