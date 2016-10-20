<?php
/**
 * Admin Bar class
 *
 * @package Beaver_Tunnels
 * @since 0.9.5
 */
class Beaver_Tunnels_Admin_Bar {

    public function __construct() {
        $this->actions();
    }

    public function actions() {
        add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 1000 );
    }

    public function admin_bar_menu( $wp_admin_bar ) {

		$fl_builder_branding = FLBuilderModel::get_branding();
		$fl_builder_default  = __( 'Page Builder', 'fl-builder' );
		$branding = __('Beaver Tunnels', 'beaver-tunnels');

		if ( $fl_builder_branding != $fl_builder_default ) {
			$branding = $fl_builder_branding;
		}

		// Make sure that we're not in the admin and that the user can edit pages
		if ( is_admin() || ! current_user_can( 'edit_pages' ) ) {
            return;
        }

		// Check if the menu should be hidden
		$beaver_tunnels = get_option('beaver_tunnels', array() );
		if ( isset( $beaver_tunnels['hide_hook_guide'] ) ) {
			return;
		}

		$actions = apply_filters('beaver_tunnels', array() );

		if ( ! empty( $actions ) ) {

			global $wp_the_query;

            if ( ! class_exists( 'FLBuilderModel' ) ) {
                return;
            }

			$node_location = 'standalone';

			if ( FLBuilderModel::is_post_editable()
				&& $wp_admin_bar->get_node('fl-builder-frontend-edit-link')
				&& ! has_action('wp_before_admin_bar_render', 'apspider_admin_bar_removal')
			) {
				$node_location = 'page-builder';
			}

			switch( $node_location ) {

				case 'page-builder':

					$wp_admin_bar->add_node(
						array(
							'id'     => 'beaver-tunnels-header',
							'title'  => sprintf('<span class="dashicons dashicons-admin-settings" style="font: 400 18px/1 dashicons !important; vertical-align: text-top; -webkit-font-smoothing: antialiased !important;"></span> %1s %2s', $branding, __( 'Hook Guide', 'beaver-tunnels' ) ),
							'parent' => 'fl-builder-frontend-edit-link',
					) );

					$wp_admin_bar->add_group(
						array(
							'id' => 'beaver-tunnels-actions',
							'parent' => 'fl-builder-frontend-edit-link',
							'meta' => array(
								'class' => 'ab-sub-secondary',
							),
						)
					);
					break;

				default:

					$wp_admin_bar->add_node(
						array(
							'id'     => 'beaver-tunnels-header',
							'title'  => Beaver_Tunnels_White_Label::get_branding(),
					) );

					$wp_admin_bar->add_node(
						array(
							'id'     => 'beaver-tunnels-header-text',
							'title'  => '<span class="dashicons dashicons-admin-settings" style="font: 400 18px/1 dashicons !important; vertical-align: text-top; -webkit-font-smoothing: antialiased !important;"></span> ' . __( 'Hook Guide', 'beaver-tunnels' ),
							'parent' => 'beaver-tunnels-header',
					) );

					$wp_admin_bar->add_group(
						array(
							'id' => 'beaver-tunnels-actions',
							'parent' => 'beaver-tunnels-header',
							'meta' => array(
								'class' => 'ab-sub-secondary',
							),
						)
					);
					break;

			}


			global $wp, $wp_rewrite;

			$query_args = $wp_rewrite->using_permalinks() ? array() : $wp->query_string;
			$current_url = esc_url_raw( home_url( add_query_arg( $query_args, $wp->request) ) );

			foreach( $actions as $platform ) {

				$safe_name = preg_replace( '/\W+/', '', strtolower( strip_tags( $platform['title'] ) ) );

				if ( isset( $_GET[ 'bt-' . $safe_name ] ) ) {
					$new_current_url = esc_url_raw( remove_query_arg( 'bt-' . $safe_name, $current_url ) );
				} else {
					$new_current_url = esc_url_raw( add_query_arg( 'bt-' . $safe_name, 'show', $current_url ) );
				}

				// $platform['title'];
				$wp_admin_bar->add_node(
					array(
						'id' => 'beaver-tunnels-action-' . $safe_name,
						'title' => $platform['title'],
						'parent' => 'beaver-tunnels-actions',
						'href' => $new_current_url,
					)
				);

			}

		}

    }

}
new Beaver_Tunnels_Admin_Bar();
