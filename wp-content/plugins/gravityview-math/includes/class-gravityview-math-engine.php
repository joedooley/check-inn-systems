<?php
/**
 * @package GravityView_Math
 */

/**
 * Wrapper for the Hoa Math package
 * @see https://github.com/hoaproject/Math
 */
class GravityView_Math_Engine {

	/** @var GravityView_Math_Engine */
	public static $instance = null;

	/** @var  \Hoa\Compiler\Llk\Parser */
	var $compiler;

	/** @var Hoa\Math\Visitor\Arithmetic */
	var $visitor;

	/**
	 * @return GravityView_Math_Engine
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {

		// 1. Load the compiler.
		$this->compiler = Hoa\Compiler\Llk::load(
			new Hoa\File\Read( 'hoa://Library/Math/Arithmetic.pp' )
		);

		// 2. Load the visitor, aka the “evaluator”.
		$this->visitor = new Hoa\Math\Visitor\Arithmetic();

		/**
		 * @action `gravityview/math/init` Allow tapping in for custom function, constant definitions
		 * @since 1.0
		 * @see https://github.com/hoaproject/Math
		 *
		 * @param GravityView_Math_Engine $this
		 */
		do_action( 'gravityview/math/init', $this );

	}

	/**
	 * @param string $expression
	 *
	 * @since 1.0
	 *
	 * @return float|string
	 */
	function result( $expression = '' ) {

		// Empty expression
		if ( '' === $expression ) {
			return '';
		}

		$abstract_syntax_tree = $this->compiler->parse( $expression );

		return $this->visitor->visit( $abstract_syntax_tree );
	}

}