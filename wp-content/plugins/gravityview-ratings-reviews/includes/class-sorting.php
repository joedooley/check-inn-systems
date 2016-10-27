<?php
/**
 * Handle sorting by Ratings Reviews fields
 *
 * @package   GravityView_Ratings_Reviews
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

class GravityView_Ratings_Reviews_Sorting extends GravityView_Ratings_Reviews_Component {

	public function load() {

		add_filter( 'gravityview/common/sortable_fields', array( $this, 'add_sort_field' ), 10, 2 );

		add_filter( 'gravityview_search_criteria', array( $this, 'search_criteria' ), 10, 3 );

	}

	/**
	 * Add the possibility to configure the View to sort by the average rating
	 * This will add the reviews_link field to the Filter & Sort metabox sort field dropdown.
	 *
	 * Requires GravityView 1.11.3+
	 *
	 * @param array $fields Sub-set of GF form sortable fields
	 * @param int $form_id  GF Form ID
	 *
	 * @return array
	 */
	function add_sort_field( $fields, $form_id ) {

		$fields['reviews_link'] = array(
			'type' => 'reviews_link',
			'label' => __( 'Reviews Link', 'gravityview-ratings-reviews' ),
		);

		return $fields;

	}

	/**
	 * @param $criteria
	 * @param $form_ids
	 * @param $context_view_id
	 *
	 * @return mixed
	 */
	function search_criteria( $criteria, $form_ids, $context_view_id ) {

		if( defined('DOING_AJAX') && DOING_AJAX ) {
			// DataTables sorting
			$sort_field = GravityView_View::getInstance()->getAtts('sort_field');
			$sort_direction = GravityView_View::getInstance()->getAtts('sort_direction');
		} else {
			// Standard sorting
			$sort_field = rgars( $criteria, 'sorting/key' );
			$sort_direction = rgars( $criteria, 'sorting/direction' );
		}

		if ( empty( $sort_field ) || !in_array( $sort_field, array( 'stars', 'votes', 'reviews_link' ) ) ) {
			return $criteria;
		}

		if ( 'reviews_link' === $sort_field ) {

			$ratings_type = GravityView_View::getInstance()->getAtts( 'entry_review_type' );

			if( empty( $ratings_type ) )  {
				do_action( 'gravityview_log_error', 'GravityView_Ratings_Reviews_Sorting[search_criteria] Empty ratings type view setting.' );
				return $criteria;
			}

			$sort_field = $ratings_type;
		}

		$criteria['sorting']['key'] = ( 'stars' === $sort_field ) ? 'gravityview_ratings_stars' : 'gravityview_ratings_votes';
		$criteria['sorting']['is_numeric'] = true;
		$criteria['sorting']['direction'] = $sort_direction;

		return $criteria;
	}
}