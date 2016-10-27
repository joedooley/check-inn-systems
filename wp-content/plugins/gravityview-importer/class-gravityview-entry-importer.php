<?php

/**
 * Handle importing a single entry at a time.
 */
class GravityView_Entry_Importer {

	/**
	 * @var int
	 */
	private $form_id = 0;

	/**
	 * @var array
	 */
	private $form = array();

	/**
	 * The number of entries before the import
	 *
	 * @var int
	 */
	private $previous_entry_id = 0;

	/**
	 * @var GF_Field[]
	 */
	private $calculation_fields = array();

	/**
	 * @var GF_Field_Total[]
	 */
	private $total_fields = array();

	/**
	 * @var GF_Field_Option[]
	 */
	private $option_fields = array();

	/**
	 * Set in prepare_field_values_for_insert
	 * @see prepare_field_values_for_insert
	 * @var boolean
	 */
	private $add_post = false;

	/**
	 * @var string
	 */
	private $currency = 'USD';

	/**
	 * @var array
	 */
	private $feed = array();

	/**
	 * The current line being processed
	 *
	 * @var int
	 */
	private $line_number = 0;

	/**
	 * Custom User Agent when no user agent is defined
	 *
	 * @var string
	 */
	private $user_agent = '';

	/**
	 * @var GravityView_Entry_Importer
	 */
	private static $instance;

	public static function getInstance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {

		$this->Reporter = GravityView_Import_Report::get_instance( $this );

		$this->feed = GV_Import_Entries_Addon::get_instance()->get_current_feed();

		$this->form_id = $this->feed['form_id'];

		$this->form = GFAPI::get_form( $this->form_id );

		$this->previous_entry_id = $this->get_max_entry_id();

		$this->currency = GFCommon::get_currency();

		$this->set_calculation_and_total_fields();

		$this->set_user_agent();

		$this->add_hooks();
	}

	/**
	 * Get the highest entry ID
	 *
	 * @since 1.0.8
	 *
	 * @return mixed
	 */
	public function get_max_entry_id() {
		/** @global wpdb $wpdb */
		global $wpdb;

		$lead_table_name = GFFormsModel::get_lead_table_name();

		$sql = "SELECT `id` FROM $lead_table_name ORDER BY `id` DESC";

		return $wpdb->get_var( $sql );
	}

	/**
	 * Get the current Form ID
	 * @return int
	 */
	public function get_form_id() {
		return $this->form_id;
	}

	/**
	 * Get the entry's currency
	 * @return int
	 */
	public function get_currency() {
		return $this->currency;
	}

	/**
	 * Get the current Form array
	 * @return array
	 */
	public function get_form() {
		return $this->form;
	}

	/**
	 * Get the previous entry ID
	 * @return int
	 */
	public function get_previous_entry_id() {
		return $this->previous_entry_id;
	}

	/**
	 * Modify the default User Agent message that's used if not set for an entry.
	 * We set it here outside the loop to improve speed.
	 *
	 * @return void
	 */
	private function set_user_agent() {

		// Not importing
		if ( empty( $this->form ) ) {
			return;
		}

		/**
		 * @filter `gravityview-import/user-agent` Modify the User Agent set by the importer
		 * @param string $user_agent Default: GravityView Import (Starting with Entry #%d)
		 */
		$this->user_agent = apply_filters( 'gravityview-import/user-agent', __( 'GravityView Import (Starting with Entry #%d)', 'gravityview-importer' ) );
	}

	public function get_user_agent() {
		return $this->user_agent;
	}

	/**
	 * Check whether the form has calculation fields
	 */
	private function set_calculation_and_total_fields() {

		// Not importing
		if ( empty( $this->form ) ) {
			return;
		}

		/** @var GF_Field $field */
		foreach ( $this->form['fields'] as $field ) {

			if ( $field->has_calculation() ) {
				$this->calculation_fields[] = $field;
				continue;
			}

			// process total field after all fields have been saved
			if ( $field->type === 'option' ) {
				$this->option_fields[] = $field;
				continue;
			}

			// process total field after all fields have been saved
			if ( $field->type === 'total' ) {
				$this->total_fields[] = $field;
				continue;
			}

		}
	}

	private function add_hooks() {

		add_action( 'gravityview-importer/process-row', array( $this, 'process_row' ), 10, 2 );

		add_filter( 'gform_post_data', array( $this, 'modify_post_data' ), 10, 3 );

		/** @since 1.1 */
		do_action( 'gravityview-importer/add-hooks', $this );
	}

	/**
	 * Gravity Forms prevents passing certain post details into the GFAPI::create_post()
	 *
	 * @param $post_data
	 * @param $form
	 * @param $entry
	 *
	 * @return mixed
	 */
	function modify_post_data( $post_data, $form, $entry ) {

		// Make sure they've granted explicit authorization to overwrite post data
		$overwrite_post_data = gravityview_importer()->get_setting( 'overwrite_post_data' );

		if ( ! empty( $post_data['ID'] ) && $overwrite_post_data !== 'yes' ) {
			return $post_data;
		}

		foreach ( $form['fields'] as $field ) {

			if ( ! isset( $entry[ $field->id ] ) ) {
				continue;
			}

			switch ( $field->type ) {
				case 'post_tags':
					$post_data['tags_input'] = explode( ',', $entry[ $field->id ] );
					break;
				case 'post_category':
					$post_data['post_category'] = explode( ',', $entry[ $field->id ] );
					break;
			}
		}

		// Already validated earlier
		if ( ! empty( $entry['post_id'] ) ) {
			$post_data['ID'] = intval( $entry['post_id'] );
		}

		// Already validated earlier
		if ( ! empty( $entry['post_author'] ) ) {
			$post_data['post_author'] = intval( $entry['post_author'] );
		}

		// Already validated earlier
		if ( ! empty( $entry['post_status'] ) ) {
			$post_data['post_status'] = $entry['post_status'];
		}

		return $post_data;
	}

	/**
	 * @param array|boolean $entry GF entry array
	 * @param boolean $force_process Force process batch
	 */
	function process_row( $row = array(), $line_number = 0 ) {

		ob_start();

		$entry = $row;

		$check_empty_row = array_filter( $row, array( $this, 'is_not_empty' ) );

		$this->line_number = ( $line_number + 1 ); // Line number, plus the header

		// Empty row!
		if ( $this->is_empty( $check_empty_row ) ) {
			$this->Reporter->addError( new WP_Error( 'empty_row', sprintf( __( 'Row %d was skipped because the mapped fields were empty.', 'gravityview-importer' ), $this->line_number ) ) );

			return;
		}

		/**
		 * Need to set a temporary ID so that GFFormsModel::get_lead_field_value() doesn't throw an error
		 * We set it to random so that the value isn't cached by GFCache.
		 * We use microtime because it's faster than generating our own number.
		 *
		 * @hack
		 */
		$entry['id'] = intval( microtime() * 1000000 );

		// Need to set this manually for RGFormsModel::get_form_meta( $lead['form_id'] );
		// in RGFormsModel::get_lead_field_value()
		$entry['form_id'] = $this->get_form_id();

		// We don't want to add posts by default
		$this->add_post = false;

		// This needs to happen before the other items because we have fake fields that cannot be checked by GF
		// Until the inputs are fixed.
		$this->process_custom_field_formats( $entry );

		// Only add the entry if the feed passes.
		if ( $this->is_feed_condition_met( $entry ) ) {

			if ( $this->check_required( $entry ) && $this->validate_entry( $entry ) ) {

				$this->add_update_entry( $entry );

			} else {

				unset( $entry['id'], $entry['row'], $entry['form_id'] );

				do_action( 'gravityview-importer/invalid-row', $entry, $line_number, $this );
			}
		}

		ob_end_flush();
		flush();

	}

	/**
	 * Fix fake inputs generated by this plugin to enable mapping unmappable fields
	 *
	 * @see GV_Import_Entries_Addon::get_field_map_choices()
	 *
	 * @param array $entry
	 */
	function process_custom_field_formats( &$entry ) {

		$this->process_list_fields( $entry );

		$this->process_checkbox_fields( $entry );

	}

	/**
	 * Some data sets have values in a non-Gravity Forms format.
	 *
	 * This modifies the checkbox values to match expected values. Instead of "YES" or "NO" for checkbox values,
	 *
	 * @filter gravityview-importer/strict-mode/fill-checkbox-choices
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	function process_checkbox_fields( &$entry ) {

		/**
		 * If true, make sure that any non-empty value uses the correct value for the input
		 * Example: The value of the input is "Apple" but the row's "Apple" column has the value of "1" - this will be autocorrected.
		 * If the value in the CSV is empty, it will still be skipped.
		 *
		 * @since 1.1
		 *
		 * @param boolean $fill_checkboxes Default: false
		 */
		$fill_checkboxes = apply_filters( 'gravityview-importer/strict-mode/fill-checkbox-choices', false );

		if ( false === $fill_checkboxes ) {
			return;
		}

		$checkbox_fields = GFCommon::get_fields_by_type( $this->get_form(), array( 'checkbox' ) );

		foreach ( $checkbox_fields as $field ) {

			foreach ( (array) $field->inputs as $i => $input ) {
				$choice = $field->choices[ $i ];
				if ( ! $this->is_empty_checkbox( $entry[ $input['id'] ], $choice['value'] ) ) {
					$entry[ $input['id'] ] = $choice['value'];
				}
			}
		}
	}

	/**
	 * Check whether checkbox values are empty
	 *
	 * The Numbers app generates checkbox cell types with "TRUE" or "FALSE" text values. We want to interpret "FALSE" as empty.
	 *
	 * Empty values include: "", "0", "FALSE"
	 *
	 * @param string $value The checkbox value
	 * @param string $choice_value The configured Gravity Forms input value.
	 *
	 * @return bool true: Checkbox should be considered empty; false: checkbox is not empty
	 */
	private function is_empty_checkbox( $value, $choice_value = '' ) {
		return empty( $value ) || ( $choice_value !== 'FALSE' && $value === 'FALSE' );
	}

	/**
	 * Creates the entry in Gravity Forms
	 *
	 * @since 1.1
	 *
	 * @param array $passed_entry Entry to add
	 *
	 * @return void
	 */
	function add_update_entry( $passed_entry ) {

		$entry = $this->process_entry_before_add( $passed_entry );

		gravityview_importer()->log_debug( 'Adding $entry: ' . print_r( $entry, true ) );

		// Get rid of the entry ID before adding an entry
		if ( ! empty( $entry['entry_id'] ) ) {
			$action      = 'update';
			$entry['id'] = $entry['entry_id'];
			unset( $entry['entry_id'] );
			$result = $this->update_entry( $entry );
		} else {

			// Get rid of the entry ID before adding an entry
			unset( $entry['id'] );

			$action = 'add';
			$result = $this->add_entry( $entry );
		}

		if ( is_wp_error( $result ) ) {

			$this->Reporter->addError( $result, $entry, $this->line_number, true );

			/**
			 * @since 1.1
			 */
			do_action( 'gravityview-importer/add-entry/error', $entry );

		} else {

			// When adding an entry, the result is the Entry ID
			$entry['id'] = ( 'update' === $action ) ? $entry['id'] : $result;

			if ( 'update' === $action ) {

				$this->Reporter->addAdded( $entry['id'], $entry, $this->line_number, true, $action );

			} else {

				$this->maybe_create_update_post( $entry );
				$this->Reporter->addAdded( $entry['id'], $entry, $this->line_number, true, $action );

			}

			$this->process_entry_after_add( $action, $entry );
		}

	}

	/**
	 * Perform actions on entries after adding.
	 *
	 * @since 1.1
	 *
	 * @filter gravityview-importer/after-add
	 * @filter gravityview-importer/after-update
	 *
	 * @param string $action Action: `update` or `add`
	 * @param array $entry Added entry array
	 *
	 * @return void
	 */
	function process_entry_after_add( $action, $entry ) {

		$this->maybe_add_entry_notes( $entry );

		/**
		 * Set Quiz results information, other entry meta set when using meta defined with `update_entry_meta_callback`
		 */
		GFFormsModel::set_entry_meta( $entry, $this->get_form() );

		$this->maybe_process_feeds( $entry );

		/**
		 * @since 1.1
		 */
		do_action( 'gravityview-importer/after-' . $action, $entry );
	}

	/**
	 * Add the entry
	 *
	 * @param $passed_entry
	 *
	 * @return WP_Error|int If success, return entry ID; If error, return WP_Error
	 */
	private function add_entry( $passed_entry ) {

		unset( $passed_entry['id'] ); // Remove cache-busting 'id' created in process_row

		$result = GFAPI::add_entry( $passed_entry );

		return $result;
	}

	/**
	 * @since 1.1
	 *
	 * @param array $passed_entry Entry to update
	 *
	 * @return WP_Error|bool If success, return true; If error, return WP_Error
	 */
	private function update_entry( $passed_entry ) {

		/**
		 * Remove default hooks associated with updating an entry
		 *
		 * @since 1.1
		 */
		$remove_hooks = apply_filters( 'gravityview-importer/remove-update-hooks', true );

		if ( $remove_hooks ) {
			remove_all_filters( 'gform_entry_pre_update' );
			remove_all_actions( 'gform_post_update_entry' );
			remove_all_actions( 'gform_get_input_value' );
		}

		$result = GFAPI::update_entry( $passed_entry );

		return $result;
	}

	/**
	 * Process feeds for the entry, if `process_feeds` setting is enabled
	 *
	 * @since 1.3
	 * 
	 * @param array $entry
	 */
	private function maybe_process_feeds( $entry ) {

		$process_feeds = gravityview_importer()->get_setting( 'process_feeds' );

		if( ! empty( $process_feeds ) ) {

			$form = $this->form;

			// Refresh the entry
			$entry = GFAPI::get_entry( $entry['id'] );

			$entry = gf_apply_filters( array( 'gform_entry_post_save', $form['id'] ), $entry, $form );

		}
	}

	/**
	 * If the entry has notes, add them. Supports JSON array of notes.
	 *
	 * @todo Allow specifying note date per note
	 * @todo Allow specifying note user per note
	 *
	 * @param $entry
	 */
	private function maybe_add_entry_notes( $entry ) {

		// No note.
		if ( empty( $entry['entry_note'] ) ) {
			return;
		}

		$notes = GV_Import_Entries_Addon::maybe_decode_json( $entry['entry_note'] );

		// Still no note.
		if ( empty( $notes ) ) {
			return;
		}

		$creator = wp_get_current_user();

		if ( ! empty( $entry['entry_note_creator'] ) ) {
			$creator = get_userdata( $entry['entry_note_creator'] );
		}

		// Allow for multiple notes in JSON format
		foreach ( (array) $notes as $note ) {
			GFFormsModel::add_note( $entry['id'], $creator->ID, $creator->user_nicename, $note, 'gvimport' );
		}

	}

	/**
	 * If there are post fields, attempt to add/update the post
	 *
	 * @param array $entry
	 */
	private function maybe_create_update_post( &$entry ) {

		// Only processed if there's a non-empty post field
		if ( $this->add_post ) {

			ob_start(); // Prevent warnings

			// Create the post!
			$post_id = GFCommon::create_post( $this->get_form(), $entry );

			/** @since 1.1 */
			$warnings = ob_get_clean(); // End prevent errors

			if ( $post_id ) {
				gravityview_importer()->log_debug( 'Post Created for Entry ' . $entry['id'] . ': #' . $post_id );
			} else {
				gravityview_importer()->log_debug( 'Post not created for Entry ' . $entry['id'] );
			}

			/** @since 1.1 */
			if ( ! empty( $warnings ) ) {
				gravityview_importer()->log_debug( 'Warnings while attempting to create Entry: ' . $warnings );
			}

			ob_end_flush();
			flush();
		}
	}

	/**
	 * @param array $passed_entry
	 *
	 * @return array
	 */
	function process_entry_before_add( $passed_entry ) {

		$entry = $passed_entry;

		$this->fill_user_login( $entry );

		$this->fill_user_agent( $entry );

		$this->fill_full_name( $entry );

		$this->process_fields( $entry );

		$this->fill_calculation_fields( $entry );

		$this->fill_option_fields( $entry );

		$this->fill_total_fields( $entry );

		$entry = array_filter( $entry, array( $this, 'is_not_empty' ) );

		return $entry;
	}

	/**
	 * Allow importing a full name by splitting it into pieces
	 *
	 * @link https://github.com/jasonpriem/HumanNameParser.php
	 */
	function fill_full_name( &$entry ) {

		$name_fields = GFCommon::get_fields_by_type( $this->get_form(), array( 'name' ) );

		foreach ( $name_fields as $field ) {

			// The full name field is here!
			if ( ! empty( $entry[ $field->id ] ) ) {

				if ( class_exists( 'HumanNameParser_Parser' ) ) {

					try {

						$Name = new HumanNameParser_Parser( $entry[ $field->id ] );

						// TODO: Switch to https://github.com/joshfraser/PHP-Name-Parser to support prefixes
						$entry[ $field->id . '.2' ] = ''; //
						$entry[ $field->id . '.3' ] = $Name->getFirst();
						$entry[ $field->id . '.4' ] = $Name->getMiddle();
						$entry[ $field->id . '.6' ] = $Name->getLast();
						$entry[ $field->id . '.8' ] = $Name->getSuffix();

						unset( $entry[ $field->id ] );

					} catch ( Exception $e ) {
						gravityview_importer()->log_error( 'Not able to parse name: ' . $e->getMessage() );
					}

				} else {
					gravityview_importer()->log_error( 'Not able to parse name; `HumanNameParser` class does not exist.' );
				}
			}
		}
	}

	/**
	 * Multi-column list fields are kind of complex.
	 *
	 * There are three options:
	 * - Multi-Column, single field mapped to everything
	 * - Multi-Column, each column mapped individually
	 * - Single Column
	 *
	 * Each option allows JSON format or `|` GF format.
	 *
	 * The value needs to be converted to an array, then serialized.
	 * The array for multi-columns needs to have the field's defined Column Name mapped to the corresponding values:
	 *
	 * [0] => array(
	 *     [Column 1 Name] => Row 1 Column 1,
	 *     [Column 2 Name] => Row 1 Column 2
	 * ),
	 * [1] => array(
	 *     [Column 1 Name] => Row 2 Column 1,
	 *     [Column 2 Name] => Row 2 Column 2
	 * )
	 *
	 * @todo Clean up this method.
	 *
	 * @param $entry
	 *
	 * @return void
	 */
	private function process_list_fields( &$entry ) {

		$list_fields = GFCommon::get_fields_by_type( $this->get_form(), array( 'list' ) );

		foreach ( $list_fields as $field ) {

			// There are multiple list columns
			if ( $field->enableColumns ) {
				$values = array();

				// Get the column key labels to map to the column values
				$choice_key_labels = wp_list_pluck( $field->choices, 'text', 'label' );

				foreach ( $field->choices as $key => $choice ) {
					$field_id = $field->id . '.' . ( $key + 1 );

					// We're mapping the fake field ID we created in GV_Import_Entries_Addon::get_field_map_choices()
					if ( ! empty( $entry[ $field_id ] ) ) {

						// Decode to JSON array or use backup if that fails
						$column_values = GV_Import_Entries_Addon::maybe_decode_json( $entry[ $field_id ] );

						// JSON
						if ( is_array( $column_values ) ) {
							$column_values = array_values( $column_values );
						} else {
							$column_values = array_values( explode( '|', $entry[ $field_id ] ) );
						}

						// If it's not an empty array
						if ( array_filter( $column_values, array( $this, 'is_not_empty' ) ) ) {

							// We need to add the column names to the values
							$values[ $key ] = array_combine( $choice_key_labels, $column_values );

						}

						// Get rid of the fake field ID
						unset( $entry[ $field_id ] );

					} elseif ( ! empty( $entry[ $field->id ] ) ) {

						// Decode to JSON array or use backup if that fails
						$column_values = GV_Import_Entries_Addon::maybe_decode_json( $entry[ $field->id ] );

						if ( is_array( $column_values ) ) {

							if ( array_filter( $column_values, array( $this, 'is_not_empty' ) ) ) {
								// We need to add the column names to the values
								foreach ( $column_values as $key => $value ) {
									$values[ $key ] = array_combine( $choice_key_labels, $value );
								}
							}
						}
					}

				}

				// Because we're mapping a multi-column list, not a single list, we don't want to set an empty entry parameter.
				// Only set the field ID if there are values, otherwise the # of items being mapped will be different than
				// the size of the $entry array
				if ( ! empty( $values ) ) {
					$entry[ $field->id ] = serialize( $values );
				}

			} // Single list column
			else {
				if ( ! empty( $entry[ $field->id ] ) ) {

					// Decode to JSON array or use backup if that fails
					$column_values = GV_Import_Entries_Addon::maybe_decode_json( $entry[ $field->id ] );

					// JSON
					if ( is_array( $column_values ) ) {
						$column_values = array_values( $column_values );
					} else {
						$column_values = array_values( explode( '|', $entry[ $field->id ] ) );
					}

					$entry[ $field->id ] = empty( $column_values ) ? '' : serialize( $column_values );
				} else {
					if ( isset( $entry[ $field->id ] ) ) {
						$entry[ $field->id ] = '';
					} else {
						unset( $entry[ $field->id ] );
					}
				}
			}
		}

	}

	/**
	 * Run all field values through the prepare_field_values_for_insert() method
	 *
	 * @see prepare_field_values_for_insert
	 *
	 * @param $entry
	 */
	private function process_fields( &$entry ) {

		$form = $this->get_form();

		foreach ( $form['fields'] as $field ) {

			/* @var GF_Field $field */
			if ( in_array( $field->type, array( 'html', 'page', 'section' ) ) ) {
				continue;
			}

			$use_default_values = $this->use_default_values();
			$inputs             = $field->get_entry_inputs();
			$field_id           = (string) $field->id;
			$field_value        = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';

			// We can't use the $entry[ $field_id ] below because we need to modify multiple $entry keys
			if ( 'checkbox' === $field->type && floatval( $field_id ) === floor( $field_id ) ) {
				if ( ! empty( $field_value ) ) {
					$this->prepare_field_checkbox( $entry, $field, $field_value );
				}
			} else if ( is_array( $inputs ) ) {
				foreach ( $inputs as $input ) {
					$input_id = (string) $input['id'];

					$input_value = isset( $entry[ $input_id ] ) ? $entry[ $input_id ] : '';
					if ( $input_value ) {
						$entry[ $input_id ] = $this->prepare_field_values_for_insert( $field, $input_value, $input_id, $entry );
					} elseif ( $use_default_values ) {
						$default_input_value = $this->get_field_default_value( $field, $input_id );
						if ( $default_input_value !== '' ) {
							$label = sprintf( '%s (%s)', $input['label'], $field->label );
							$this->Reporter->addWarning( new WP_Error( 'default_value', sprintf( __( 'Setting default value for %s: %s', 'gravityview-importer' ), $label, '<code>' . $default_input_value . '</code>' ) ) );
						}
						$entry[ $input_id ] = $default_input_value;
					}
				}
			} else {

				if ( $use_default_values && $field_value === '' ) {
					$default_field_value = $this->get_field_default_value( $field );
					if ( $default_field_value !== '' ) {
						$field_value = $default_field_value;
						$this->Reporter->addWarning( new WP_Error( 'default_value', sprintf( __( 'Setting default value for %s: %s', 'gravityview-importer' ), $field->label, '<code>' . $default_field_value . '</code>' ) ) );
					}
				}

				$entry[ $field_id ] = $this->prepare_field_values_for_insert( $field, $field_value, $field_id, $entry );
			}
		}
	}

	/**
	 * Get the default value for a field. If checkbox or radio, uses isSelected value.
	 *
	 * @since 1.0.2
	 *
	 * @param GF_Field $field
	 *
	 * @return string
	 */
	private function get_field_default_value( $field, $input_id = '' ) {

		$default_value = $field->defaultValue;

		switch ( $field->type ) {
			case 'checkbox':
				$selected = wp_list_filter( (array) $field->choices, array( 'isSelected' => true ) );

				// Multiple inputs
				if ( ! empty( $input_id ) ) {
					// Get the ID from the full input string ({$field}.{input})
					list( $field_id, $id ) = explode( '.', $input_id );

					// Reduce by one to get 0-key based
					$key = $id - 1;

					// If the filtered choice list exists with the input ID key, that's the default.
					$default_value = isset( $selected[ $key ] ) ? $selected[ $key ]['value'] : '';
				}
				break;
			case 'select':
			case 'multiselect':
			case 'radio':
				if ( empty( $default_value ) ) {

					$selected = wp_list_filter( (array) $field->choices, array( 'isSelected' => true ) );

					if ( ! empty( $selected ) ) {
						$selected      = array_pop( $selected );
						$default_value = $selected['value'];
					}
				}
				break;
			case 'name':
				if ( empty( $default_value ) ) {
					foreach ( (array) $field->inputs as $input ) {
						if ( (string) $input_id === (string) rgar( $input, 'id' ) ) {

							$default_value = rgar( $input, 'defaultValue' );

							// If the defaults are a dropdown, then get the default value from the dropdown
							if ( empty( $default_value ) && ! empty( $input['choices'] ) ) {
								$selected      = wp_list_filter( $input['choices'], array( 'isSelected' => true ) );
								$selected      = wp_list_pluck( $selected, 'value', 'reset' );
								$default_value = empty( $selected ) ? '' : $selected;
							}
						}
					}
				}
				break;
		}

		return $default_value;
	}

	/**
	 * Checkboxes are stored in the database with a separate entry in `lead_detail` for each checkbox.
	 *
	 * We need to map multiple values stored in field ID to single values in entry ID:
	 * Instead of `$entry['10'] = "Apple,Orange";` becomes `$entry['10.1'] = "Apple"; $entry['10.2'] = "Orange";`
	 *
	 * @param array &$entry Entry to be added/updated
	 * @param GF_Field_Checkbox|GF_Field_MultiSelect $field
	 * @param string $field_value string of files (JSON array or normal)
	 *
	 * @since 1.0.8
	 */
	private function prepare_field_checkbox( &$entry, $field, $field_value ) {

		$values = explode( ',', $field_value );

		foreach ( $values as $value ) {

			$trimmed_value = trim( $value );

			foreach ( (array) $field->inputs as $i => $input ) {
				$choice = $field->choices[ $i ];

				if ( RGFormsModel::choice_value_match( $field, $choice, $value ) ) {
					$entry[ $input['id'] ] = $value;
				} elseif ( RGFormsModel::choice_value_match( $field, $choice, $trimmed_value ) ) {
					$entry[ $input['id'] ] = $trimmed_value;
				}
			}
		}

		// Checkboxes don't have any base field input value
		unset( $entry[ (string) $field->id ] );

	}

	/**
	 * @param GF_Field $field
	 * @param string $field_value
	 * @param array $entry
	 *
	 * @return mixed
	 */
	private function prepare_field_values_for_insert( &$field, $field_value, $input_id, $entry ) {

		switch ( $field->type ) {

			case 'post_category':
			case 'post_title':
			case 'post_content':
			case 'post_excerpt':
			case 'post_custom_field':
				if ( ! empty( $field_value ) ) {
					$this->add_post = true;
				}
				break;

			case 'quiz':
			case 'poll':
				$field_value = $this->prepare_field_quiz_poll( $field, $field_value );
				break;

			/**
			 * Convert tags from JSON array to CSV array
			 */
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'post_tags':
				if ( ! empty( $field_value ) ) {
					$this->add_post = true;
				}
			// break; intentionally left out
			case 'multiselect':

				// First, attempt JSON
				$field_value = GV_Import_Entries_Addon::maybe_decode_json( $field_value );

				// Then simple CSV
				if ( ! is_array( $field_value ) ) {
					$field_value = explode( ',', $field_value );
				}

				if ( is_array( $field_value ) ) {
					$field_value = implode( ',', $field_value );
				}
				break;

			/**
			 * Convert JSON to `|:|` GF format
			 */
			case 'post_image':
				if ( ! empty( $field_value ) ) {
					$this->add_post = true;
				}
				$field_value = $this->prepare_field_post_image( $field, $field_value );
				break;

			case 'fileupload':
				$field_value = $this->prepare_field_fileupload( $field, $field_value );
				break;

			/** @since 1.1 */
			case 'date':
				$field_value = $this->prepare_field_date( $field, $field_value );
				break;

			case 'time':
				// Gravity Forms gets caught up with `if ( is_array( $field->inputs ) ) {` during add_entry()
				// since the time field has inputs, but it's saved without inputs
				unset( $field->inputs );
				break;
		}

		if ( $this->use_default_values() && empty( $field_value ) ) {
			$default_value = $this->get_field_default_value( $field, $input_id );
			$field_value   = GFCommon::replace_variables_prepopulate( $default_value, false, $entry );
		}

		return $field_value;
	}


	/**
	 * Match text value of quiz or poll against the choices available. If matched, return the choice value.
	 *
	 * The choice value is a random string like `gpoll158bda15a` or `gquiz24c7441b2`.
	 *
	 * @since 1.2
	 *
	 * @param GF_Field_Radio $field
	 * @param string $field_value Text value of quiz or poll
	 *
	 * @return string|null Null if match not made (this shouldn't happen, since validation already occurred)
	 */
	private function prepare_field_quiz_poll( $field, $field_value ) {

		foreach ( (array) $field->choices as $i => $choice ) {
			if ( in_array( $field_value, array( $choice['text'], $choice['value'] ) ) ) {
				return $choice['value'];
			}
		}

		$error = new WP_Error( 'missing_quiz_value', __( 'Quiz answer could not be found.', 'gravityview-importer' ), $this->line_number );

		$this->Reporter->addWarning( $error, $this->line_number );

		return null;
	}

	/**
	 * @param GF_Field_FileUpload $field
	 * @param string $field_value string of files (JSON array or normal)
	 */
	private function prepare_field_post_image( $field, $field_value ) {

		return $this->get_post_image_format_from_value( $field_value );

	}

	private function get_post_image_format_from_value( $value ) {

		$return = $value;

		// Preferred format, defined by our plugin (JSON array)
		$files_array = GV_Import_Entries_Addon::maybe_decode_json( $value );

		// JSON
		if ( is_array( $files_array ) ) {

			// Form the right array order, with all slots filled
			$file = array(
				'file'        => rgget( 'file', $files_array ),
				'title'       => rgget( 'title', $files_array ),
				'caption'     => rgget( 'caption', $files_array ),
				'description' => rgget( 'description', $files_array ),
			);

			$return = implode( '|:|', $file );

		}

		return $return;
	}

	/**
	 * Convert the field value into YYYY-MM-DD, which is how GF stores all dates in DB, regardless of format
	 *
	 * @since 1.1
	 *
	 * @param GF_Field_Date $field
	 * @param string $field_value Date value
	 *
	 * @return array
	 */
	private function prepare_field_date( $field, $field_value ) {

		// Sometimes GF doesn't save the date format, so we use the GF default
		$date_format = empty( $field->dateFormat ) ? 'mdy' : $field->dateFormat;

		$date = GFCommon::parse_date( $field_value, $date_format );

		// Didn't parse properly
		if ( empty( $date ) ) {
			return $field_value;
		}

		// GF stores dates in YYYY-MM-DD
		$return = sprintf( '%s-%s-%s', $date['year'], $date['month'], $date['day'] );

		return $return;
	}

	/**
	 * @param GF_Field_FileUpload $field
	 * @param string $field_value string of files (JSON array or normal)
	 */
	private function prepare_field_fileupload( $field, $field_value ) {

		if ( empty( $field_value ) ) {
			return $field_value;
		}

		// Convert string values to array
		if ( $field->multipleFiles ) {

			// Preferred format, defined by our plugin (JSON array)
			$files_array = GV_Import_Entries_Addon::maybe_decode_json( $field_value );

			// Gravity Forms export format (CSV)
			if ( ! is_array( $files_array ) ) {
				$files_array = explode( ',', $field_value );
				$files_array = array_map( 'trim', $files_array );
				$files_array = array_map( 'rtrim', $files_array );
			}

		} else {
			$files_array = array( $field_value );
		}

		// Upload files, if enabled
		$files_array = $this->maybe_upload_files( $files_array, $field );

		if ( $field->multipleFiles ) {
			// Convert back to JSON
			$field_value = json_encode( $files_array );
		} else {
			// Just one element. Convert to string.
			$field_value = array_shift( $files_array );
		}

		return $field_value;
	}

	/**
	 * Upload files for the field, if enabled in the settings
	 *
	 * If possible:
	 *   - Convert relative paths to absolute, if file exists in the site directory
	 *   - Move to Gravity Forms uploads directory
	 *   - Otherwise, use original URL link
	 *
	 * @return array Files array
	 */
	private function maybe_upload_files( $files_array, $field ) {

		$upload_files = gravityview_importer()->get_setting( 'upload_files' );

		if ( $upload_files === 'yes' ) {

			foreach ( (array) $files_array as $key => $file ) {

				if ( empty( $file ) ) {
					continue;
				}

				// This can take a while.
				gv_importer_reset_time_limits();

				$file = str_replace( WP_CONTENT_URL, '', $file );

				if ( file_exists( $file ) ) {
					$file = site_url( $file, 'relative' );
				} else {

					$target = GFFormsModel::get_file_upload_path( $this->get_form_id(), basename( $file ) );

					$tmp_name = download_url( $file );

					/** @var WP_Error $tmp_name */
					if ( is_wp_error( $tmp_name ) ) {

						$error_field = array(
							'error' => sprintf( __( 'File upload failed: %s', 'gravityview-importer' ), $tmp_name->get_error_message() ),
							'field' => sprintf( __( 'Field: %s', 'gravityview-importer' ), $field->label ),
						);

						$error = new WP_Error( 'upload_failed', sprintf( '%s (%s)<br />%s', $error_field['error'], $error_field['field'], '<a href="' . esc_url( $file ) . '" target="_blank">' . $file . '</a>' ) );
						$this->Reporter->addWarning( $error, $this->line_number );
						continue;
					}

					if ( rename( $tmp_name, $target['path'] ) ) {

						GFCommon::log_debug( 'GFFormsModel::upload_file(): Setting permissions on ' . $target['path'] );
						GFFormsModel::set_permissions( $target['path'] );

						$file = $target['url'];
					} else {
						$this->Reporter->addWarning( new WP_Error( 'file_upload_error', sprintf( 'File Upload failed for %s; the temporary file could not be copied to %s.', $tmp_name, $target['path'] ) ), $this->line_number );
						continue;
					}
				}

				$files_array[ $key ] = $file;
			}
		}

		return (array) $files_array;
	}

	/**
	 * Set the individual inputs for each choice in a field
	 *
	 * @since 1.2
	 *
	 * @param array $entry
	 * @param GF_Field_Option $field
	 * @param string $field_value Existing value
	 *
	 * @return null|string
	 */
	private function fill_option_field_option( $entry, $field, $field_value ) {

		// Instead of returning empty string, we return a | so that GFCommon::get_option_info() doesn't freak out during calculation of totals
		$price = null;
		$option_name = null;

		// Replace escaped commas (\,) with placeholder so we can explode using commas.
		$field_value = str_replace('\,', '[comma]', $field_value );
		$exploded_options = explode( ',', $field_value );

		foreach ( $exploded_options as $exploded_option ) {

			// Restore the original commas
			$exploded_option = str_replace( '[comma]', ',', $exploded_option );

			foreach ( $field->choices as $index => $choice ) {

				list( $pipe_value, $pipe_price ) = array_pad( explode( '|', $exploded_option ), 2, null );

				if( ! is_null( $pipe_price ) && ! is_null( $pipe_value ) ) {
					$compare_value = $pipe_value;
					$option_price = $pipe_price;
				} else {
					$compare_value = $exploded_option;
					$option_price = $choice['price'];
				}

				$key = $index + 1;
				$input_id = sprintf( '%d.%d', $field->id, $key );

				$entry = $this->set_option_field_choice( $entry, $input_id, $choice, $compare_value, $option_price );
			}
		}

		return $entry;
	}

	/**
	 * Check a value against the field's choices and see if it exists. If so, set `$entry[$input_id]` to the correct format (value|float)
	 *
	 * @since 1.2
	 *
	 * @param array $entry
	 * @param string $input_id The ID for each input in a field to check against and/or set (1.1, 1.2, 1.3, etc)
	 * @param array $choice Array of values from the Option field's chocies ($field->choices)
	 * @param null $value If set, use this value to compare against the choice value and label, not `$entry[ $input_id ]`
	 * @param null $price If set, use this value for the price, not the choice price
	 *
	 * @return array Modified entry. If `$entry[ $input_id ]` doesn't exist, unset it. If it does and there's a value match, set the value of `$entry[ $input_id ]`. Otherwise, return original `$entry`.
	 */
	function set_option_field_choice( $entry, $input_id, $choice, $value = null, $price = null ) {

		if( is_null( $value ) && isset( $entry[ $input_id ] ) ) {
			$value = $entry[ $input_id ];
		}

		if( is_null( $value ) ) {
			unset( $entry[ $input_id ] );
		} elseif ( $choice['value'] === $value || $choice['text'] === $value ) {
			$option_name  = $choice['value'];
			$option_price = $price ? $price : $choice['price'];
			$entry[ $input_id ] = sprintf( '%s|%s', $option_name, GFCommon::to_number( $option_price ) );
		}

		return $entry;
	}

	/**
	 * Upload a set of product options or a single product option.
	 *
	 * If already formatted using "Option Name|float", verifies the formatting
	 *
	 * If using the field ID, not each input value, use the following formats:
	 *
	 * 1. Use a CSV array of pipe-separated Value/Price pairs: `First Option VALUE|1.11,Third Option VALUE|3.33`
	 * 2. Use a CSV array of option values: `First Option VALUE,Third Option VALUE`
	 * 3. Use a CSV array of option labels: `First Option,Third Option`
	 *
	 * You can define prices that aren't used in the form.
	 *
	 * ---------------------
	 *
	 * If you're instead using the default Gravity Forms export format, which is one column per Product Option, it should be formatted as the value of the option:
	 * - Column "First Option" in the CSV would have the value of `First Option VALUE`
	 * - Column "Second Option" in the CSV would have the value of `Second Option VALUE`
	 * - Column "Third Option" in the CSV would have the value of `Third Option VALUE`
	 *
	 * This will then automatically fetch the price from the form.
	 *
	 * You can override the price of a product option by adding a pipe bar and the price, like so:
	 *
	 * - If you want to set the price to $9.85 for the "First Option" column, add the price after a `|` symbol: `First Option VALUE|9.85`
	 *
	 * Note: All prices are stripped of currency formats, regardless of how it was formatted originally.
	 *
	 * @since 1.2
	 *
	 * @param array $entry Entry array, passed by reference.
	 */
	private function fill_option_fields( &$entry ) {

		// Process total fields
		if ( ! empty( $this->option_fields ) ) {

			/** @var GF_Field_Option $total_field */
			foreach ( $this->option_fields as $field ) {

				// The field itself, not an input
				if( isset( $entry["{$field->id}"] ) && '' !== $entry["{$field->id}"] ) {

					$entry = $this->fill_option_field_option( $entry, $field, $entry["{$field->id}"] );
					unset( $entry["{$field->id}"] );
				} else {

					foreach ( $field->choices as $index => $choice ) {

						$key = $index + 1;
						$input_id = sprintf( '%d.%d', $field->id, $key );

						$entry = $this->set_option_field_choice( $entry, $input_id, $choice );
					}
				}
			}
		}
	}

	/**
	 * Fill User ID from the User Login if User ID isn't set
	 *
	 * @param array $entry
	 */
	function fill_user_login( &$entry ) {

		// Don't bother processing if there's no user mapping
		if ( empty( $entry['user_id'] ) && empty( $entry['user_login'] ) ) {
			return;
		}

		$user_data  = false;
		$user_type  = NULL;
		$user_value = NULL;

		if ( ! empty( $entry['user_id'] ) ) {

			$user_value = $entry['user_id'];
			$user_type  = _x( 'ID', 'the type of method used to search for a User. ID or Login', 'gravityview-importer' );
			$user_data  = get_user_by( 'id', $entry['user_id'] );

		} else if ( ! empty( $entry['user_login'] ) ) {

			$user_value = $entry['user_login'];
			$user_type  = _x( 'username', 'the type of method used to search for a User. ID or username', 'gravityview-importer' );
			$user_data  = get_user_by( 'login', $entry['user_login'] );

		}

		// User wasn't found.
		if ( false === $user_data ) {

			$this->Reporter->addError( new WP_Error( 'invalid_user', sprintf( __( 'A user cannot be found with the following %s: %s', 'gravityview-importer' ), $user_type, $user_value ) ) );

		} else {

			$entry['created_by'] = $user_data->ID;

		}

		// Don't keep passing the values, since they aren't used.
		unset( $entry['user_id'], $entry['user_login'] );
	}

	/**
	 * Set the user agent to an import message
	 *
	 * @param $entry
	 */
	function fill_user_agent( &$entry ) {

		if ( empty( $entry['user_agent'] ) || $entry['user_agent'] === 'API' && ! empty( $this->user_agent ) ) {
			$entry['user_agent'] = ( sprintf( $this->user_agent, $this->get_previous_entry_id() ) );
		}
	}

	/**
	 * Run through entry value calculations if the import had any calculation fields
	 *
	 * Yes, we could have used a different way of adding entries and then GF would have handled this for us,
	 * but the problem with that is GF hard-codes some of the entry parameters, and we want access to all of them.
	 *
	 * Furthermore, that would loop through and run multiple DB queries per input, which is much worse for performance.
	 *
	 * @param array $entry GF entry array
	 */
	function fill_calculation_fields( &$entry ) {

		// Process calculation fields
		if ( ! empty( $this->calculation_fields ) ) {
			foreach ( $this->calculation_fields as $calculation_field ) {

				// If the formula is not empty, then use the passed formula.
				$use_custom_formula = ! empty( $entry["{$calculation_field->id}"] );

				if ( $use_custom_formula ) {
					add_filter( 'gform_calculation_formula', array( $this, 'calculation_formula_filter' ), 10, 4 );
				}

				$entry["{$calculation_field->id}"] = GFCommon::calculate( $calculation_field, $this->get_form(), $entry );

				if ( $use_custom_formula ) {
					remove_filter( 'gform_calculation_formula', array( $this, 'calculation_formula_filter' ), 10 );
				}
			}
		}

	}

	/**
	 * Run through any Total fields (GF_Field_Total) and update the value
	 *
	 * @param array &$entry GF entry array
	 * 
	 * @return void
	 */
	function fill_total_fields( &$entry ) {

		/**
		 * @filter `gravityview-import/recalculate-totals` Whether or not to re-calculate totals for an entry
		 * @param boolean $recalculate_totals False: Use supplied value; True: Go through the entry and re-calculate the entry total based on products and options.
		 */
		$recalculate_totals = apply_filters( 'gravityview-import/recalculate-totals', true, $entry );

		if( ! $recalculate_totals ) {
			return;
		}

		// Remove the entry ID temporarily so that there's not a database warning thrown by Gravity Forms when setting `gform_product_info__` cache meta
		$entry_id = $entry['id'];
		$entry['id'] = NULL;

		// Process total fields
		if ( ! empty( $this->total_fields ) ) {
			/** @var GF_Field_Total $total_field */
			foreach ( $this->total_fields as $total_field ) {
				$entry["{$total_field->id}"] = GFFormsModel::get_prepared_input_value( $this->get_form(), $total_field, $entry, $total_field->id );
			}
		}

		// Add it back in
		$entry['id'] = $entry_id;
	}

	/**
	 * Override the Gravity Forms form calculation setting with own formula
	 *
	 * @param string $field_calculation_formula Existing setting, defined in form
	 * @param GF_Field $field
	 * @param array $form GF Form array
	 * @param array $entry GF Entry array
	 *
	 * @return string New formula, grabbed from the CSV
	 */
	public function calculation_formula_filter( $field_calculation_formula, $field, $form = array(), $entry = array() ) {

		$new_formula = isset( $entry["{$field->id}"] ) ? $entry["{$field->id}"] : $field_calculation_formula;

		return $new_formula;
	}

	/**
	 * Validate entry values
	 *
	 * @param $entry
	 *
	 * @return bool False: failed validation
	 */
	private function validate_entry( $entry ) {

		foreach ( $entry as $field_id => $field_value ) {

			$field_value = trim( $field_value );

			// Get the field type for the field ID
			$field = GFFormsModel::get_field( $this->form, $field_id );

			if ( $field ) {

				/**
				 * @hack Gravity Forms checks raw values in some field's validate() methods
				 */
				$_POST[ 'input_' . $field_id ] = $field_value;

				$this->modify_field_settings_pre_validation( $field );

				$this->validate_field( $field, $field_value, $field_id, $entry );

				if ( $field->failed_validation ) {

					$label   = empty( $field->label ) ? sprintf( 'ID #%d', $field->id ) : $field->label;
					$message = sprintf( 'Row #%d - Invalid value for field "%s". Message: %s', $this->line_number, $label, $field->validation_message );

					if ( ! empty( $field_value ) ) {
						$field_value = maybe_unserialize( $field_value );
						// It gets complicated with serialized arrays, etc. Let's just show text values.
						if ( ! is_array( $field_value ) ) {
							$message .= '<br /> ' . sprintf( 'Invalid value: %s', '<code>' . $field_value . '</code>' );
						}
					}

					$this->Reporter->addError( new WP_Error( 'field_import_error', $message ), $this->line_number );

					return false;
				}

				unset( $_POST[ 'input_' . $field_id ] );

			} else {

				return $this->validate_custom_field_format( $field_id, $field_value );

			}
		}

		return true;
	}

	/**
	 * Check whether a value is not empty
	 *
	 * @since 1.0.8
	 *
	 * @param string $value
	 *
	 * @return bool True: not empty. False: empty.
	 */
	function is_not_empty( $value = '' ) {
		return ! $this->is_empty( $value );
	}

	/**
	 * @since 1.0.8
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	function is_empty( $value = '' ) {

		if ( ! isset( $value ) ) {
			return true;
		}

		// If string,
		if ( is_string( $value ) ) {
			$value = trim( $value );
			$value = function_exists( 'rtrim' ) ? rtrim( $value ) : $value;

			return $value === '';
		}
		
		return empty( $value );
	}

	function validate_custom_field_format( $field_id, $field_value ) {

		$valid = true;

		switch ( $field_id ) {
			case 'entry_id':
				$valid = $this->validate_entry_id( $field_value );
				break;
			case 'post_id':
				$valid = $this->validate_post_id( $field_value );
				break;
			case 'entry_note_creator':
				$valid = $this->validate_user( $field_value, __( 'Entry Note Creator', 'gravityview-importer' ) );
				break;
			case 'post_author':
				$valid = $this->validate_user( $field_value, __( 'Post Author', 'gravityview-importer' ) );
				break;
			case 'post_status':
				$valid = $this->validate_post_status( $field_value );
				break;
		}

		return $valid;
	}

	/**
	 * Validate whether an entry ID exists
	 *
	 * @param $entry_id
	 *
	 * @return bool True: Entry exists, able to be updated. False: entry doesn't exist; can't be updated.
	 */
	function validate_entry_id( $entry_id ) {

		$valid = false;

		// Make sure they've granted explicit authorization to overwrite post data
		$skip_empty_overwrite_entry = gravityview_importer()->get_setting( 'skip_empty_overwrite_entry' );

		if ( empty( $entry_id ) ) {

			if ( $skip_empty_overwrite_entry ) {

				$this->Reporter->addError( new WP_Error( 'entry_not_exists', sprintf( __( 'Row #%d - The Entry ID was empty. Based on import settings, this row has been skipped.', 'gravityview-importer' ), $this->line_number ) ) );

			} else {
				$valid = true;
			}

		} else {

			$entry_or_error = GFAPI::get_entry( $entry_id );

			if ( is_wp_error( $entry_or_error ) ) {
				$this->Reporter->addError( new WP_Error( 'entry_not_exists', sprintf( __( 'Row #%d - Entry ID #%s does not exist', 'gravityview-importer' ), $this->line_number, $entry_id ) ), $this->line_number );
			} else {
				$valid = true;
			}
		}

		return $valid;
	}

	/**
	 * If passing a post ID, update the post instead of creating a new one. Checks to make sure the post exists.
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	function validate_post_id( $post_id ) {

		if ( empty( $post_id ) ) {
			return true;
		}

		// Make sure they've granted explicit authorization to overwrite post data
		$overwrite_post_data = gravityview_importer()->get_setting( 'overwrite_post_data' );

		if ( empty( $overwrite_post_data ) || $overwrite_post_data === 'no' ) {
			$error = new WP_Error( 'overwrite_post_data', __( 'A Post ID was specified to update, but the "Overwrite Post Data" setting is not enabled. It must be checked to overwrite existing post data.', 'gravityview-importer' ), array( 'row' => $this->line_number ) );
		} // Post ID does NOT EXIST
		else if ( ! is_numeric( $post_id ) ) {
			$error = new WP_Error( 'post_id_missing', sprintf( __( 'Post ID must be a number: %s', 'gravityview-importer' ), $post_id ), array( 'row' => $this->line_number ) );
		} // If the status is false, doesn't exist
		else if ( false === get_post_status( $post_id ) ) {
			$error = new WP_Error( 'post_id_missing', sprintf( __( 'Post ID #%d could not be updated; it does not exist. The entry will not be imported.', 'gravityview-importer' ), $post_id ) );
		} // It does exist.
		else {
			return true;
		}

		$this->Reporter->addError( $error, $this->line_number );

		return false;
	}

	/**
	 * Check whether the post status that was defined is valid (or empty, which is valid). If not, add warning.
	 *
	 * @param string $status Post status (`publish`, `draft`, etc)
	 *
	 * @return boolean
	 */
	function validate_user( $user, $type = 'Post Author' ) {

		if ( get_userdata( $user ) ) {
			return true;
		}

		$this->Reporter->addWarning( new WP_Error( 'field_post_author', sprintf( __( '%s cannot be set: this User ID does not exist: "%d"', 'gravityview-importer' ), $type, $user ) ), $this->line_number );

		return false;
	}

	/**
	 * Check whether the post status that was defined is valid (or empty, which is valid). If not, add warning.
	 *
	 * @param string $status Post status (`publish`, `draft`, etc)
	 *
	 * @return boolean
	 */
	function validate_post_status( $status ) {

		if ( empty( $status ) || in_array( $status, get_post_stati() ) ) {
			return true;
		}

		$this->Reporter->addWarning( new WP_Error( 'field_post_status', sprintf( __( 'An invalid post status was defined: "%s"', 'gravityview-importer' ), $status ) ), $this->line_number );

		return false;
	}

	/**
	 * Whether to use default values
	 *
	 * @since 1.0.2
	 * @return boolean
	 */
	private function use_default_values() {

		$use_default_values = gravityview_importer()->get_setting( 'use_default_value' );

		$use_default_values = apply_filters( 'gravityview-importer/use-default-value', ! empty( $use_default_values ) );

		return (bool) $use_default_values;
	}

	/**
	 * Whether to ignore required fields
	 *
	 * @since 1.0.2
	 * @return boolean
	 */
	private function ignore_required() {
		return gravityview_importer()->get_setting( 'ignore_required' );
	}

	/**
	 * Check whether a field is required and the value is empty
	 *
	 * @param GF_Field $field
	 * @param mixed $field_value
	 */
	function check_required( $entry ) {

		$passed = true;

		$ignore_required = $this->ignore_required();

		$use_default_values = $this->use_default_values();

		if ( ! $ignore_required ) {

			/** @var GF_Field $field */
			foreach ( $this->form['fields'] as $field ) {

				if ( empty( $field->isRequired ) ) {
					continue;
				}

				// Credit card isn't required during import.
				if( 'creditcard' === $field->type ) {
					continue;
				}

				$default_value = '';
				$labels        = array();

				if ( is_array( $field->inputs ) ) {

					$is_empty = false;

					foreach ( $field->inputs as $input ) {
						$input_id = (string) $input['id'];

						// If is hidden or has default value, it shouldn't trigger required.
						if ( rgget( 'isHidden', $input ) ) {
							continue;
						}

						$default_value = $this->get_field_default_value( $field, $input_id );

						if ( ! isset( $entry[ $input_id ] ) || GFCommon::is_empty_array( $entry[ $input_id ] ) ) {
							$is_empty = true;
							$labels[] = sprintf( '%s (%s)', $input['label'], $field->label );
						}

					}

				} else {
					$value         = isset( $entry[ (string) $field->id ] ) ? $entry[ (string) $field->id ] : '';
					$default_value = $this->get_field_default_value( $field );
					$is_empty      = ( strlen( trim( $value ) ) <= 0 ) || ( $field->type == 'post_category' && $value < 0 );
					$labels[]      = $field->label;
				}

				if ( $use_default_values && ! $this->is_empty( $default_value ) ) {
					continue;
				}

				if ( $is_empty ) {
					if ( count( $labels ) > 1 ) {
						$what_fields = sprintf( __( 'A required field was empty: %s', 'gravityview-importer' ), $field->label );
					} else {
						$what_fields = sprintf( __( 'A required field was empty: %s', 'gravityview-importer' ), implode( ', ', $labels ) );
					}

					$message = sprintf( __( 'Row %d was not imported. %s', 'gravityview-importer' ), $this->line_number, $what_fields );
					$this->Reporter->addError( new WP_Error( 'field_required_error', $message ), $entry, $this->line_number, true );
					$passed = false;
				}

			} // End foreach $field

		}


		return $passed;
	}

	/**
	 * @param GF_Field $field
	 * @param $field_value
	 * @param int|float $field_id Field ID
	 *
	 * @return mixed
	 */
	private function validate_field( $field, $field_value, $field_id, $entry ) {

		switch ( $field->type ) {

			// Don't validate these special fields; they get a pass.
			case 'calculation':
			case 'product':
			case 'creditcard':
			case 'coupon':
				break;

			case 'option':
				/** @var GF_Field_Option $field */
				$this->validate_field_option( $field, $field_value );
				break;

			// GF had an error when empty $field_value
			case 'time':
				if ( ! $this->is_empty( $field_value ) ) {
					/** @var GF_Field_Time $field */
					$field->validate( $field_value, $this->form );
				}
				break;

			case 'quiz':
			case 'poll':
				$valid = $this->validate_field_choices( $field, $field_value, true );
				if( ! $valid ) {
					$field->failed_validation = true;
					$field->validation_message = sprintf( __( 'Invalid choice value: %s', 'gravityview-importer' ), $field_value );
				}
				break;

			case 'list':
				/** @var GF_Field_List $field */
				$this->validate_field_list( $field, $field_value );
				break;

			case 'post_image':
				$this->validate_field_post_image( $field, $field_value );
				break;

			case 'fileupload':
				$this->validate_field_fileupload( $field, $field_value );
				break;

			case 'radio':
				$this->validate_field_radio( $field, $field_value );
				break;

			case 'name':
			case 'address':
				$this->validate_field_with_inputs( $field, $field_value, $field_id, $entry );
				break;

			case 'phone':
				$field->validate( $field_value, $this->form );

				// The message is otherwise generic.
				if ( ! $this->is_empty( $field->failed_validation ) ) {
					if ( $field->phoneFormat === 'standard' ) {
						$field->validation_message = __( 'Expected field format: (###) ###-####. Try updating field to use "International" Phone Format.', 'gravityview-importer' );
					}
				}
				break;
			default:
				$field->validate( $field_value, $this->form );
		}

	}

	/**
	 * Handle validating fields with inputs
	 *
	 * @since 1.0.2
	 *
	 * @param GF_Field $field
	 * @param $field_value
	 * @param $field_id
	 *
	 * @return array|bool
	 */
	function validate_field_with_inputs( &$field, $field_value, $field_id, $entry ) {

		$ignore_required = gravityview_importer()->get_setting( 'ignore_required' );

		// Not required
		if ( empty( $field->isRequired ) || $ignore_required ) {
			return false;
		}

		$errors = array();

		$use_default_values = $this->use_default_values();

		// Full field mapping?
		if ( floor( $field_id ) === floatval( $field_id ) ) {

			if ( $this->is_empty( $field_value ) ) {
				$errors[] = sprintf( __( 'The %s field (ID #%d) was empty, and is required.', 'gravityview-importer' ), $field->label, $field_id );
			}

		} else {

			/** @var array $inputs */
			$inputs = $field->get_entry_inputs();

			// Go through and check if any of the inputs are hidden. If so, don't validate them.
			foreach ( $inputs as $key => $input ) {

				// If the input is hidden, or if there's a default value, not invalid
				if ( ! empty( $input['isHidden'] ) || ( $use_default_values && $this->get_field_default_value( $field, $input['id'] ) ) ) {
					unset( $inputs[ $key ] );
				}
			}

			// If all inputs have default values, then return
			if ( ! $this->is_empty( $inputs ) ) {

				// If there are inputs
				foreach ( $inputs as $input ) {
					$input_id = (string) $input['id'];
					// If the ID is the same, check whether empty
					if ( $this->is_empty( $entry[ $input_id ] ) ) {
						$errors[] = sprintf( __( 'the "%s" %s field was empty, and is required', 'gravityview-importer' ), $input['label'], $field->type );
					}
				}
			}

			if ( $errors ) {
				$field->failed_validation  = true;
				$field->validation_message = implode( '; ', $errors ) . '.';
			}

			return empty( $errors ) ? false : $errors;

		} // End not full field

		return false;
	}

	/**
	 * Check if a field value exists in field choices. If $label_is_valid, also compare against $choice text
	 *
	 * @since 1.2
	 * 
	 * @param GF_Field $field
	 * @param string $field_value
	 * @param bool $is_choice_text_valid True: Also check the $choice['text'] value against $field_value
	 *
	 * @return bool True: Choice exists with text or value of $field_value; false: nope!
	 */
	function validate_field_choices( $field, $field_value, $is_choice_text_valid = false ) {

		$valid = false;

		foreach ( $field->choices as $choice ) {
			// The value is one of the official choices. It's valid.
			if ( trim( $choice['value'] ) === trim( $field_value ) ) {
				$valid = true;
				break;
			}

			if( $is_choice_text_valid && trim( $choice['text'] ) === trim( $field_value ) ) {
				$valid = true;
				break;
			}
		}

		return $valid;
	}

	function validate_field_radio( &$field, $field_value ) {

		$ignore_required = gravityview_importer()->get_setting( 'ignore_required' );

		$valid = true;

		// Empty's NOT allowable if required!
		if ( ! $ignore_required && $field->isRequired && $this->is_empty( $field_value ) ) {
			$field->failed_validation  = true;
			$field->validation_message = __( 'This field is required.', 'gravityview-importer' );

			return;
		}

		// Empty's allowable
		if ( $this->is_empty( $field_value ) ) {
			$valid = true;
		} // If the "Other" choice value is enabled, it can be anything
		else if ( $field->enableOtherChoice ) {
			$valid = true;
		} else {
			$valid = $this->validate_field_choices( $field, $field_value );
		}

		if ( false === $valid && apply_filters( 'gravityview-importer/strict-mode', apply_filters( 'gravityview-importer/strict-mode/radio-choices', true ) ) ) {
			$field->failed_validation  = true;
			$field->validation_message = sprintf( __( 'The value was not one of the defined radio field Choices: "%s"', 'gravityview-importer' ), $field_value );
		}
	}

	function validate_field_list( &$field, $field_value ) {

		if ( apply_filters( 'gravityview-importer/strict-mode', apply_filters( 'gravityview-importer/strict-mode/field-maxrows', true ) ) ) {
			if ( $field->maxRows ) {
				$maxRow = intval( $field->maxRows );
				$value  = maybe_unserialize( $field_value );
				if ( sizeof( $value ) > $maxRow ) {
					$field->failed_validation  = true;
					$field->validation_message = sprintf( __( 'The number of rows (%d) was greater than the Maximum Rows field setting allows (%d).', 'gravityview-importer' ), sizeof( $value ), $maxRow );
				}
			}
		}
	}

	/**
	 * Handle validating option fields
	 *
	 * @since 1.2
	 *
	 * @param GF_Field_Option $field
	 * @param string $field_value
	 * @param string $field_id
	 *
	 * @return array|bool
	 */
	private function validate_field_option( &$field, $field_value ) {

		$valid = true;
		$validation_message = null;

		$csv_options = explode( ',', $field_value );

		foreach ( $csv_options as $csv_option ) {

			// First, check whether it's already got an option set
			$check_option = explode( '|', $csv_option );
			if( 2 === sizeof( $check_option ) ) {
				$option_label = $check_option[0];
				$option_price = $check_option[1];
				if( '' === $option_price ) {
					$valid = false;
					$validation_message = sprintf( __( 'An price was not set for Option "%s"', 'gravityview-importer' ), $option_label );
				}
			} else {

				// Get an array where $options[ $choice['value'] ] => $choice['text']
				$options = wp_list_pluck( $field->choices, 'text', 'value' );


				if( ! isset( $options["{$csv_option}"] ) && ! array_search( $csv_option, $options, true ) ) {
					$valid              = false;
					$validation_message = sprintf( 'Price Option with value of "%s" does not exist for the "%s" field', $csv_option, $field->get_field_label( false, $csv_option ) );
				}
			}

			if( ! $valid ) {
				$field->failed_validation  = true;
				$field->validation_message = $validation_message;
			}

		}

	}

	/**
	 * Process the GF formatting for Post Images and validate the file
	 *
	 * @param $field
	 * @param $field_value
	 */
	function validate_field_post_image( &$field, $field_value ) {

		$value = $this->get_post_image_format_from_value( $field_value );

		$image_array = explode( '|:|', $value );

		list( $url, $title, $caption, $description ) = array_pad( $image_array, 4, false );

		// The file is the first parameter in the |:| array
		if ( ! $this->is_empty( $url ) ) {
			$this->validate_field_fileupload( $field, $url );
		}
	}

	/**
	 * @param GF_Field_FileUpload $field
	 * @param $field_value
	 */
	function validate_field_fileupload( &$field, $field_value ) {

		if ( $field->multipleFiles ) {
			$file_names = GV_Import_Entries_Addon::maybe_decode_json( $field_value );
		} else {
			$file_names = array( $field_value );
		}

		if ( $this->is_empty( $file_names ) || ! is_array( $file_names ) ) {
			return;
		}

		$allowed_extensions = ! empty( $field->allowedExtensions ) ? GFCommon::clean_extensions( explode( ',', strtolower( $field->allowedExtensions ) ) ) : array();

		foreach ( $file_names as $file_name ) {
			$info = pathinfo( $file_name );

			if ( empty( $allowed_extensions ) ) {
				if ( GFCommon::file_name_has_disallowed_extension( $file_name ) ) {
					$field->failed_validation  = true;
					$field->validation_message = empty( $field->errorMessage ) ? __( 'The uploaded file type is not allowed.', 'gravityview-importer' ) : $field->errorMessage;
				}
			} else {
				if ( ! empty( $info['basename'] ) && ! GFCommon::match_file_extension( $file_name, $allowed_extensions ) ) {
					$field->failed_validation  = true;
					$field->validation_message = empty( $field->errorMessage ) ? sprintf( __( 'The uploaded file type is not allowed. Must be one of the following: %s', 'gravityview-importer' ), strtolower( $field->allowedExtensions ) ) : $field->errorMessage;
				}
			}
		}

	}

	/**
	 * Some fields need to be tweaked before passing to GF_Field validation.
	 *
	 * @param $field
	 */
	private function modify_field_settings_pre_validation( &$field ) {

		// Make sure all failed validation settings are false.
		// We had an issue with date fields being defaulted to true.
		$field->failed_validation = false;

		switch ( $field->type ) {
			case 'date':
				$field->dateType = 'datepicker';
				break;

			/**
			 * Tell GF not to check for confirmed status
			 */
			case 'email':
				$field->emailConfirmEnabled = false;
				break;
		}

	}

	/**
	 * Does the entry match the import feed conditions?
	 *
	 * @param array $entry
	 *
	 * @return boolean True: Yes, import; False: Nope, skip.
	 */
	private function is_feed_condition_met( $entry ) {

		$is_met = gravityview_importer()->is_feed_condition_met( $this->feed, $this->form, $entry );

		return $is_met;
	}

}
