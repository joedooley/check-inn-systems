<?php

/**
 * Enable using Gravity Forms merge tags in the `[gv_math]` shortcode.
 *
 * Replaces the values of the merge tags with values before the numbers are run through the calculator.
 *
 * Example:
 *
 * Entry #123 has a field named "Number" with the ID "5" and a value of 4:
 * - Before: [gv_math scope="entry" id="123"] {Number:5} + 2 [/gv_math]
 * - After: [gv_math scope="entry" id="123"] 4 + 2 [/gv_math]
 *
 */
class GravityView_Math_GravityForms {

	/**
	 * Regex to match any Gravity Forms Merge Tags
	 * @since 1.0
	 */
	const MERGE_TAG_REGEX = '/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi';

	/**
	 * @since 1.0
	 * @var array {
	 * @type string $scope If not defined, return original formula for basic math. If defined, choices are `form`, `view`, `visible`, `entry`
	 * @type int $id Number of decimals to use. Default: '' (empty string). If not set, shows calculated # of decimals
	 * }
	 */
	private static $default_atts = array(
		'scope'         => '', // form, view, visible, entry. If not set, do basic math.
		'id'            => '',    // Form ID if scope is form, View ID if view, Entry ID if entry
		'default_value' => '',    // Form ID if scope is form, View ID if view, Entry ID if entry
	    'debug'         => '',
	);
	/**
	 * @since 1.0
	 * @var array
	 */
	private static $scopes = array(
		'form',
		'view',
		'visible',
		'entry',
	);
	/**
	 * @var GravityView_Math_Report
	 */
	public $reporter;

	function __construct() {

		$this->reporter = GravityView_Math_Report::get_instance();

		add_filter( 'gravityview/math/shortcode/before', array( $this, 'shortcode' ), 10, 4 );
	}

	/**
	 * @since 1.0
	 *
	 * @param string $formula
	 * @param array $atts
	 * @param string $content
	 * @param string $shortcode
	 *
	 * @return mixed|string
	 */
	public function shortcode( $formula = '', $atts = array(), $content = '', $shortcode = 'gv_math' ) {

		$atts = shortcode_atts( self::$default_atts, $atts, $shortcode );

		// If the scope isn't defined or supported by this class, just return the original formula.
		if ( empty( $atts['scope'] ) || ! in_array( $atts['scope'], self::$scopes ) ) {

			return $formula;

		} elseif ( 'visible' !== $atts['scope'] && 'entry' !== $atts['scope'] && empty( $atts['id'] ) ) {

			$formula = '';

			$data = array(
				'atts' => $atts,
				'code' => 'ID_not_set'
			);

			do_action( 'gravityview_math_log_error', esc_html__( 'No ID provided', 'gravityview-math' ), $data );

			unset( $data );

			return $formula;
		}

		switch ( $atts['scope'] ) {
			case 'entry':
				$formula = $this->get_formula_for_entry( $formula, $atts );
				break;
			default:
				$formula = $this->get_formula_bulk( $formula, $atts );
		}

		// Remove all merge tags that remain.
		// They cause errors for the math engine if left in.
		$formula = preg_replace( self::MERGE_TAG_REGEX, '', $formula );

		return $formula;
	}

	/**
	 * @since 1.0
	 *
	 * @param $formula
	 * @param $atts
	 *
	 * @return string
	 */
	function get_formula_for_entry( $formula, $atts ) {

		$entry = $this->get_entry( $atts );

		if ( $entry && $form = GFAPI::get_form( $entry['form_id'] ) ) {
			$formula = $this->replace_merge_tags( $formula, $form, $entry, $atts );
		}

		return $formula;
	}

	/**
	 * Fetch an entry array from attribute IDs, if set, otherwise, the current entry in GravityView
	 *
	 * @param array $atts
	 *
	 * @return array|mixed
	 */
	function get_entry( $atts ) {

		$entry = array();

		if ( ! empty( $atts['id'] ) ) {
			$entry = GFAPI::get_entry( $atts['id'] );
		} else if ( class_exists( 'GravityView_View' ) ) {
			$entry = GravityView_View::getInstance()->getCurrentEntry();
		}

		return $entry;
	}

	/**
	 * Replace merge tags
	 *
	 * @since 1.0
	 *
	 * @param $formula
	 * @param $form
	 * @param $lead
	 * @param array $atts
	 *
	 * @return mixed
	 */
	private function replace_merge_tags( $formula, $form, $lead, $atts = array() ) {

		$matches = $this->match_merge_tags( $formula );

		foreach ( $matches as $match ) {

			$value = '';
			$match = array_pad( $match, 5, null );

			list( $merge_tag, $input_id, $_empty, $modifier_tag_with_sep, $modifier_tag ) = $match;

			$field = GFFormsModel::get_field( $form, floor( $input_id ) );

			$debug_data = array(
				'input_id' => $input_id,
				'lead'     => $lead,
				'atts'     => $atts,
				'field'    => $field,
			);

			if ( ! GFCommon::is_valid_for_calcuation( $field ) ) {

				$debug_data['code'] = 'invalid_field';

				do_action( 'gravityview_math_log_error', esc_html__( 'Field not valid for calculation', 'gravityview-math' ), $debug_data );

				unset( $debug_data );

				$formula = '';

				continue;
			}

			/**
			 * If the field value is empty check for a default value attribute.
			 * If no default value is set then let Gravity Forms Handle it.
			 * If the default value is 'skip' then move on to the next field.
			 */
			if ( ! isset( $lead[ $input_id ] ) || '' === $lead[ $input_id ] ) {

				$debug_data['code'] = 'empty_entry';

				do_action( 'gravityview_math_log_debug', esc_html__( 'A field in the calculation did not contain a value', 'gravityview-math' ), $debug_data );

				if ( 'skip' === $atts['default_value'] ) {
					$formula = '';
					continue;
				} elseif ( '' !== $atts['default_value'] ) {
					$value = floatval( $atts['default_value'] );
				}

			} else {
				$value = GFCommon::get_calculation_value( $input_id, $form, $lead );
			}

			$value = apply_filters( 'gform_merge_tag_value_pre_calculation', $value, $input_id, rgar( $match, 4 ), $field, $form, $lead );

			$formula = str_replace( $merge_tag, $value, $formula );
		}

		unset( $debug_data );

		return $formula;
	}

	/**
	 *
	 * @since 1.0
	 *
	 * @param $content
	 *
	 * @return array
	 */
	private function match_merge_tags( $content ) {

		preg_match_all( self::MERGE_TAG_REGEX, $content, $matches, PREG_SET_ORDER );

		return (array) $matches;
	}

	/**
	 * @since 1.0
	 *
	 * @param $formula
	 * @param $atts
	 *
	 * @return mixed
	 */
	function get_formula_bulk( $formula, $atts ) {

		$matches = $this->match_merge_tags( $formula );

		foreach ( $matches as $match ) {

			$match = array_pad( $match, 5, null );

			/**
			 * @var string $merge_tag Full matched merge tag (Example: `{Number:5:avg}`)
			 * @var string $input_id The ID of the input (Example: `5`)
			 * @var string $_empty This is empty. Do not use.
			 * @var string $modifier_tag_with_sep Number modifier with separator (Example: `:avg`)
			 * @var string $modifier_tag Modifier that says what calculation you want to perform on the number (Example: `avg`)
			 */
			list( $merge_tag, $input_id, $_empty, $modifier_tag_with_sep, $modifier_tag ) = $match;

			$method_name = "get_aggregate_data_{$atts['scope']}";

			/**
			 * Get an array of values based on the scope.
			 *
			 * @var array|false $result If invalid, is false. Otherwise, array with max, min, avg, count, sum values for the field ID based on the scope.
			 */
			$result = $this->$method_name( $atts, $input_id );

			if ( $result ) {
				if ( 'skip' == $atts['default_value'] || 'form' !== $atts['scope'] ) {
					switch ( $modifier_tag ) {
						case 'max':
						case 'min':
						case 'avg':
						case 'count':
							$value = $result[ $modifier_tag ];
							break;
						case 'sum':
						default:
							$value = $result['sum'];
					}
				} else {
					switch ( $modifier_tag ) {
						case 'max':
							$value = max( $result['max'], $atts['default_value'] );
							break;
						case 'min':
							$value = min( $result['max'], $atts['default_value'] );
							break;
						case 'avg':
							$value = $result['count'] > 0 ? ( $result['sum'] + ( count( explode( ',', $result['all_ids'] ) ) - count( explode( ',', $result['filtered_ids'] ) ) ) * $atts['default_value'] ) / $result['count'] : 0; // Prevent division by 0
							break;
						case 'count':
							$value = $result[ $modifier_tag ];
							break;
						case 'sum':
						default:
							$value = $result['sum'] + ( count( explode( ',', $result['all_ids'] ) ) - count( explode( ',', $result['filtered_ids'] ) ) ) * $atts['default_value'];
					}
				}
				if ( isset( $result['debug'] ) && ! empty( $result['debug'] ) ) {

					$data = array(
						'input_id' => $input_id,
						'lead'     => $result['debug'],
						'atts'     => $atts,
						'code'     => 'empty_form_field'
					);

					do_action( 'gravityview_math_log_debug', esc_html__( 'A field in the calculation did not contain a value', 'gravityview-math' ), $data );

					unset( $data );

				}

				$formula = str_replace( $merge_tag, $value, $formula );
			}
		}

		return $formula;
	}

	/**
	 * Get the data array for only visible entries in GravityView
	 *
	 * @param array $atts
	 * @param int $field_id
	 *
	 * @return array|bool
	 */
	function get_aggregate_data_visible( $atts = array(), $field_id = 0 ) {

		$entries = GravityView_View::getInstance()->getEntries();

		if ( empty( $entries ) ) {
			$view_id = GravityView_View::getInstance()->getViewId();

			if ( empty( $view_id ) ) {

				$data = array(
					'atts' => $atts,
					'code' => 'no_view_found'
				);

				do_action( 'gravityview_math_log_debug', esc_html__( 'No View was found', 'gravityview-math' ), $data );

				unset( $data );

			} else {

				$data = array(
					'atts'      => $atts,
					'code'      => 'no_entries_found',
					'view_link' => admin_url( sprintf( 'post.php?post=%d&action=edit', $view_id ) ),
				);

				do_action( 'gravityview_math_log_debug', esc_html__( 'The following View does not currently contain any entries', 'gravityview-math' ), $data );

				unset( $data );
			}

			return false;
		}

		$all_values = $non_empty_values = array();

		foreach ( $entries as $entry ) {
			$all_values[] = ! isset( $entry[ $field_id ] ) ? $entry[ $field_id ] = '' : $entry[ $field_id ];

			if ( '' !== $entry[ $field_id ] ) {
				$non_empty_values[] = $entry[ $field_id ];
			} else {

				$data = array(
					'input_id' => $field_id,
					'lead'     => $entry,
					'atts'     => $atts,
					'code'     => 'empty_visible_field'
				);

				do_action( 'gravityview_math_log_debug', esc_html__( 'A field in the calculation did not contain a value', 'gravityview-math' ), $data );

				unset( $data );

				if ( is_numeric( $atts['default_value'] ) ) {

					$all_values   = array_slice( $all_values, - 1 );
					$all_values[] = $non_empty_values[] = floatval( $atts['default_value'] );
				}
			}
		}

		$return = array(
			'sum'   => array_sum( $all_values ),
			'max'   => max( $non_empty_values ),
			'min'   => min( $non_empty_values ),
			'avg'   => ( sizeof( $non_empty_values ) > 0 ? array_sum( $all_values ) / sizeof( $non_empty_values ) : 0 ),
			// Prevent division by 0
			'count' => sizeof( $non_empty_values ),
		);

		unset( $all_values, $non_empty_values );

		return $return;
	}

	/**
	 * Get aggregate data for a form from a basic SQL query
	 *
	 * @since 1.0
	 *
	 * @param array $atts
	 * @param int|double $field_id
	 *
	 * @return bool|mixed
	 */
	function get_aggregate_data_form( $atts = array(), $field_id = 0 ) {
		global $wpdb;

		$form_id = (int) $atts['id'];

		$value = $this->get_cache( $form_id, $field_id );

		if ( ! $value ) {

			/** @define "$lead_detail_table" "wp_rg_lead_detail" */
			$lead_detail_table_name = RGFormsModel::get_lead_details_table_name();
			/** @define "$lead_table" "wp_rg_lead" */
			$lead_table = RGFormsModel::get_lead_table_name();

			$field_number_min = (double) $field_id - 0.0001;
			$field_number_max = (double) $field_id + 0.0001;

			$sql     = <<<SQL
			SELECT
			  GROUP_CONCAT(DISTINCT details.`lead_id`
						   ORDER BY details.`lead_id` ASC
						   SEPARATOR ',') AS all_ids,
			  GROUP_CONCAT( DISTINCT
							CASE
							WHEN details.`field_number` BETWEEN %f AND %f THEN details.`lead_id`
							ELSE NULL
							END
							ORDER BY details.`lead_id` ASC
							SEPARATOR ',') AS filtered_ids,
			  SUM( CASE
				   WHEN details.`field_number` BETWEEN %f AND %f THEN CAST( details.`value` AS DECIMAL( 65, 30 ) )
				   ELSE 0
				   END ) AS sum,
			  AVG( CASE
				   WHEN details.`field_number` BETWEEN %f AND %f THEN CAST( details.`value` AS DECIMAL( 65, 30 ) )
				   ELSE NULL
				   END ) AS avg,
			  MAX( CASE
				   WHEN details.`field_number` BETWEEN %f AND %f THEN CAST( details.`value` AS DECIMAL( 65, 30 ) )
				   ELSE NULL
				   END ) AS max,
			  MIN( CASE
				   WHEN details.`field_number` BETWEEN %f AND %f THEN CAST( details.`value` AS DECIMAL( 65, 30 ) )
				   ELSE NULL
				   END ) AS min,
			  SUM( CASE
				   WHEN details.`field_number` BETWEEN %f AND %f THEN 1
				   ELSE 0
				   END ) AS count
			FROM
			  `$lead_detail_table_name` details
			  LEFT JOIN
			  `$lead_table` lead ON details.lead_id = lead.id
			WHERE
			  lead.`status` = %s AND
			  details.`form_id` = %d
SQL;
			$sql     = $wpdb->prepare( $sql, $field_number_min, $field_number_max, $field_number_min, $field_number_max, $field_number_min, $field_number_max, $field_number_min, $field_number_max, $field_number_min, $field_number_max, $field_number_min, $field_number_max, 'active', $form_id );
			$results = $wpdb->get_results( $sql, ARRAY_A );

			//Store skipped entries for WP_Error
			$filtered_id_cnt = count( explode( ',', $results[0]['filtered_ids'] ) );
			$all_ids_cnt     = count( explode( ',', $results[0]['all_ids'] ) );

			//determine if there are skipped results
			if ( $filtered_id_cnt !== $all_ids_cnt ) {

				$all_ids         = explode( ',', $results[0]['all_ids'] );
				$filtered_ids    = explode( ',', $results[0]['filtered_ids'] );
				$skipped_entries = array_diff( $all_ids, $filtered_ids );

				if ( count( $skipped_entries ) > 0 ) {
					$results[0]['debug'] = $skipped_entries;
					$results[0]['debug'] = array_values( $results[0]['debug'] );
				}
			}

			$value = $results[0];

			$this->set_cache( $form_id, $field_id, $value );
		}

		return $value;
	}

	/**
	 * Get a cached value from the database
	 *
	 * This allows us to not regenerate queries we've already performed
	 *
	 * @param $form_id
	 * @param int $field_id
	 *
	 * @return bool|mixed
	 */
	function get_cache( $form_id, $field_id = 0 ) {
		if ( class_exists( 'GravityView_Cache' ) ) {
			$Cache = new GravityView_Cache( $form_id, array(
				'field_id' => $field_id,
			) );
			$value = $Cache->get();
		} else {
			$value = wp_cache_get( $this->get_cache_key( $form_id, $field_id ) );
		}

		return $value;
	}

	/**
	 * @since 1.0
	 *
	 * @param int $form_id
	 * @param int $field_id
	 *
	 * @return string
	 */
	function get_cache_key( $form_id, $field_id = 0 ) {
		$key = sprintf( 'gv_math_%d_%d', $form_id, $field_id );

		return $key;
	}

	/**
	 * Store fetched value in cache for
	 *
	 * @since 1.0
	 *
	 * @param $form_id
	 * @param int $field_id
	 * @param $value
	 * @param null $expire
	 *
	 * @return bool
	 */
	function set_cache( $form_id, $field_id = 0, $value, $expire = null ) {
		if ( class_exists( 'GravityView_Cache' ) ) {
			$Cache = new GravityView_Cache( $form_id, array(
				'field_id' => $field_id,
			) );
			$valid = $Cache->set( $value );
		} else {
			$valid = wp_cache_set( $this->get_cache_key( $form_id, $field_id ), $value, $expire );
		}

		return $valid;
	}

	/**
	 * @since 1.0
	 *
	 * @param $atts
	 * @param $field_id
	 *
	 * @return array
	 */
	function get_aggregate_data_view( $atts, $field_id ) {

		$form_ids = array();

		$view_id = $atts['id'];

		$form_ids[] = gravityview_get_form_id( $view_id );

		$view_data = gravityview_get_current_view_data( $view_id );

		$view_data['atts']['id'] = $view_id;

		$value = $this->get_cache( $form_ids, $field_id );

		if ( ! $value ) {

			$entries = array();

			if ( count( $form_ids ) > 1 ) {
				foreach ( $form_ids as $form_id ) {
					$view_entries = array_merge( $entries, GravityView_frontend::get_view_entries( $view_data['atts'], $form_id ) );
				}
			} else {
				$view_entries = GravityView_frontend::get_view_entries( $view_data['atts'], $form_ids[0] );
			}

			if ( empty( $view_entries['entries'] ) ) {

				if ( empty( $view_id ) ) {

					$data = array(
						'atts' => $atts,
						'code' => 'no_view_found'
					);

					do_action( 'gravityview_math_log_debug', esc_html__( 'No View was found', 'gravityview-math' ), $data );

					unset( $data );

				} else {

					$data = array(
						'atts'      => $atts,
						'code'      => 'no_entries_found',
						'view_link' => admin_url( sprintf( 'post.php?post=%d&action=edit', $view_id ) ),
					);

					do_action( 'gravityview_math_log_debug', esc_html__( 'The following View does not currently contain any entries', 'gravityview-math' ), $data );

					unset( $data );

				}

				return false;
			}
			$all_values = $non_empty_values = array();

			foreach ( $view_entries['entries'] as $entry ) {
				$all_values[] = $entry[ $field_id ];

				if ( '' !== $entry[ $field_id ] ) {
					$non_empty_values[] = $entry[ $field_id ];
				} else {

					$data = array(
						'input_id' => $field_id,
						'lead'     => $entry,
						'atts'     => $atts,
						'code'     => 'empty_view_field'
					);

					do_action( 'gravityview_math_log_debug', esc_html__( 'A field in the calculation did not contain a value', 'gravityview-math' ), $data );

					unset( $data );

					if ( is_numeric( $atts['default_value'] ) ) {

						$all_values   = array_slice( $all_values, - 1 );
						$all_values[] = $non_empty_values[] = floatval( $atts['default_value'] );
					}

					$non_empty_values[] = $entry[ $field_id ];
				}
			}

			$value = array(
				'sum'   => array_sum( $all_values ),
				'max'   => max( $all_values ),
				'min'   => min( $non_empty_values ),
				'avg'   => ( sizeof( $non_empty_values ) > 0 ? array_sum( $all_values ) / sizeof( $non_empty_values ) : 0 ),
				// Prevent division by 0
				'count' => sizeof( $all_values ),
			);

			$this->set_cache( $form_ids, $field_id, $value );

			unset( $all_values, $non_empty_values );
		}

		return $value;
	}

}

new GravityView_Math_GravityForms;