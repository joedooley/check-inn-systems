<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once BEAVER_TUNNELS_PLUGIN_DIR . 'includes/meta-box-builder.php';

class Beaver_Tunnels_Meta_Box {

	private $textdomain = 'beaver-tunnels';
	private $prefix = '_beavertunnels_';

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'setup' ), 20 );
	}

	/**
	 * Setup the meta box
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setup() {

		new Beaver_Tunnels_Meta_Box_Builder( array(
			'id'       => 'beaver_tunnels',
			'title'    => Beaver_Tunnels_White_Label::get_branding(),
			'context'  => 'normal',
			'priority' => 'high',
			'screens'  => array(
				'fl-builder-template',
			),
			'fields' => array(
				array(
					'id' => $this->prefix . 'action_title',
					'label' => __( 'Location', $this->textdomain ),
					'desc' => __( 'Select where you would like to display this template.', $this->textdomain ),
					'type' => 'heading',
				),
				array(
					'id'                       => $this->prefix . 'action',
					'label'                    => __( 'Action', $this->textdomain ),
					'type'                     => 'select',
					'chosenjs'                 => true,
					'chosenjs_single_deselect' => true,
					'default'                  => '',
					'options'                  => $this->get_action_options(),
					'optgroup'                 => true,
				),
				array(
					'id'         => $this->prefix . 'priority',
					'label'       => __( 'Priority', $this->textdomain ),
					'desc'       => __( 'Default is 10. Lower numbers correspond with earlier execution.', $this->textdomain ),
					'type'       => 'text',
					'default'    => '10',
					'attributes' => array(
						'type' => 'number',
						'min'  => '1',
						'max'  => '999999',
					),
				),
				array(
					'id' => $this->prefix . 'page_rules_title',
					'label' => __( 'Display Rules', $this->textdomain ),
					'desc' => __( 'On which page/post should this template be displayed?', $this->textdomain ),
					'type' => 'heading',
				),
				array(
					'id'       => $this->prefix . 'all_pages',
					'label'    => __( 'All Pages and Posts', $this->textdomain ),
					'desc'     => __( 'Show on all pages/post. (This overrides any rules set below.)', $this->textdomain ),
					'type'     => 'checkbox',
				),
				array(
					'id'       => $this->prefix . 'conditions',
					'label'    => __( 'Match Conditions', $this->textdomain ),
					'type'     => 'select',
					'chosenjs' => true,
					'default'  => 'any',
					'options'  => array(
						'any' => __( 'Match ANY Rule', $this->textdomain ),
						'all' => __( 'Match ALL Rules', $this->textdomain ),
					),
				),
				array(
					'id'       => $this->prefix . 'posts',
					'label'    => __( 'Posts', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_post_options(),
				),
				array(
					'id'       => $this->prefix . 'pages',
					'label'    => __( 'Pages', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_page_options(),
				),
				array(
					'id'       => $this->prefix . 'templates',
					'label'    => __( 'Page Templates', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_page_template_options(),
				),
				array(
					'id'       => $this->prefix . 'archives',
					'label'    => __( 'Archives', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_post_type_options(),
				),
				array(
					'id'       => $this->prefix . 'singles',
					'label'    => __( 'Singular', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_post_type_options(),
				),
				array(
					'id'       => $this->prefix . 'taxonomies',
					'label'    => __( 'Taxonomy Archives', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_taxonomy_options(),
				),
				array(
					'id'       => $this->prefix . 'terms',
					'label'    => __( 'Term Singular', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_term_options(),
				),
				array(
					'id' => $this->prefix . 'user_rules_title',
					'label' => __( 'User Display Rules', $this->textdomain ),
					'desc' => __( 'Which users should see this template?', $this->textdomain ),
					'type' => 'heading',
				),
				array(
					'id'       => $this->prefix . 'user_status',
					'label'    => __( 'User Status', $this->textdomain ),
					'type'     => 'select',
					'chosenjs' => true,
					'default'  => 'all',
					'options'  => array(
						'all'        => __( 'All', $this->textdomain ),
						'logged_in'  => __( 'Logged In', $this->textdomain ),
						'logged_out' => __( 'Logged Out', $this->textdomain ),
					),
				),
				array(
					'id'       => $this->prefix . 'users',
					'label'    => __( 'Users', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_user_options(),
				),
				array(
					'id'       => $this->prefix . 'user_roles',
					'label'    => __( 'User Roles', $this->textdomain ),
					'type'     => 'select',
					'multiple' => true,
					'chosenjs' => true,
					'default'  => '',
					'options'  => $this->get_user_role_options(),
				),
			)
		) );

	}

	/**
	 * Retrive the array of action hooks that are available
	 *
	 * @since 1.0.0
	 *
	 * @return array Available action hooks
	 */
	private function get_action_options() {

		$actions = apply_filters('beaver_tunnels', array() );

		$actions_array = array();
		if ( ! empty( $actions ) ) {

			foreach( $actions as $platform ) {

				$platform_actions = array();
				if ( ! empty( $platform['actions'] ) && is_array( $platform['actions'] ) ) {

					foreach ( $platform['actions'] as $action ) {
						$platform_actions[ $action ] = $action;
					}

				}

				$actions_array[] = array(
					'title' => $platform['title'],
					'options' => $platform_actions,
				);

			}

		}

		return $actions_array;

	}

	private function get_post_options() {

		$posts = get_posts( array(
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
		) );

		$post_options = array();
		if ( ! empty( $posts ) ) {
			foreach( $posts as $post ) {
				$post_options[ $post->ID ] = $post->post_title;
			}
		}

		return $post_options;

	}

	/**
	 * Retrive an array of published pages
	 *
	 * @since 1.0.0
	 *
	 * @return array Pages
	 */
	private function get_page_options() {

		$pages = get_pages( array(
			'post_type' => 'page',
			'post_status' => 'publish',
		) );

		$page_options = array();
		if ( ! empty( $pages ) ) {
			foreach( $pages as $page ) {
				$page_options[ $page->ID ] = $page->post_title;
			}
		}

		return $page_options;

	}

	/**
	 * Retrive an array of registered page templates
	 *
	 * @since 1.0.0
	 *
	 * @return array Page templates
	 */
	private function get_page_template_options() {

		require_once ABSPATH . 'wp-admin/includes/admin.php';

		$templates = get_page_templates();

		$page_template_options = array();
		$page_template_options['default_template'] = __('Default Template', $this->textdomain );
		if ( ! empty( $templates ) ) {
			foreach ( $templates as $template_name => $template_filename ) {
			   $page_template_options[ $template_filename ] = $template_name . ' (' . $template_filename . ')';
			}
		}

		return $page_template_options;

	}

	/**
	 * Retrieve an array of post types
	 *
	 * @since 1.0.0
	 *
	 * @return array Post types
	 */
	private function get_post_type_options() {

		$builtin_post_types = get_post_types( array(
			'public' => true,
			'_builtin' => true,
		) );

		if ( ! is_array( $builtin_post_types ) ) {
			$builtin_post_types = array();
		}

		$post_types = get_post_types( array(
			'public' => true,
			'_builtin' => false,
		) );

		if ( ! is_array( $post_types ) ) {
			$post_types = array();
		}

		return array_merge( $builtin_post_types, $post_types );

	}

	/**
	 * Retrieve an array of public taxonomies
	 *
	 * @since 1.0.0
	 *
	 * @return array Taxonomies
	 */
	private function get_taxonomy_options() {

		$taxonomies = get_taxonomies( array(
			'public' => true,
		) );

		return $taxonomies;

		$taxonomy_options = array();

		return $taxonomy_options;

	}

	/**
	 * Retrieve an array of public taxonomy terms
	 *
	 * @since 1.0.0
	 *
	 * @return array Taxonomy terms
	 */
	private function get_term_options() {

		$taxonomies = get_taxonomies( array(
			'public' => true,
		) );

		$taxonomy_options = array();

		foreach( $taxonomies as $tax ) {
			//var_dump( get_terms( $tax, array( 'hide_empty' => false ) ) );
			$tax_terms = get_terms( $tax, array( 'hide_empty' => false ) );
			if ( is_array( $tax_terms ) ) {
				foreach( $tax_terms as $term ) {
					$taxonomy_options[ $tax . '|' . $term->slug ] = $term->name . ' (' . $term->taxonomy . ')';
				}
			}

		}

		return $taxonomy_options;

	}

	private function get_user_options() {

		$users = get_users( array(
			'orderby' => 'login',
			'order'   => 'ASC',
			'fields'  => array(
				'ID',
				'display_name',
				'user_nicename',
			),
		) );

		$users_options = array();

		foreach( $users as $user ) {
			$users_options[ $user->ID ] = $user->display_name . ' (' . $user->user_nicename . ')';
		}

		return $users_options;

	}

	private function get_user_role_options() {

		global $wp_roles;
	    $user_roles = $wp_roles->roles;

		$user_roles_options = array();

		foreach( $user_roles as $key => $value ) {
			$user_roles_options[ $key ] = $value['name'];
		}

	    return $user_roles_options;

	}

}
new Beaver_Tunnels_Meta_Box;
