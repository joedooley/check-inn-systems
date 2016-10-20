<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Beaver_Tunnels_Meta_Box_Builder {

	private $id = '';
	private $title = '';
	private $context = 'advanced';
	private $priority = 'default';
	private $screens = array();
	private $fields = array();

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 *
	 * @param array $metabox Array of metabox settings
	 */
	public function __construct( $metabox ) {

		if ( ! is_array( $metabox ) ) {
			return;
		}

		if ( isset( $metabox['id'] ) ) {
			$this->id = $metabox['id'];
		}

		if ( isset( $metabox['title'] ) ) {
			$this->title = $metabox['title'];
		}

		if ( isset( $metabox['context'] ) ) {
			$this->context = $metabox['context'];
		}

		if ( isset( $metabox['priority'] ) ) {
			$this->priority = $metabox['priority'];
		}

		if ( isset( $metabox['screens'] ) ) {
			$this->screens = $metabox['screens'];
		}

		if ( isset( $metabox['fields'] ) ) {
			$this->fields = $metabox['fields'];
		}

		$this->actions();

	}

	/**
	 * Meta Box Actions
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function actions() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
	}

	/**
	 * Creates a meta box for each screen
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {

		foreach( $this->screens as $screen ) {

			add_meta_box(
				$this->id,
				$this->title,
				array( $this, 'add_meta_box_callback' ),
				$screen,
				$this->context,
				$this->priority
			);

		}

	}

	/**
	 * Generates the meta box output
	 *
	 * @since 1.0.0
	 *
	 * @param object $post The post object
	 */
	public function add_meta_box_callback( $post ) {

		wp_nonce_field( $this->id . '_metabox', $this->id . '_metabox' );
		$this->build_fields( $post );

	}

	/**
	 * Builds the specified fields
	 *
	 * @since 1.0.0
	 *
	 * @param  object $post The post object
	 *
	 * @return void
	 */
	public function build_fields( $post ) {

		$output = '';
		foreach ( $this->fields as $field ) {

			$label = '<label for="' . $field['id'] . '">' . $field['label'] . '</label>';
			$current_value = get_post_meta( $post->ID, $field['id'], true );

			switch ( $field['type'] ) {

				case 'heading':
					$input = $this->heading_field( $field );
					break;

				case 'text':
					$input = $this->text_field( $field, $current_value );
					break;

				case 'checkbox':
					$input = $this->checkbox_field( $field, $current_value );
					break;

				case 'select':
					$input = $this->select_field( $field, $current_value );
					break;

				default:
					$input = $this->text_field( $field, $current_value );
					break;

			}

			switch( $field['type'] ) {
				case 'heading':
					$output .= $this->row_format_heading( $input );
					break;
				default:
					$output .= $this->row_format( $label, $input );
					break;
			}

		}

		echo '<table class="form-table"><tbody>' . $output . '</tbody></table>';

	}

	/**
	 * Heading field
	 *
	 * @since 1.0.0
	 *
	 * @param  array $field The field settings
	 *
	 * @return string        The field output
	 */
	private function heading_field( $field ) {

		$input = sprintf(
			'<div style="font-size: 16px; font-weight:bold;">%s</div>',
			$field['label']
		);

		if ( isset( $field['desc'] ) ) {
			$input .= sprintf(
				'<div style="font-weight: normal; font-style: italic; color: #666;">%s</div>',
				$field['desc']
			);
		}

		return $input;

	}

	/**
	 * Text field
	 *
	 * @since 1.0.0
	 *
	 * @param  array $field         The field settings
	 * @param  string $current_value The current value of the field
	 *
	 * @return string                The field output
	 */
	private function text_field( $field, $current_value ) {

		$attr = $this->get_attributes( $field );

		if ( strlen( $current_value ) == 0 && isset( $field['default'] ) ) {
			$current_value = $field['default'];
		}

		$input = sprintf(
			'<input %s id="%s" name="%s" value="%s"%s>',
			'',
			$field['id'],
			$field['id'],
			$current_value,
			$attr
		);

		if ( isset( $field['desc'] ) ) {
			$input .= sprintf(
				'<p class="description">%s</p>',
				$field['desc']
			);
		}

		return $input;

	}

	/**
	 * Builds a string of attributes for a field
	 *
	 * @since 1.0.0
	 *
	 * @param  array $field The field attributes
	 *
	 * @return string        The string of field attributes
	 */
	private function get_attributes( $field ) {

		if ( ! isset( $field['attributes'] ) || ! is_array( $field['attributes'] ) ) {
			return '';
		}

		$attr_string = '';
		foreach( $field['attributes'] as $key => $value ) {
			$attr_string .= ' ' . $key . '="' . $value . '"';
		}
		return $attr_string;

	}

	/**
	 * Checkbox field
	 *
	 * @since 1.0.0
	 *
	 * @param  array $field         The field settings
	 * @param  string $current_value The current value of the field
	 *
	 * @return string                The field output
	 */
	private function checkbox_field( $field, $current_value ) {

		$input =  sprintf(
			'<input %s id="%s" name="%s" type="checkbox" value="1">',
			$current_value === '1' ? 'checked' : '',
			$field['id'],
			$field['id']
		);

		if ( isset( $field['desc'] ) ) {
			$input .= sprintf(
				' <span class="description">%s</span>',
				$field['desc']
			);
		}

		return $input;

	}

	/**
	 * Select field
	 *
	 * @since 1.0.0
	 *
	 * @param  array $field         The field settings
	 * @param  string $current_value The current value of the field
	 *
	 * @return string                The field output
	 */
	private function select_field( $field, $current_value ) {

		if ( ! is_array( $current_value ) && strlen( $current_value ) == 0 && isset( $field['default'] ) ) {
			$current_value = $field['default'];
		}

		$input = sprintf(
			'<select id="%s" name="%s"%s>',
			$field['id'],
			( isset( $field['multiple'] ) && true === $field['multiple'] ) ? $field['id'] . '[]' : $field['id'],
			( isset( $field['multiple'] ) && true === $field['multiple'] ) ? ' multiple="multiple"' : ''
		);

		if ( ( ! isset( $field['multiple'] ) || true !== $field['multiple'] ) && ( isset( $field['chosenjs'] ) && true === $field['chosenjs'] ) ) {
			$input .= '<option value=""></option>';
		}

		if ( isset( $field['optgroup'] ) && true === $field['optgroup'] ) {

			foreach ( $field['options'] as $optgroup ) {

				$input .= sprintf(
					'<optgroup label="%s">',
					$optgroup['title']
				);

				foreach( $optgroup['options'] as $key => $label ) {

					$selected = false;
					if ( ( is_array( $current_value ) && in_array( $key, $current_value ) ) || $current_value == $key ) {
						$selected = true;
					}

					$input .= sprintf(
						'<option %s value="%s">%s</option>',
						$selected ? 'selected="selected"' : '',
						$key,
						$label
					);

				}

				$input .= '</optgroup>';

			}

		} else {

			foreach( $field['options'] as $key => $label ) {

				$selected = false;
				if ( ( is_array( $current_value ) && in_array( $key, $current_value ) ) || $current_value == $key ) {
					$selected = true;
				}

				$input .= sprintf(
					'<option %s value="%s">%s</option>',
					$selected ? 'selected="selected"' : '',
					$key,
					$label
				);
			}

		}

		$input .= '</select>';

		if ( isset( $field['desc'] ) ) {
			$input .= sprintf(
				'<p class="description">%s</p>',
				$field['desc']
			);
		}

		if ( isset( $field['chosenjs'] ) && true === $field['chosenjs'] ) {

			$this->enqueue_chosen();

			$allow_single_deselect = '';
			if ( isset( $field['chosenjs_single_deselect'] ) && true === $field['chosenjs_single_deselect'] ) {
				$allow_single_deselect = ', allow_single_deselect: true';
			}

			ob_start();
			?>
			<script>
			jQuery( document ).ready(function($) {
				jQuery('#<?php echo $field['id']; ?>').chosen({width: "100%"<?php echo $allow_single_deselect; ?>});
			});
			</script>
			<style>
				#<?php echo $field['id']; ?>_chosen { width: 100% !important; }
			</style>
			<?php
			$input .= ob_get_clean();

		}

		return $input;

	}

	/**
	 * Build the row formatting
	 *
	 * @since 1.0.0
	 *
	 * @param  string $label The field label
	 * @param  string $input The field HTML
	 *
	 * @return string        The row HTML
	 */
	public function row_format( $label, $input ) {
		return sprintf(
			'<tr><th scope="row">%s</th><td>%s</td></tr>',
			$label,
			$input
		);
	}

	/**
	 * Build the row formatting for the Heading field
	 *
	 * @since 1.0.0
	 *
	 * @param  string $input The field HTML
	 *
	 * @return string        The row HTML
	 */
	private function row_format_heading ( $input ) {
		return sprintf(
			'<tr><th colspan="2" style="padding-bottom: 0;">%s<hr></th></tr>',
			$input
		);
	}

	/**
	 * Save the field data
	 *
	 * @since 1.0.0
	 *
	 * @param  string $post_id The post ID
	 *
	 * @return void
	 */
	public function save_post( $post_id ) {

		if ( ! isset( $_POST[ $this->id . '_metabox' ] ) ) {
			return $post_id;
		}

		$nonce = $_POST[ $this->id . '_metabox' ];
		if ( ! wp_verify_nonce( $nonce, $this->id . '_metabox' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		foreach ( $this->fields as $field ) {

			if ( isset( $_POST[ $field['id'] ] ) ) {

				switch ( $field['type'] ) {

					case 'email':
						$_POST[ $field['id'] ] = sanitize_email( $_POST[ $field['id'] ] );
						break;

					case 'text':
						if ( isset( $field['attributes'] ) && isset( $field['attributes']['type'] ) && 'number' == $field['attributes']['type'] ) {
							if ( ! is_numeric( $_POST[ $field['id'] ] ) ) {
								$_POST[ $field['id'] ] = '10';
								break;
							}
						}

						$_POST[ $field['id'] ] = sanitize_text_field( $_POST[ $field['id'] ] );
						break;

				}

				update_post_meta( $post_id, $field['id'], $_POST[ $field['id'] ] );

			} else {

				switch ( $field['type'] ) {

					case 'checkbox':
						update_post_meta( $post_id, $field['id'], '0' );
						break;

					case 'select':
						update_post_meta( $post_id, $field['id'], '' );
						break;

				}

			}

		}

	}

	/**
	 * Chosen jQuery Plugin
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function enqueue_chosen() {
		wp_enqueue_script( 'chosen', BEAVER_TUNNELS_PLUGIN_URL . 'lib/chosen/chosen.jquery.min.js', array('jquery'), '1.5.1' );
		wp_enqueue_style( 'chosen', BEAVER_TUNNELS_PLUGIN_URL . 'lib/chosen/chosen.min.css' );
	}

}
