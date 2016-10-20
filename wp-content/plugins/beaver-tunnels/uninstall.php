<?php
/**
 * Uninstall Beaver Tunnels
 *
 * @package		Beaver Tunnels
 * @subpackage	Uninstall
 * @copyright	Copyright (c) 2016, FireTree Design, LLC
 * @license		http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		0.9.2
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

class Beaver_Tunnels_Uninstall {

	private $license_url    = 'https://beavertunnels.com/';
	private $item_id        = 'beaver-tunnels';
	private $item_name      = 'Beaver Tunnels';
	private $version        = '1.2.0';
	private $settings       = '';
	private $site_settings  = false;
	private $network_active = false;

	public function __construct() {

		if ( is_multisite() ) {
			$this->site_settings = get_site_option('beaver_tunnels', false );
		}

		if ( false !== $this->site_settings ) {
			$this->settings = $this->site_settings;
			$this->network_active = true;
		} else {
			$this->settings = get_option('beaver_tunnels', array() );
		}

		if ( isset( $this->settings['remove_data'] ) ) {

			$this->deactivate_license();

			if ( $this->network_active ) {

				$blog_list = get_blog_list( 0, 'all' );
				foreach ($blog_list AS $blog) {

					switch_to_blog( $blog['blog_id'] );
					$this->delete_post_meta();
					$this->delete_options();
					restore_current_blog();

				}

				$this->delete_site_options();

			} else {
				$this->delete_post_meta();
				$this->delete_options();
			}

		}

	}

	private function deactivate_license() {

		if ( isset( $this->settings['license_key'] ) ) {

			$body = array(
				'edd_action' => 'deactivate_license',
				'license'    => $this->settings['license_key'],
				'item_name'  => urlencode( $this->item_name ),
				'url'        => home_url(),
			);

			wp_remote_post(
				$this->license_url,
				array(
					'timeout'	=> 15,
					'sslverify'	=> false,
					'body'      => $body,
				)
			);

		}

	}

	private function delete_post_meta() {

		// Grab all of the templates
		$templates = get_posts( array(
			'posts_per_page' => -1,
			'post_type'      => 'fl-builder-template'
		) );

		if ( ! empty( $templates ) ) {

			// Loop through the templates and remove the post meta
			foreach( $templates as $template ) {

				delete_post_meta( $template->ID, '_beavertunnels_action' );
				delete_post_meta( $template->ID, '_beavertunnels_priority' );
				delete_post_meta( $template->ID, '_beavertunnels_all_pages' );
				delete_post_meta( $template->ID, '_beavertunnels_conditions' );
				delete_post_meta( $template->ID, '_beavertunnels_posts' );
				delete_post_meta( $template->ID, '_beavertunnels_pages' );
				delete_post_meta( $template->ID, '_beavertunnels_templates' );
				delete_post_meta( $template->ID, '_beavertunnels_archives' );
				delete_post_meta( $template->ID, '_beavertunnels_singles' );
				delete_post_meta( $template->ID, '_beavertunnels_taxonomies' );
				delete_post_meta( $template->ID, '_beavertunnels_term_archives' );
				delete_post_meta( $template->ID, '_beavertunnels_terms' );

			}

		}

	}

	private function delete_options() {

		delete_option('beaver_tunnels');
		delete_option('beaver_tunnels_license_data');

	}

	private function delete_site_options() {

		delete_site_option('beaver_tunnels');
		delete_site_option('beaver_tunnels_license_data');

	}

}

new Beaver_Tunnels_Uninstall();
