<?php


if ( class_exists( 'GFForms' ) ) {
	GFForms::include_addon_framework();

	class GVMathAddOn extends GFAddOn {

		protected $_version = '1.0';

		protected $_min_gravityforms_version = '1.9';

		protected $_slug = 'gravityview-math';

		protected $_path = 'gravityview-math/gravityview-math.php';

		protected $_full_path = __FILE__;

		protected $_title = 'Math by GravityView';

		protected $_short_title = 'Math by GravityView';

		/**
		 * @var string|array A string or an array of capabilities or roles that have access to the settings page
		 */
		protected $_capabilities_settings_page = array( 'manage_options', 'gform_full_access' );

		/**
		 * @var string|array A string or an array of capabilities or roles that have access to the plugin page
		 */
		protected $_capabilities_plugin_page = array( 'manage_options', 'gform_full_access' );

		/**
		 * @var GVMathAddOn
		 */
		private static $instance;

		/**
		 * We're not able to set the __construct() method to private because we're extending the GFAddon class, so
		 * we fake it. When called using `new GravityView_Settings`, it will return get_instance() instead. We pass
		 * 'get_instance' as a test string.
		 *
		 * @see get_instance()
		 *
		 * @param string $prevent_multiple_instances
		 */
		public function __construct( $prevent_multiple_instances = '' ) {

			$this->_title = __( 'Math by GravityView', 'gravityview-math' );
			$this->_short_title = __( 'Math by GravityView', 'gravityview-math' );

			parent::__construct();
		}

		/**
		 * @return GVMathAddOn
		 */
		public static function get_instance() {

			if( empty( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function init_frontend() {
			parent::init_frontend();

			add_filter( 'gravityview/math/shortcode/default_value', array( $this, 'get_default_value' ), 10, 2 );
		}

		public function form_settings_fields( $form ) {
			return array(
				array(
					'title'  => esc_html__( 'Math by GravityView', 'gravityview-math' ),
					'fields' => array(
						array(
							'feedback_callback' => array( 'GravityView_Math_Shortcode', 'is_valid_default_value' ),
							'label'             => esc_html__( 'Default Entry Value', 'gravityview-math' ) . '<p class="description">' . esc_html__( 'Choose a default value shown when an calculations are impossible because an entry value is empty. Calculations with empty fields are skipped by default.', 'gravityview-math' ) . '</p>',
							'type'              => 'text',
							'name'              => 'default_value',
							'class'             => 'small',
							'default_value'     => 'skip',
						),
					)
				)
			);
		}

		/**
		 * Get the supplied default value for a form otherwise default to 'skip'
		 *
		 * @param string $default_value
		 * @param $atts
		 *
		 * @todo add default value for each field and for view
		 * @return mixed|string
		 */
		public function get_default_value( $default_value = 'skip', $atts ) {

			$form  = '';
			$id    = ! isset( $atts['id'] ) ? null : $atts['id'];
			$scope = ! isset( $atts['scope'] ) ? null : $atts['scope'];

			if ( ! $scope ) {
				if ( isset( $atts['default_value'] ) ) {
					$default_value = $atts['default_value'];
				} else {
					$default_value = 'skip';
				}

				return $default_value;
			}

			switch ( $scope ):
				case 'form':
					$form_id = $id;
					$form    = GFAPI::get_form( $form_id );
					break;
				case 'visible':
					$form = GravityView_View::getInstance()->getFormId();
					break;
				case 'view':
					$form = GravityView_View::getInstance()->getFormId();
					break;
				case 'entry':
					$form = gravityview_get_form_from_entry_id( $id );
					break;
			endswitch;

			$settings = $this->get_form_settings( $form );

			if ( is_array( $settings ) && array_key_exists( 'default_value', $settings ) ) {
				$default_value = $settings['default_value'];
			} else {
				$default_value = 'skip';
			}

			return $default_value;

		}
	}

	GFAddOn::register( GVMathAddOn::get_instance() );
}