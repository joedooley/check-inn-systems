<?php

/**
 * Class GravityView_Math_Report
 * @todo do something about error output in context of do_shortcode
 */
class GravityView_Math_Report {

	/**
	 * @var GravityView_Math_Report
	 */
	static $instance;

	/**
	 * @var WP_Error[] $alerts
	 */
	private static $alerts = array();

	/**
	 * flag a formula to be skipped
	 * @var bool
	 */
	private static $skip_flag = false;

	/**
	 * track the number of gv_math shortcodes on a page
	 * @var int[]
	 */
	private static $index = array();

	/**
	 * @var string The capability required to view debug messages
	 * @since 1.0
	 */
	private $debug_cap = 'edit_others_posts';

	/**
	 * @var bool Display errors and debugging messages to administrators
	 */
	private $debug = false;

	/**
	 * @var bool Display Notices to End Users
	 */
	private $notices = false;

	/**
	 * @return GravityView_Math_Report
	 */
	public static function get_instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * GravityView_Math_Report constructor.
	 */
	private function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {
		add_action( 'gravityview_math_log_error', array( $this, 'log_from_action' ), 10, 4 );
		add_action( 'gravityview_math_log_debug', array( $this, 'log_from_action' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

		add_filter( 'gravityview/math/shortcode/before', array( $this, 'add_shortcode_debugging' ), 10, 5 );
		add_filter( 'gravityview/math/shortcode/output', array( $this, 'add_report_links' ), 10, 5 );
	}

	/**
	 * Enqueue the script to enhance GV Math debugging output
	 *
	 * @since 1.0
	 */
	public function register_scripts() {
		wp_register_script( 'gv-math-fe-debug', plugins_url( 'assets/js/gv-math-fe-debug.js', GRAVITYVIEW_MATH_FILE ), array( 'jquery' ), GRAVITYVIEW_MATH_VERSION, true );
	}

	/**
	 * Add links to the reports
	 *
	 * @since 1.0
	 *
	 * @param string $result Shortcode output
	 * @param array $atts Shortcode parameters
	 * @param string $content Content passed to the shortcode
	 * @param string $shortcode Shortcode used (default: `gv_math`)
	 * @param GravityView_Math_Shortcode $this Current object
	 *
	 * @return string Original result with debugging links appended, if notices are enabled
	 */
	function add_report_links( $result, $atts, $content, $shortcode, $object ) {

		$link = '';

		// Check to display notices in the event of a warning or error
		// Determine if an inline notice or debug link should be include with output.
		if ( ( wp_validate_boolean( $atts['notices'] ) || current_user_can( $this->debug_cap ) ) && ! $this->suppress_alerts() ) {
			//get the current index
			$index = $this->get_current_index( true );
			$link = $this->get_debug_link( $index, current_user_can( $this->debug_cap ) );
			$link = sprintf( '<span id="gv-math-%d" class="gv-math-output">%s</span>', $index, $link );
		}
		
		return $result . $link;
	}

	/**
	 * Add reporting for a shortcode
	 *
	 * @since 1.0
	 *
	 * @param string $formula The math formula to modify
	 * @param array $atts Shortcode parameters
	 * @param string $content Content passed to the shortcode
	 * @param string $shortcode Shortcode used (default: `gv_math`)
	 * @param GravityView_Math_Shortcode $object Current object
	 *
	 * @return string Original formula, not modified
	 */
	public function add_shortcode_debugging( $formula, $atts, $content, $shortcode, $object ) {

		// If debug is set and not 0 or false, debug is on
		$this->debug = $this->get_debug( $atts );

		$this->notices = wp_validate_boolean( $atts['notices'] );

		//don't setup processing unless there the gv_math shortcode is present
		add_filter( 'the_content', array( $this, 'report_errors' ), 12 ); //insert frontend warnings/errors only once
		
		//reset the flag each time
		$this->set_skip_flag();

		$this->add_index( array(
			'index'   => $this->get_current_index(),
			'debug'   => $this->debug,
			'notices' => $this->notices
		) );

		return $formula;
	}

	/**
	 * Add the current shortcode into the indexes of the Reporter class
	 *
	 * @see GravityView_Math_Shortcode::do_shortcode
	 *
	 * @param $atts
	 *
	 * @return bool
	 */
	private function get_debug( $atts ) {

		// Added in 4.0, don't assume exists
		$atts['debug'] = function_exists( 'wp_validate_boolean' ) ? wp_validate_boolean( $atts['debug'] ) : ! empty( $atts['debug'] );

		if ( true == $atts['debug'] ) {
			$return = true;
		} elseif ( false == $atts['debug'] && ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && ( defined( 'GV_MATH_DEBUG' ) && GV_MATH_DEBUG ) ) {
			$return = defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : true;
		} else {
			$return = false;
		}

		return $return;
	}

	/**
	 *
	 *
	 * @since 1.2
	 *
	 * @param array $errors Pre-sanitized error output {@see GravityView_Math_Report::get_error_message_output()}
	 * @param array $messages
	 *
	 * @return string
	 */
	private function get_error_report_html( $errors = array(), $messages = array() ) {

		$output = '';

		if ( $messages ) {
			$output .= '<p><em>' . implode( '</em></p><p><em>', $messages ) . '</em></p>';
		}

		if ( $errors && ! $this->suppress_alerts() ) {

			$output .= "<ul>";

			foreach ( $errors as $key => $error ) {

				//array keys are organized $index-alert_type
				$matches = explode( '-', $key );

				list( $id, $class ) = $matches;

				//check if the shortcode has multiple warnings
				$shortcode_id = false !== strstr( $id, '_', true ) ? strstr( $id, '_', true ) : $id;

				$output .= "<li id='gv-math-{$class}-{$id}' class='debug_log gv-math-{$class}'>";
				$output .= '<a href="#gv-math-' . $shortcode_id .'">[' . $shortcode_id .']</a>&nbsp;';
				$output .= $error;
	            $output .= '</li>';
			}

			$output .= '</ul>';
		}

		return ! empty( $output ) ? '<div class="gv-math-notices">' . $output . '</div>' : '';
	}

	/**
	 * Include errors and warnings after the_content
	 *
	 * @since 1.0
	 *
	 * @param string $content Existing post content
	 *
	 * @return string Post content with errors/warnings appended, if any
	 */
	public function report_errors( $content ) {

		if ( $this->get_alert_messages() ) {
			$message = array();
			$errors  = array();

			if ( $this->debug && current_user_can( $this->debug_cap ) ) {

				$errors = $this->get_alert_messages('errors');

				//only load this script if the user has the debug_caps and debug is on
				wp_enqueue_script( 'gv-math-fe-debug' );

				/**
				 * @filter `gravityview/math/admin_notice` Message shown when there is a warning with the calculation.
				 * @since 1.2
				 * @param string $message Default: "* Results may not be accurate."
				 */
				$message['admin'] = apply_filters( 'gravityview/math/admin_notice', __( 'You can only see this message because you are logged in and have permissions.', 'gravityview-math' ) );

			} else {

				if ( $this->has_warnings() ) {

					/**
					 * @filter `gravityview/math/accuracy_message` Message shown when there is a warning with the calculation.
					 * @since 1.0
					 * @param string $message Default: "* Results may not be accurate."
					 */
					$message['warning'] = apply_filters( 'gravityview/math/accuracy_message', __( '* Results may not be accurate.', 'gravityview-math' ) );
				}

				if ( $this->has_errors() ) {

					/**
					 * @filter `gravityview/math/accuracy_message` Message shown when there is a warning with the calculation.
					 * @since 1.0
					 * @param string $message Default: "* Results may not be accurate."
					 */
					$message['error'] = apply_filters( 'gravityview/math/no_results_message', __( '** No Results Currently Available.', 'gravityview-math' ) );
				}

			}

			$message = $this->filter_notices( $message );

			$content .= $this->get_error_report_html( $errors, $message );
		}

		return $content;
	}

	/**
	 * Add Errors to GFLogging and if debug is on, store for output
	 *
	 * @since 1.0
	 *
	 * @uses GFLogging::include_logger
	 * @uses GFLogging::log_message
	 *
	 * @param string $message
	 * @param null $data
	 *
	 * @return void
	 */
	public function log_from_action( $message = '', $data = null ) {

		switch( current_filter() ) {
			case 'gravityview_math_log_error':
				$key = 'errors';
				break;
			case 'gravityview_math_log_debug':
			default:
				$key = 'warnings';
				break;
		}

		list( $message, $data ) = $this->generate_message( $message, $data );

		$index = $this->get_current_index( true );

		$this->add_alert( $key, new WP_Error( $data['code'], $message, $data ), $index );

		if ( class_exists( "GFLogging" ) ) {
			GFLogging::include_logger();
			$function = $this->get_print_function();
			$klogger_level = ( 'errors' === $key ) ? KLogger::ERROR : KLogger::DEBUG;
			GFLogging::log_message( 'gravityview_math', $function( $message, true ) . $function( $data, true ), $klogger_level );
		}
	}

	/**
	 * Get the name of the function to print messages for debugging
	 *
	 * @since 1.0
	 *
	 * This is necessary because `ob_start()` does not allow `print_r()` inside it.
	 *
	 * @return string "print_r" or "var_export"
	 */
	private function get_print_function() {
		if ( ob_get_level() > 0 ) {
			$function = 'var_export';
		} else {
			$function = 'print_r';
		}

		return $function;
	}

	/**
	 * Construct stored data and output message for each type of Alert
	 *
	 * @since 1.0
	 *
	 * @param $message
	 * @param null $data
	 *
	 * @return array
	 */
	private function generate_message( $message, $data = null ) {

		/**
		 * Setup Defaults
		 */
		$default_atts = array(
			'default_value' => '',
			'id'            => null,
			'scope'         => ''
		);

		$input_id    = ! empty( $data['input_id'] ) ? $data['input_id'] : null;
		$lead        = ! empty( $data['lead'] ) ? $data['lead'] : array();
		$atts        = ! empty( $data['atts'] ) ? $data['atts'] : $default_atts;
		$code        = ! empty( $data['code'] ) ? $data['code'] : '';
		$entry_links = array();

		/**
		 * Determine if a $lead is an array or an array(ids)
		 * @see GFAPI::get_entry
		 */
		if ( ! isset( $lead['id'] ) ) {
			$leads = array();
			foreach ( $lead as $id ) {
				$leads[] = GFAPI::get_entry( $id );
			}
			$lead = $leads;
		} else {
			$lead = array( $lead );
		}

		//setup entry links for each lead
		foreach ( $lead as $id ) {
			$entry_link    = admin_url( sprintf( 'admin.php?page=gf_entries&amp;view=entry&id=%d&lid=%d', $id['form_id'], $id['id'] ) );
			$entry_links[] = '<a href="' . esc_url( $entry_link ) . '" target="_blank">' . $id['id'] . '</a>';
		}

		//store additional message info
		$entry_info         = ! empty( $entry_links ) ? __( 'in entries', 'gravityview-math' ) . ': ' . implode( ',', $entry_links ) : '';
		$default_value_info = isset( $atts['default_value'] ) && 'skip' !== $atts['default_value'] ? __( 'The supplied default value was used', 'gravityview-math' ) . ': ' . $atts['default_value'] : '';

		$final_data = array(
			'code'          => $code,
			'leads'         => $lead,
			'input_id'      => $input_id,
			'default_value' => $atts['default_value']
		);

		$final_message = sprintf( '%s: (%s)<br>%s %s', $message, $input_id, $entry_info, $default_value_info );

		switch ( $code ) {
			
			case 'invalid_field':
				$entry_link      = admin_url( sprintf( 'admin.php?page=gf_edit_forms&id=%d#field_%d', $lead[0]['form_id'], floor( $input_id ) ) );
				$final_message   = sprintf( '%s (%s)<br />%s', $message, $input_id, '<a href="' . esc_url( $entry_link ) . '" target="_blank">' . $lead[0]['form_id'] . '</a>' );
				$final_data[0][] = array( 'field-' . $input_id => $data['field'] );
				self::$skip_flag = true;
				break;
			
			case 'no_entries_found':
				$entry_link      = '<a href="' . esc_url( $data['view_link'] ) . '" target="_blank">' . get_the_title( $atts['id'] ) . '</a>';
				$final_message   = sprintf( '%s (%s)', esc_html( $message ), $entry_link );
				self::$skip_flag = true;
				break;
			
			case 'no_view_found':
				$final_message   = esc_html( $message );
				self::$skip_flag = true;
				break;
			
			case 'calc_error':
				$final_message   = sprintf( '%s: %s', $message, $data['trace']->getMessage() );
				$final_data[0][] = array(
					'calculated_formula' => $data['calculated_formula'],
					'error_trace'        => $data['trace']->getTraceAsString()
				);
				break;
			
			case 'empty_formula':
				$final_message = sprintf( '%s: %s = \'%s\', %s = \'%s\'', $message, __( 'formula', 'gravityview-math' ), $atts['formula'], __( 'content', 'gravityview-math' ), $data['content'] );
				break;
			
			case 'ID_not_set':
				$final_message   = sprintf( '%s, %s', $message, sprintf( __( '%s scope requires an ID to process the formula.', 'gravityview-math' ), $atts['scope'] ) );
				self::$skip_flag = true;
				break;
			
			default:
				if ( 'skip' == $atts['default_value'] && 'entry' == $atts['scope'] ) {
					self::$skip_flag = true;
				}
		}

		/**
		 * @filter `gravityview/math/debug_message` Modify debugging messages displayed
		 * @param string $final_message Message content to be output
		 * @param array $data Error data to be displayed; `code`, `leads`, `input_id`, `default_value`
		 */
		$final_message = apply_filters( 'gravityview/math/debug_message', $final_message, $data );

		return array( $final_message, $final_data );
	}

	/**
	 * Foreach gv_math shortcode on a post add it to the index
	 *
	 * @since 1.0
	 *
	 * @param bool $debug_value
	 *
	 * @return void
	 */
	public function add_index( $debug_value = false ) {
		self::$index[] = $debug_value;
	}

	/**
	 * Return the Alert Index
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_index() {
		return (array) self::$index;
	}

	/**
	 * Get current position for the debug index
	 * this fires early so later use will require -1
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_current_index( $already_advanced = false ) {

		$index = sizeof( self::$index );

		//The index has already advanced so we need to minus 1
		if( $already_advanced ) {
			$index = $index > 0 ? $index - 1 : 0;
		}

		return $index;
	}

	/**
	 * Add Errors to the Alert array only if debugging is on
	 *
	 * @since 1.0
	 *
	 * @param string $type Type of alert to add: 'warnings' or 'errors'. Default: 'warning'
	 * @param WP_Error $error
	 * @param int $line_number
	 *
	 * @return void
	 */
	public function add_alert( $type = 'warnings', WP_Error $error, $line_number = 0 ) {
		if ( $this->is_debug_active( $line_number ) ) {
			if ( is_int( $line_number ) ) {
				self::$alerts[ $line_number ][ $type ][] = $error;
			} else {
				self::$alerts[][ $type ][] = $error;
			}
		}
	}

	/**
	 * Get Error messages for supplied or current index
	 *
	 * @since 1.0
	 *
	 * @see GFCommon::display_admin_message()
	 *
	 * @param int $index requested shortcode
	 *
	 * @return array Errors, with stored errors added
	 */
	public function get_error_message( $index ) {

		$errors = array();

		if ( ! isset( self::$alerts[ $index ]['errors'] ) ) {
			return $errors;
		}

		$error = self::$alerts[ $index ]['errors'];

		if ( is_array( $error ) ) {
			/** @var WP_Error $e */
			foreach ( $error as $k => $e ) {
				$errors[ $index ][] = $this->get_error_message_output( $e );
			}
		} else {
			/** @var WP_Error $error */
			$errors[ $index ] = $this->get_error_message_output( $error );
		}

		return $errors;
	}

	/**
	 * Get prepared message output for supplied supplied WP_Error object
	 *
	 * @since 1.0
	 *
	 * @param WP_Error $error
	 *
	 * @return string Error message
	 */
	private function get_error_message_output( $error ) {

		$message = $error->get_error_message();

		$data = $error->get_error_data();

		if ( ! empty( $data ) ) {
			$message .= ' <a href="#" class="gv-math-debug-more">' . esc_html__( '(Additional info)', 'gravityview-math' ) . "</a>";

			/** @see http://php.net/manual/en/json.constants.php */
			$pretty_print = defined( 'JSON_PRETTY_PRINT' ) && defined( 'JSON_UNESCAPED_SLASHES' ) ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : true;

			$message .= is_string( $data ) ? '<span style="display:none;" class="gv-math-debug-msg">' . esc_html( $data ) . '</span>' : '<pre style="display:none;" class="gv-math-debug-msg">' . esc_html( json_encode( $data, $pretty_print ) ) . '</pre>';
		}

		return $message;
	}

	/**
	 * Get all Errors and Warning messages
	 *
	 * @since 1.0
	 *
	 * @param string $type Type of alerts to get. Options: 'all' 'errors' 'warnings'. Default: 'all'
	 *
	 * @return array
	 */
	public function get_alert_messages( $type = 'all' ) {

		$alerts = array();

		foreach ( self::$alerts as $id => $alert_type ) {

			if ( ! empty( $alert_type['warnings'] ) && in_array( $type, array( 'all', 'warnings' ) ) ) {
				foreach ( $alert_type['warnings'] as $k => $e ) {
					$key = $id . '_' . $k . '-warning';
					$alerts[ $key ] = $this->get_error_message_output( $e );
				}
			}

			if ( ! empty( $alert_type['errors'] ) && in_array( $type, array( 'all', 'errors' ) ) ) {
				foreach ( $alert_type['errors'] as $k => $e ) {
					$key = $id . '_' . $k . '-error';
					$alerts[ $key ] = $this->get_error_message_output( $e );
				}
			}
		}

		return $alerts;
	}

	/**
	 * Get Warning messages for supplied or current index
	 *
	 * @since 1.0
	 *
	 * @see GFCommon::display_admin_message()
	 *
	 * @param int $index current shortcode being requested
	 *
	 * @return array Errors, with stored errors added
	 */
	public function get_warning_message( $index ) {

		$warnings = array();

		if ( ! isset( self::$alerts[ $index ]['warnings'] ) ) {
			return $warnings;
		}

		$warning = self::$alerts[ $index ]['warnings'];

		if ( is_array( $warning ) ) {
			/** @var WP_Error $e */
			foreach ( $warning as $k => $w ) {
				$warnings[ $index ][] = $this->get_error_message_output( $w );
			}
		} else {
			/** @var WP_Error $error */
			$warnings[ $index ] = $this->get_error_message_output( $warning );
		}

		return $warnings;
	}

	/**
	 * Are there errors?
	 *
	 * @since 1.0
	 *
	 * @return bool true: Yes, there are. False: nope!
	 */
	public function has_errors() {
		$errors = $this->get_alert_messages('errors');

		return count( $errors ) > 0;
	}

	/**
	 * Get WP_Error Warning for a supplied index
	 *
	 * @param string $type Options: 'warnings' 'alerts'. Default: 'warnings'
	 * @param int $index Index
	 *
	 * @return array
	 */
	private function get_alert( $type = 'warnings', $index = null ) {
		if ( ! isset( self::$alerts[ $index ] ) || ! isset( self::$alerts[ $index ]['warnings'] ) ) {
			return false;
		}

		return self::$alerts[ $index ]['warnings'];
	}

	/**
	 * Are there warnings?
	 *
	 * @since 1.0
	 *
	 * @return bool true: Yes, there are. False: nope!
	 */
	public function has_warnings() {
		$warnings = $this->get_alert_messages('warnings');

		return count( $warnings ) > 0;
	}

	/**
	 * Get the skip status for the current formula being processed
	 * @return bool
	 */
	public function get_skip_flag() {
		return self::$skip_flag;
	}

	/**
	 * Set a flag when a formula should not be processed
	 *
	 * @param bool $flag
	 *
	 * @return bool
	 */
	public function set_skip_flag( $flag = false ) {
		return self::$skip_flag = $flag;
	}

	/**
	 * Get the link or signify a notice
	 *
	 * @param $index
	 * @param bool $debug_caps
	 *
	 * @return string
	 */
	public function get_debug_link( $index, $debug_caps = false ) {

		$debug_link = array();
		$warning    = $this->get_warning_message( $index );
		$error      = $this->get_error_message( $index );
		$title      = esc_attr__( 'Math by GravityView Notice', 'gravityview-math' );

		if ( $this->is_debug_active() && $debug_caps ) {
			if ( count( $warning ) > 0 ) {
				for ( $i = 0; $i < count( $warning[ $index ] ); $i ++ ) {
					$debug_link[] = "<sup><a href='#gv-math-warning-{$index}_{$i}' title='{$title}'>[{$index}]</a></sup>";
				}
			}

			if ( count( $error ) > 0 ) {
				for ( $i = 0; $i < count( $error[ $index ] ); $i ++ ) {
					$debug_link[] = "<sup><a href='#gv-math-error-{$index}_{$i}' title='{$title}'>[{$index}]</a></sup>";
				}
			}
		} else {
			if ( ! empty( $warning ) ) {
				$debug_link[] = "*";
			}

			if ( ! empty( $error ) ) {
				$debug_link[] = "**";
			}
		}

		return implode( ' ', $debug_link );
	}

	/**
	 * Check if the supplied index has debugging on
	 *
	 * @since 1.0
	 *
	 * @param int $index
	 *
	 * @return bool
	 */
	public function is_debug_active( $index = 0 ) {

		$status = false;
		$debug_index = $this->get_index();

		if ( $index ) {
			if ( isset( $debug_index[ $index ]['debug'] ) ) {
				$status = $debug_index[ $index ]['debug'];
			}
		} else {
			$current_index = $this->get_current_index( true );
			$status        = $debug_index[ $current_index ]['debug'];
		}

		return $status;
	}

	/**
	 * Only output final notice message if requested
	 *
	 * @param array $message
	 *
	 * @return array
	 */
	public function filter_notices( $message = array() ) {

		$suppress_errors = $this->suppress_alerts();

		if ( $suppress_errors ) {
			return array();
		}

		$debug_index = $this->get_index();

		$keep_warning = false;
		$keep_error   = false;

		foreach ( $debug_index as $key => $value ) {
			$notice_flag = (bool) $value['notices'];
			$is_warning  = $this->get_alert( 'warnings', $key );
			$is_error    = $this->get_alert( 'errors', $key );

			if ( false !== $notice_flag && false !== $is_warning ) {
				$keep_warning = true;
			}
			if ( false !== $notice_flag && false !== $is_error ) {
				$keep_error = true;
			}
		}

		if ( ! $keep_warning ) {
			unset( $message['warning'] );
		}

		if ( ! $keep_error ) {
			unset( $message['error'] );
		}

		return $message;
	}

	/**
	 * Should errors be suppressed?
	 *
	 * @since 1.0
	 *
	 * @return bool True: don't show the errors. False: show the errors. Default: false
	 */
	public function suppress_alerts() {

		/**
		 * @filter `gravityview/math/suppress_errors` Toggle whether to suppress (not show) math functionality errors
		 * @since 1.0
		 * @param boolean $suppress_errors True: don't show the errors. False: show the errors. Default: false
		 */
		return apply_filters( 'gravityview/math/suppress_errors', false );

	}


}