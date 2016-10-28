<?php
/**
 * Plugin Name:  Register Post Types and Taxonomies
 * Plugin URI:   http://www.checkinn.com.au/
 * Description:  Register CPT's and custom Taxonomies for Check Inn Systems child theme.
 * Author:       Joe Dooley
 * Author URI:   http://www.developingdesigns.com/
 * Version:      1.0.0
 * Text Domain:  register-post-types-and-tax
 * Domain Path:  languages
 *
 * Register Post Types and Taxonomies is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Publicicense as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Register Post Types and Taxonomies is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Register Post Types and Taxonomies. If not, see <http://www.gnu.org/licenses/>.
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


/**
 * Register CPT's and Taxonomies
 *
 * A class filled with functions that will never go away upon theme deactivation.
 *
 * @package    Register_Post_Type_And_Tax
 * @since 1.0.0
 */
class DD_Register_CPT_Tax {

	public function __construct() {

		add_action( 'init', [ $this, 'add_cpt' ] );
		add_action( 'init', [ $this, 'add_tax' ] );

	}

	/**
	 * Register child theme CPT's
	 *
	 * @since 1.0.0
	 */
	public function add_cpt() {

		$labels = [
			'name'          => __( "FAQ's", '' ),
			'singular_name' => __( 'FAQ', '' ),
		];

		$args = [
			'label'               => __( "FAQ's", '' ),
			'labels'              => $labels,
			'description'         => '',
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'rest_base'           => 'faqs',
			'has_archive'         => true,
			'show_in_menu'        => true,
			'exclude_from_search' => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'hierarchical'        => false,

			'rewrite' => [
				'slug'       => 'faqs',
				'with_front' => true,
			],

			'query_var' => true,
			'menu_icon' => 'dashicons-megaphone',

			'supports' => [
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'trackbacks',
				'revisions',
				'page-attributes',
				'post-formats',
				'genesis-cpt-archives-settings',
			],

		];

		register_post_type(
			'faqs',
			$args
		);

	}


	/**
	 * Register child theme Taxonomies
	 *
	 * @since 1.0.0
	 */
	public function add_tax() {

		$labels = [
			'name'          => __( 'FAQ Categories', '' ),
			'singular_name' => __( 'FAQ Category', '' ),
		];

		$args = [
			'label'              => __( 'FAQ Categories', '' ),
			'labels'             => $labels,
			'public'             => true,
			'hierarchical'       => false,
			'label'              => 'FAQ Categories',
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'query_var'          => true,

			'rewrite'            => [
				'slug'       => 'faq_cat',
				'with_front' => true,
			],

			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'rest_base'          => 'faq_cat',
			'show_in_quick_edit' => true,
		];

		register_taxonomy(
			'faq_cat',
			[ 'faqs' ],
			$args
		);

	}

}

if ( class_exists( 'DD_Register_CPT_Tax' ) ) {
	$dd_register_cpt_tax = new DD_Register_CPT_Tax();
}

