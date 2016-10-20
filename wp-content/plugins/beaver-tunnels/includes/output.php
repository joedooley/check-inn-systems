<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Beaver_Tunnels_Output {

	public $post;

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( ! is_admin() ) {
			add_action( 'wp', array( $this, 'set_post' ) );
		}
		add_action( 'wp', array( $this, 'find_templates' ) );

	}

	public function set_post() {

		global $post;
		$this->post = $post;

	}

	/**
	 * Loop through the saved templates to find action hooks
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function find_templates() {

		if ( is_singular('fl-builder-template') || is_admin() || defined( 'DOING_AUTOSAVE' ) ) {
			return;
		}

		$templates = $this->get_tunneled_templates();

		if ( ! empty( $templates ) ) {

			foreach( $templates as $template ) {

				$rules = $this->get_rules( $template->ID );

				// Continue if the template shouldn't be displayed on this page
				if ( ! $this->show_on_page( $rules ) ) {
					continue;
				}

				$hook     = get_post_meta( $template->ID, '_beavertunnels_action', true );
				$priority = get_post_meta( $template->ID, '_beavertunnels_priority', true );
				$self     = $this;

				add_action( $hook, function() use( $self, $rules, $template ) {

					$content = $self->render_content( $template );

					if ( FLBuilderModel::is_builder_active() ) {
						$self->render_builder_active_content( $rules, $content );
					} else {
						echo $content;
					}
				}, $priority );

			} // foreach

		} // if

	}

	private function get_tunneled_templates() {

		// Check the cache for the query
		$templates = wp_cache_get( 'beaver_tunnels_templates' );

		// If the cache is empty, then run the query
		if ( false === $templates ) {

			$templates = get_posts( array(
				'posts_per_page' => -1,
				'post_type'      => 'fl-builder-template',
				'post_status'    => 'publish',
				'meta_query' => array(
					array(
						'key'     => '_beavertunnels_action',
						'value'   => '',
						'compare' => '!=',
					),
				),
			) );

			// Save the query to the cache
			wp_cache_set( 'beaver_tunnels_templates', $templates );

		}

		return $templates;

	}

	private function show_on_page( $rules ) {

		if (
			( '1' === $rules['all_pages']
			|| ( 'any' === $rules['conditions'] && $this->match_any_rules( $rules ) )
			|| ( 'all' === $rules['conditions'] && $this->match_all_rules( $rules ) )
			) && $this->match_user_rules( $rules )
		) {
			return true;
		}

		return false;

	}

	public function render_content( $template ) {

		$method = 'render_query';

		switch( $method ) {
			case 'render_query':
				ob_start();
				FLBuilder::render_query( array(
					'post_type' => 'fl-builder-template',
					'p'         => $template->ID
				) );
				$content = ob_get_clean();
				break;

			case 'shortcode':
				$content = do_shortcode('[fl_builder_insert_layout id="' . $template->ID . '"]');
				break;

		}

		return $content;

	}

	public function render_builder_active_content( $rules, $content ) {

		wp_enqueue_style( 'beaver-tunnels-fl-builder', BEAVER_TUNNELS_PLUGIN_URL . 'assets/css/fl-builder.css', array(), '1.0.0' );
		wp_enqueue_script( 'beaver-tunnels-fl-builder', BEAVER_TUNNELS_PLUGIN_URL . 'assets/js/fl-builder.js', array('jquery'), '1.0.0', false );
		?>
		<div class="beaver-tunnels-content">
			<a href="<?php echo get_permalink( $rules['template_id'] ); ?>"></a>
			<?php echo $content; ?>
			<div class="bt-template-overlay">
				<div class="fl-block-overlay-header">
					<div class="bt-template-overlay-actions">
						<div class="bt-template-overlay-title"><?php echo get_the_title( $rules['template_id'] ); ?></div>
						<i class="fl-block-settings fa fa-sign-out"></i>
					</div>
					<div class="fl-clear"></div>
				</div>
			</div>
		</div>
		<?php

	}

	/**
	 * Build the rules array
	 *
	 * @since since 0.9.2
	 *
	 * @param  string $template_id The id of the Beaver Builder Template
	 *
	 * @return array        The array of display rules
	 */
	private function get_rules( $template_id ) {

		$rules_conditions = get_post_meta( $template_id, '_beavertunnels_conditions', true );
		if ( 0 == strlen( $rules_conditions ) ) {
			$rules_conditions = 'any';
		}

		$rules = array(
			'template_id'   => $template_id,
			'all_pages'     => get_post_meta( $template_id, '_beavertunnels_all_pages', true ),
			'conditions'    => $rules_conditions,
			'posts'         => $this->check_array( get_post_meta( $template_id, '_beavertunnels_posts', true ) ),
			'pages'         => $this->check_array( get_post_meta( $template_id, '_beavertunnels_pages', true ) ),
			'templates'     => $this->check_array( get_post_meta( $template_id, '_beavertunnels_templates', true ) ),
			'archives'      => $this->check_array( get_post_meta( $template_id, '_beavertunnels_archives', true ) ),
			'singles'       => $this->check_array( get_post_meta( $template_id, '_beavertunnels_singles', true ) ),
			'taxonomies'    => $this->check_array( get_post_meta( $template_id, '_beavertunnels_taxonomies', true ) ),
			'term_archives' => $this->check_array( get_post_meta( $template_id, '_beavertunnels_term_archives', true ) ),
			'terms'         => $this->check_array( get_post_meta( $template_id, '_beavertunnels_terms', true ) ),
			'user_status'   => get_post_meta( $template_id, '_beavertunnels_user_status', true ),
			'logged_in'     => get_post_meta( $template_id, '_beavertunnels_logged_in', true ),
			'users'         => $this->check_array( get_post_meta( $template_id, '_beavertunnels_users', true ) ),
			'user_roles'    => $this->check_array( get_post_meta( $template_id, '_beavertunnels_user_roles', true ) ),
		);

		return $rules;

	}

	/**
	 * Check if any of the user rules match
	 *
	 * @since since 0.9.2
	 *
	 * @param  array $rules Array of rules
	 *
	 * @return boolean        True / false
	 */
	private function match_user_rules( $rules ) {

		if ( '' === $rules['user_status'] ) {
			$rules['user_status'] = 'all';
		}

		if ( 'all' === $rules['user_status'] && empty( $rules['users'] ) && empty( $rules['user_roles'] ) ) {
			return true;
		}

		if ( 'logged_out' === $rules['user_status'] && ! is_user_logged_in() ) {
			return true;
		}

		if ( 'logged_in' === $rules['user_status'] && is_user_logged_in() && empty( $rules['users'] ) && empty( $rules['user_roles'] ) ) {
			return true;
		}

		if ( 'logged_in' === $rules['user_status'] && ! is_user_logged_in() ) {
			return false;
		}

		if ( in_array( get_current_user_id(), $rules['users'] ) ) {
			return true;
		}

		$user = wp_get_current_user();
		if ( '0' < count( array_intersect( $rules['user_roles'], (array) $user->roles ) ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if any of the rules match
	 *
	 * @since since 1.0.0
	 *
	 * @param  array $rules Array of rules
	 *
	 * @return boolean        True / false
	 */
	private function match_any_rules( $rules ) {

		if ( $this->is_on_page( $rules )
			|| $this->is_on_post( $rules )
			|| $this->is_on_page_template( $rules )
			|| $this->is_on_archive( $rules )
			|| $this->is_on_singles( $rules )
			|| $this->is_on_tax( $rules )
			|| $this->is_on_tax_archive( $rules )
			|| $this->is_on_term( $rules )
		) {
			return true;
		}

		return false;

	}

	/**
	 * Check if all of the rules match
	 *
	 * @since 1.0.0
	 *
	 * @param  array $rules Array of rules
	 *
	 * @return boolean        True / false
	 */
	private function match_all_rules( $rules ) {

		if ( ! empty( $rules['pages'] ) ) {
			if ( ! $this->is_on_page( $rules ) ) {
				return false;
			}
		}

		if ( ! empty( $rules['posts'] ) ) {
			if ( ! $this->is_on_post( $rules ) ) {
				return false;
			}
		}

		if ( ! empty( $rules['templates'] ) ) {
			if ( ! $this->is_on_page_template( $rules ) ) {
				return false;
			}
		}

		if ( ! empty( $rules['archives'] ) ) {
			if ( ! $this->is_on_archive( $rules ) ) {
				return false;
			}
		}

		if ( ! empty( $rules['singles'] ) ) {
			if ( ! $this->is_on_singles( $rules ) ) {
				return false;
			}
		}

		if ( ! empty( $rules['taxonomies'] ) ) {
			if ( ! $this->is_on_tax( $rules ) ) {
				return false;
			}
		}

		if ( ! empty( $rules['term_archives'] ) ) {
			if ( ! $this->is_on_tax_archive( $rules ) ) {
				return false;
			}
		}

		if ( ! empty( $rules['terms'] ) ) {
			if ( ! $this->is_on_term( $rules ) ) {
				return false;
			}
		}

		return true;

	}

	/**
	 * Check if we are on a specific post
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $rules Array of rules
	 *
	 * @return boolean        True / false
	 */
	private function is_on_post( $rules ) {

		if ( empty( $rules['posts'] ) ) {
			return false;
		}

		$result = false;

		switch ( $rules['conditions'] ) {

			case 'all':
				$result = true;
				foreach ( $rules['posts'] as $single_post ) {
					if ( ! is_single( $single_post ) ) {
						$result = false;
					}
				}
				break;

			case 'any':
				if ( is_single( $rules['posts'] ) ) {
					$result = true;
				}
				break;

		}

		return $result;

	}

	/**
	 * Check if we are on a specific page
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $rules Array of rules
	 *
	 * @return boolean        True / false
	 */
	private function is_on_page( $rules ) {

		if ( empty( $rules['pages'] ) ) {
			return false;
		}

		$result = false;

		switch ( $rules['conditions'] ) {

			case 'all':
				$result = true;
				foreach( $rules['pages'] as $page ) {
					if ( ! is_page( $page ) ) {
						$result = false;
					}
				}
				break;

			case 'any':
				if ( is_page( $rules['pages'] ) ) {
					$result = true;
				}
				break;

		}

		return $result;

	}

	/**
	 * Check if we are on a specific page template
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $rules Array of rules
	 *
	 * @return boolean            True / false
	 */
	private function is_on_page_template( $rules ) {

		if ( empty( $rules['templates'] ) ) {
			return false;
		}

		if ( is_page_template( $rules['templates'] ) ) {
			return true;
		}

		if ( in_array( 'default_template', $rules['templates'] ) && is_page() && ! is_page_template() ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if we are on a specific archive page
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $rules Array of rules
	 *
	 * @return boolean           True / false
	 */
	private function is_on_archive( $rules ) {

		if ( empty( $rules['archives'] ) ) {
			return false;
		}

		if ( is_post_type_archive( $rules['archives'] ) ) {
			return true;
		}

		if ( in_array( 'post', $rules['archives'] ) && is_home() ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if we are on a single post type page
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $rules Array of rules
	 *
	 * @return boolean          True / false
	 */
	private function is_on_singles( $rules ) {

		if ( empty( $rules['singles'] ) ) {
			return false;
		}

		if ( is_singular( $rules['singles'] ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if we are on a specific taxonomy page
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $rules Array of rules
	 *
	 * @return boolean             True / false
	 */
	private function is_on_tax( $rules ) {

		if ( empty( $rules['taxonomies'] ) ) {
			return false;
		}

		if ( is_tax( $rules['taxonomies'] ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if we are on a specific taxonomy archive
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $rules Array of rules
	 *
	 * @return boolean             True / false
	 */
	private function is_on_tax_archive( $rules ) {

		if ( empty( $rules['taxonomies'] ) ) {
			return false;
		}

		foreach( $rules['taxonomies'] as $tax ) {
			$tax_array = explode( '|', $tax );
			if ( is_tax( $tax_array[0], $tax_array[1] ) || ( ( 'category' == $tax_array[0] ) && is_category( $tax_array[1] ) ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Check if we are on a specific taxonomy post
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $rules Array of rules
	 *
	 * @return boolean        True / false
	 */
	private function is_on_term( $rules ) {

		if ( empty( $rules['terms'] ) ) {
			return false;
		}

		// Set the $post object
		global $post;
		$post_backup = $post;
		$post        = $this->post;

		if ( ! is_single() ) {
			$post = $post_backup;
			return false;
		}

		$result = false;

		switch ( $rules['conditions'] ) {

			case 'all':
				foreach( $rules['terms'] as $term ) {
					$result = true;
					$term_array = explode( '|', $term );
					if ( ! has_term( $term_array[1], $term_array[0] ) && ! ( ( 'category' == $term_array[0] ) && in_category( $term_array[1] ) ) ) {
						$result = false;
					}
				}
				break;

			case 'any':
				foreach( $rules['terms'] as $term ) {
					$term_array = explode( '|', $term );
					if ( has_term( $term_array[1], $term_array[0] ) && ( ( 'category' == $term_array[0] ) && in_category( $term_array[1] ) ) ) {
						$result = false;
					}
				}
				break;

		}

		// Reset the $post object
		$post = $post_backup;

		return $result;

	}

	/**
	 * Fixes input that should be an array
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $input Field values
	 *
	 * @return array        Array of field values
	 */
	private function check_array( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		return $input;
	}

}
new Beaver_Tunnels_Output();
