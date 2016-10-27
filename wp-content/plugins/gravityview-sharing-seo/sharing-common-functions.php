<?php

if( ! function_exists('gravityview_social_get_title') ) {

	/**
	 * Get the title to be used for the social sharing. Can be overridden.
	 *
	 * @uses GravityView_frontend::getEntry()
	 * @uses GravityView_API::replace_variables()
	 * @uses GravityView_frontend::get_context_view_id()
	 *
	 * @param $previous_title
	 * @param int $post_id
	 *
	 * @return string
	 */
	function gravityview_social_get_title( $previous_title, $post_id = 0 ) {

		$return = $previous_title;

		$view_id = GravityView_frontend::getInstance()->get_context_view_id();

		$data = gravityview_get_current_view_data( $view_id );

		if ( ! empty( $data ) ) {

			$single_entry_title_setting = trim( rtrim( $data['atts']['single_title'] ) );

			if ( ! empty( $single_entry_title_setting ) ) {

				$entry = GravityView_frontend::getInstance()->getEntry();

				$return = GravityView_API::replace_variables( $single_entry_title_setting, $data['form'], $entry );
			}
		}

		return $return;
	}
}

if( ! function_exists('gravityview_social_get_permalink') ) {

	/**
	 * Filter the permalink for a post with a custom post type.
	 *
	 * @param string $post_link The post's permalink.
	 * @param WP_Post|null $passed_post The post in question.
	 * @param bool $leavename Whether to keep the post name.
	 * @param bool $sample Is it a sample permalink.
	 */
	function gravityview_social_get_permalink( $post_link, $passed_post = NULL, $leavename = false, $sample = false ) {
		global $post;

		if ( is_admin() || ! class_exists( 'GravityView_API' ) ) {
			return $post_link;
		}

		if ( $passed_post instanceof WP_Post ) {
			$post_id = $passed_post->ID;
		} elseif ( $passed_post ) {
			$post_id = $passed_post;
		} elseif ( $post instanceof WP_Post ) {
			$post_id = $post->ID;
		} else {
			return $post_link;
		}

		if ( $single_entry = GravityView_frontend::is_single_entry() ) {
			$entry_id = $single_entry;
		} else {
			$entry_id = GravityView_View::getInstance()->getCurrentEntry();
		}

		if ( ! empty( $entry_id ) ) {
			return GravityView_API::entry_link( $entry_id, $post_id, false );
		}

		return $post_link;
	}

}