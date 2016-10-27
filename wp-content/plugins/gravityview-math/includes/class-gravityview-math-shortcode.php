<?php

/**
 * @property string original_shortcode
 */
class GravityView_Math_Shortcode {

	/**
	 * @var int The max number of decimal digits to display. Overridden by `gravityview/math/precision` filter
	 * @since 1.0
	 */
	private $precision_level = 16;

	/**
	 * @var mixed $default_value when {merge_tag} value returns empty or 0. If 'skip' then skip the current formula
	 */
	private $default_value = 'skip';

	/**
	 * @var array {
	 * @type string $formula If defined, use this for the equation instead of content inside the shortcode
	 * @type string $format Format for output. Options: `raw` (no number formatting)
	 * @type int $decimals Number of decimals to use. Default: '' (empty string). If not set, shows calculated # of decimals
	 * @type bool $debug Whether or not to display debug content. {@see GravityView_Math_Shortcode::report_errors}
	 * @type string $default_value The default value to display if error or warning
	 * @type bool $notices Whether to show or hide accuracy messages
	 * }
	 * @since 1.0
	 */
	private static $default_atts = array(
		'formula'       => '', // If defined, use this for the equation instead of content inside the shortcode
		'format'        => '', // raw
		'decimals'      => '',       // If not set, use original decimals
		'debug'         => false,
		'default_value' => 'skip',
		'notices'       => false,
	);

	/**
	 * @var GravityView_Math_Engine
	 * @since 1.0
	 */
	private $calculator;


	/**
	 * @var GravityView_Math_Report
	 * @since 1.1
	 */
	public $reporter;

	/**
	 * @var GravityView_Math_Shortcode
	 * @since 1.0
	 */
	public static $instance = null;

	/**
	 * @since 1.0
	 *
	 * @param GravityView_Math_Engine $calculator
	 * @param GravityView_Math_Report $reporter
	 *
	 * @return GravityView_Math_Shortcode
	 */
	public static function get_instance( $calculator, $reporter ) {

		if ( ! self::$instance ) {
			self::$instance = new self( $calculator, $reporter );
		}

		return self::$instance;
	}

	/**
	 * @param GravityView_Math_Engine $calculator
	 * @param GravityView_Math_Report $reporter
	 */
	private function __construct( $calculator, $reporter ) {

		$this->calculator = $calculator;
		$this->reporter   = $reporter;

		/**
		 * @filter `gravityview/math/precision`
		 * @since 1.0
		 *
		 * @param int $float_precision The number of decimal places to use as a maximum precision level
		 */
		$this->precision_level = (int) apply_filters( 'gravityview/math/precision', $this->precision_level );

		$this->add_hooks();
	}

	/**
	 * Register the shortcode
	 *
	 * @since 1.0
	 */
	public function add_hooks() {
		add_shortcode( 'gvmath', array( $this, 'do_shortcode' ), 11 );
		add_shortcode( 'gv_math', array( $this, 'do_shortcode' ), 11 );
		add_filter( 'gravityview/merge_tags/do_replace_variables', array( $this, 'dont_strip_gv_shortcodes' ), 2, 11 );
	}

	/**
	 * @param $bool
	 * @param $text
	 *
	 * @return bool
	 */
	public function dont_strip_gv_shortcodes( $bool = true, $text ) {

		if ( has_shortcode( $text, 'gv_math' ) || has_shortcode( $text, 'gvmath' ) ) {
			$bool = false;
		}

		return $bool;
	}

	/**
	 * WordPress converts minus to "–" (ndash)
	 *
	 * @since 1.0
	 *
	 * @param string $original_formula
	 *
	 * @return string Formula with tags and whitespaces stripped, with thousands separators taken out, and dashes converted to minus
	 */
	private function sanitize_formula( $original_formula ) {
		global $wp_locale;

		$formula = $original_formula;

		$formula = wp_strip_all_tags( $formula ); // Fix BR or P for shortcodes with newlines

		// Remove mdash
		$formula = str_replace( array( '&#8211;' ), array( '-' ), $formula );

		// replace multiple white spaces with single space
		$formula = preg_replace( '/\s+/', ' ', $formula );

		// When it's a function, the commas separating the arguments get all screwed up. Just process numbers.
		if ( is_numeric( $original_formula ) ) {

			/**
			 * @filter `gravityview/math/thousands_sep` Change the thousands separator used to clean GF merge tag values
			 * @since 1.0
			 *
			 * @param string $thousands_sep The thousands separator for numbers. Normally either a comma or dot
			 */
			$thousands_sep = apply_filters( 'gravityview/math/thousands_sep', $wp_locale->number_format['thousands_sep'] );

			// Strip the thousands separator in already-processed GF merge tags
			$formula = str_replace( $thousands_sep, '', $formula );

			// Force default decimal point
			$formula = str_replace( $wp_locale->number_format['decimal_point'], '.', $formula );

		}

		// Strip currencies
		if ( class_exists( 'RGCurrency' ) ) {

			$currencies = RGCurrency::get_currencies();

			foreach ( $currencies as $currency ) {
				$strip_currency = array( $currency['symbol_right'], $currency['symbol_left'] );
				if ( ! empty( $currency['symbol_old'] ) ) {
					$strip_currency[] = $currency['symbol_old'];
				}
				$formula = str_replace( $strip_currency, '', $formula );
			}
		}

		/**
		 * @filter `gravityview/math/sanitize` Modify the sanitization
		 * @since 1.0
		 *
		 * @param string $formula The formula
		 */
		$formula = apply_filters( 'gravityview/math/sanitize', $formula );

		return $formula;
	}

	/**
	 * If a default value is set and not '' or false, then use it. Otherwise, use the form's default value
	 *
	 * @see GVMathAddOn::get_default_value() Modifies the default value for each form
	 *
	 * @since 1.0
	 *
	 * @param array $atts Attributes array passed to the shortcode
	 *
	 * @return mixed The default value to use if the equation does not have a valid output
	 */
	private function get_default_value( $atts ) {

		/**
		 * @filter `gravityview/math/shortcode/default_value` Modify the default value before being processed
		 * @param[in,out] mixed $this->default_value The default value to modify
		 */
		$default_value = apply_filters( 'gravityview/math/shortcode/default_value', $this->default_value, $atts );

		if( ! isset( $atts['default_value'] ) ) {
			return $default_value;
		}

		if( '' === $atts['default_value'] || 'false' === $atts['default_value'] ) {
			return $default_value;
		}

		if( ! GravityView_Math_Shortcode::is_valid_default_value( $atts['default_value'] ) ) {
			return $default_value;
		}

		return $atts['default_value'];
	}

	/**
	 * Process the shortcode
	 *
	 * @since 1.0
	 *
	 * @see GravityView_Math_Shortcode::$default_atts
	 *
	 * @param array $atts Attributes for the shortcode.
	 * @param string $content
	 * @param string $shortcode
	 *
	 * @return mixed|string|void
	 */
	public function do_shortcode( $atts = array(), $content = '', $shortcode = 'gv_math' ) {

		$this->default_value = $this->get_default_value( $atts );

		// Don't use shortcode_atts() because we don't want to filter any other defined attributes
		$atts = wp_parse_args( $atts, self::$default_atts );

		// If content is empty, use the `formula` parameter instead
		$formula = ( '' === $content ) ? $atts['formula'] : $content;

		// Strip whitespace, <br>s
		$formula = $this->sanitize_formula( $formula );

		/**
		 * @filter `gravityview/math/shortcode/before` Modify the formula before being processed
		 * @since 1.0
		 * @param[in,out] string $formula The math formula to modify
		 * @param[in] array $atts Shortcode parameters
		 * @param[in] string $content Content passed to the shortcode
		 * @param[in] string $shortcode Shortcode used (default: `gv_math`)
		 * @param[in] GravityView_Math_Shortcode $this Current object
		 */
		$formula = apply_filters( 'gravityview/math/shortcode/before', $formula, $atts, $content, $shortcode, $this );

		//if the flag is true then there is an error or warning to explain this
		if ( true === $this->reporter->get_skip_flag() ) {
			$result = '';
		} //if the formula is empty and the skipFlag is not true, log empty formula error
		elseif ( '' === $formula ) {

			$data = array(
				'atts'    => $atts,
				'content' => $content,
				'code'    => 'empty_formula'
			);

			do_action( 'gravityview_math_log_error', esc_html__( 'The [gv_math] formula was empty', 'gravityview-math' ), $data );

			unset( $data );

			$result = '';

		} else {
			$result = $this->process_formula( $formula, $atts, $shortcode );
		}


		/**
		 * @filter `gravityview/math/shortcode/before` Modify the output of the shortcode
		 * @since 1.0
		 * @param[in,out] string $result Shortcode output
		 * @param[in] array $atts Shortcode parameters
		 * @param[in] string $content Content passed to the shortcode
		 * @param[in] string $shortcode Shortcode used (default: `gv_math`)
		 * @param[in] GravityView_Math_Shortcode $this Current object
		 */
		$output = apply_filters( 'gravityview/math/shortcode/output', $result, $atts, $content, $shortcode, $this );

		return $output;
	}

	/**
	 * Run the formula and format the result
	 *
	 * @uses GravityView_Math_Engine::result
	 *
	 * @since 1.0
	 *
	 * @param string $formula The math formula to run
	 * @param array $atts
	 * @param string $shortcode Shortcode used. Default: `gv_math`
	 *
	 * @return mixed|string|void
	 */
	private function process_formula( $formula = '', $atts = array(), $shortcode = 'gv_math' ) {

		$result = '';

		try {

			$result = $this->calculator->result( $formula );

		} catch ( Exception $e ) {

			$data = array(
				'atts'               => $atts,
				'trace'              => $e,
				'calculated_formula' => $formula,
				'code'               => 'calc_error'
			);

			do_action( 'gravityview_math_log_error', esc_html__( 'Error', 'gravityview-math' ), $data );

			unset( $data );
		}

		// If the $atts['format'] isn't set as raw, then format the number
		if ( 'raw' !== $atts['format'] && is_numeric( $result ) ) {

			$result = $this->format_number( $result, $atts['decimals'] );
		}

		return $result;
	}

	/**
	 * Intelligently format a number
	 *
	 * If you don't define the number of decimal places, then it will use the existing number of decimal places. This is done
	 * in a way that respects the localization of the site.
	 *
	 * If you do define decimals, it uses number_format_i18n()
	 *
	 * @see number_format_i18n()
	 *
	 * @since 1.0
	 *
	 * @param int|float|string|double $number A number to format
	 * @param int|string $decimals Optional. Precision of the number of decimal places. Default '' (use existing number of decimals)
	 *
	 * @return string Converted number in string format.
	 */
	private function format_number( $number, $decimals = '' ) {
		global $wp_locale;

		if ( '' === $decimals ) {

			$decimal_point = isset( $wp_locale ) ? $wp_locale->number_format['decimal_point'] : '.';

			/**
			 * Calculate the position of the decimal point in the number
			 *
			 * @see http://stackoverflow.com/a/2430144/480856
			 */
			$decimals = strlen( substr( strrchr( $number, $decimal_point ), 1 ) );
		}

		// Force a max precision of decimal places
		$decimal_places = ( $this->precision_level && $decimals > $this->precision_level ) ? $this->precision_level : $decimals;

		$number = number_format_i18n( $number, $decimal_places );

		return $number;
	}

	/**
	 * Validates shortcode supplied default value
	 *
	 * @since 1.0
	 *
	 * @see GravityView_Math_Shortcode::do_shortcode
	 *
	 * @param mixed $value Default value setting
	 *
	 * @return bool True: valid default value; False: invalid
	 */
	static public function is_valid_default_value( $value ) {
		return is_numeric( $value ) || $value === "skip";
	}

}