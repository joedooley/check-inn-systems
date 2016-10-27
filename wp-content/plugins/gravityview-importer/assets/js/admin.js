/**
 * globals jQuery,gv_importer_strings
 */

(function( $ ) {
	'use strict';

	var GVImport = {

		selectFields: null,

		/**
		 * Whether to prevent the form from submitting.
		 * @since 1.1
		 * @see GVImport.submitSettings
		 */
		allowSubmit: true,

		init: function() {

			GVImport.setVars();
			GVImport.moveButtons();
			GVImport.updateFormParameters();

			$( document )
				.on('change', '#gform-settings', GVImport.updateAvailableFields )
				.on('change', '#gform-settings', GVImport.initDependentSettings )
				.on('submit', '#gform-settings', GVImport.submitSettings )
				.on('click', GVImport.resetButton, GVImport.resetMapping )
				.on('click', GVImport.smartButton, GVImport.smartMapping )
				.on('click', ':input[type=submit]', GVImport.addClickedAttr )
				.on('click', '.gv-importer-hide-console', GVImport.toggleConsole )
				.on('click', '.gv-complete-import', GVImport.completeImport );

			// Force available fields to be triggered
			$( '#gform-settings' ).trigger('change');
		},

		/**
		 * Get a field mapping <select> element by the selected <option>'s value
		 *
		 * @param {string} option_value The value attribute of the <option>
		 * @returns {jQuery|boolean}
		 */
		getFieldMapSelectByValue: function( select_value ) {
			var $option = $( '#gaddon-setting-row-import_field_map' ).find( 'select option[value="' + select_value + '"]:selected' );

			return ( $option && $option.length > 0 ) ? $option.parent( 'select' ) : false;
		},

		/**
		 * Take a settings row and move it under a <select> field when the select field value matches
		 * @param {string} select_value The value to match of the <option> in the select field
		 * @param {string} row_id The ID of the settings row to move under the select field (without `#gaddon-setting-row-` prefix)
		 * @param {jQuery} e The event that triggered
		 */
		toggleDependentSettings: function( select_value, row_id, e ) {
			var $target = $( e.target );
			var $settings_row = $( '#gaddon-setting-row-' + row_id );
			var $select = false;

			if( $target.is('select') ) {
				$select = $target;
			} else if( $target.is('form') ) {
				// If the event is the form being triggered onLoad, find a select with the right value
				$select = GVImport.getFieldMapSelectByValue( select_value );
			}

			if( ! $select ) {
				return;
			}

			// If the values match, append the settings row to below the <select>
			if( select_value === $select.val() ) {
				$settings_row
					.find('th').hide().end()
					.find('td').attr('colspan', 2 ).end()
					.find('input:checkbox').attr('checked', null ).end() // Default values to false
					.remove()
					.attr('data-parent-field-id', $select.attr('id') )
					.insertAfter( $select.parent( 'td' ).parent('tr') )
					.fadeIn();
			} else {
				// Otherwise, hide it
				$settings_row
					.filter('[data-parent-field-id="' + $select.attr('id') + '"]' ).fadeOut('fast');
			}
		},

		/**
		 * Specify settings rows that get added to the field map table based on the mapped values
		 *
		 * @param e
		 */
		initDependentSettings: function( e ) {
			GVImport.toggleDependentSettings( 'post_id', 'overwrite_post_data', e );
			GVImport.toggleDependentSettings( 'entry_id', 'overwrite_entry', e );
			//GVImport.toggleDependentSettings( 'entry_id', 'skip_empty_overwrite_entry', e );
		},

		/**
		 * The reset button was inside the tooltip. This fixes that.
		 */
		moveButtons: function() {

			var $field_map = $( '#gaddon-setting-row-import_field_map' );

			$field_map.find( 'th button' ).appendTo( $field_map.find( '> th' ) );
		},

		/**
		 * Set initial values if the fields are exact matches
		 *
		 * @param e
		 */
		smartMapping: function( e ) {
			e.preventDefault();

			$('.settings-field-map-table').find('tr')
				.filter(function() {
					return $('select', $(this) ).val() === '';
				})
				.each(function() {
					var column_label = $('td:first-child label', $( this ) ).text().trim().toLowerCase();
					$( 'option', $( 'td:last-child select', $( this ) ) ).filter(function() {
						return $( this ).text().trim().toLowerCase() === column_label;
					} ).attr('selected', 'selected');
				});
		},

		/**
		 * Reset the field mapping to empty values
		 * @param e
		 */
		resetMapping: function( e ) {
			// We don't want the form reset, just the fields we want.
			// Also causes weird conflicts with GVImport.updateAvailableFields
			e.preventDefault();

			$( '.settings-field-map-table select' ).val( '' );

			// Reset the disabled attr
			$( '#gform-settings' ).trigger( 'change' );

			$( this ).fadeOut('fast');
		},

		/**
		 * Add click attr to the clicked submit button to allow submitSettings to see what submit button was clicked
		 */
		addClickedAttr: function() {
			$(":input[type=submit]", $(this).parents("form")).removeAttr("clicked");
			$( this ).attr('clicked', true );
		},

		getNotEmptySelectsLength: function() {
			return $('.settings-field-map-table:visible').find( 'tbody tr:visible' ).filter( function () {
				return $( 'select', this ).val() !== '';
			} ).length;
		},

		/**
		 * Make sure there are mapped fields before allowing submission
		 * @param e
		 * @returns {boolean}
		 */
		submitSettings: function( e ) {

			var $target = $( e.target );

			// They were submitting the form, not just saving the configuration
			// [clicked] was added by GVImport.addClickedAttr
			if( $target.find( 'input[clicked]' ).hasClass( 'button-primary' ) ) {
				GVImport._submitEmptyFieldMap( $target );
				GVImport._submitConfirmations();
			}

			return GVImport.allowSubmit;
		},

		/**
		 * Make sure users know exactly what they're doing by confirming overwriting data
		 *
		 * @returns {boolean}
		 * @private
		 */
		_submitConfirmations: function() {

			var passed_confirmations = true;

			var $entry_select = GVImport.getFieldMapSelectByValue( 'entry_id' );

			if ( $entry_select ) {
				passed_confirmations = window.confirm( gv_importer_strings.overwrite_entry );
				if( !passed_confirmations ) {
					$entry_select.focus();
				}
			}

			var $post_select = GVImport.getFieldMapSelectByValue( 'post_id' );

			if( $post_select && passed_confirmations ) {
				passed_confirmations = window.confirm( gv_importer_strings.overwrite_posts );
				if( !passed_confirmations ) {
					$post_select.focus();
				}
			}

			GVImport.allowSubmit = passed_confirmations;
		},

		/**
		 * Validate empty field map submission
		 * @param $target
		 * @private
		 */
		_submitEmptyFieldMap: function( $target ) {

			var field_map = $('.settings-field-map-table:visible');

			if( field_map.length > 0 ) {

				// There are only empty selects
				if ( GVImport.getNotEmptySelectsLength() === 0 ) {

					alert( gv_importer_strings.field_mapping_empty );

					// Highlight the field
					$( '#gaddon-setting-row-import_field_map' ).find('tr:first-child select' ).focus();

					GVImport.allowSubmit = false;

				}
			}
		},

		completeImport: function( e ) {
			e.preventDefault();

			var data = {
				action: 'gv_import_complete',
				feed_id: gv_importer_strings.feed_id,
				nonce: gv_importer_strings.nonce
			};

			$( e.target ).html( gv_importer_strings.wrapping_up );

			$.post( ajaxurl, data, function ( response ) {

				$( e.target ).text( gv_importer_strings.complete );

				var url = $( e.target ).attr('href' );

				if( !url || url === '#' ) {
					// Reload the page
					window.location.reload();
				} else {
					// Redirect to the link
					window.location.replace( url );
				}
			} );

		},

		setVars: function() {
			GVImport.selectFields = $( '#gform-settings' ).find('select.gaddon-select:first' ).clone();
			GVImport.resetButton = '#gform-settings button[type=reset]';
			GVImport.smartButton = '#gform-settings button.smart-map';
		},

		/**
		 * Convert the form into the correct type to upload files
		 */
		updateFormParameters: function() {

			// Improve form design
			$( '.settings-field-map-table' )
				.find('thead tr th:last-child:not(.gv-importer-col-form-field)').html( gv_importer_strings.column_header ) // Make the label better
					.end()
				.find('tbody tr:odd')
					.addClass('alt'); // Zebra stripe

			// Allow uploads
			$('#gform-settings').attr('enctype', 'multipart/form-data' );
		},

		toggleConsole: function( e ) {
			e.preventDefault();

			var $button = $( this );

			$('.gravityview-importer-console' ).slideToggle( function() {

				var button_text = $( this ).is(':visible') ? gv_importer_strings.hide_console : gv_importer_strings.show_console;

				$button.text( button_text );
			});
		},

		importerComplete: function() {

			$('.gravityview-importer-status.processing' ).fadeOut('fast', function() {
				$('.gravityview-importer-status.complete' ).fadeIn('fast');
			});


			$('.gravityview-importer-console' ).animate({
				height: 200
			}, 1000, function() {
				$( '.gravityview-importer-report' ).slideDown();
			});
		},

		/**
		 * Modify the GVImport.selectFields input to disable existing search fields, then replace the fields with the generated input.
		 * @return {void}
		 */
		updateAvailableFields: function( e ) {

			// Clear out the disabled options first
			$( 'option', GVImport.selectFields ).attr('disabled', null );

			if( GVImport.getNotEmptySelectsLength() === 0 ) {
				$( GVImport.resetButton ).hide();
			} else {
				$( GVImport.resetButton ).fadeIn('fast');
			}

			$('#gform-settings' )

				.find('tr select.gaddon-select' )

				// Update the selectFields var to disable all existing values
				.each( function() {

					if( $( this ).val() !== '' ) {
						GVImport.selectFields
							.find('option[value="'+ $( this ).val() +'"]')
							.attr( 'title', gv_importer_strings.already_mapped )
							.attr('disabled', true);
					}
				})

				.not( e.target )

				// Then once we have the select input finalized, run through again
				// and replace the select inputs with the new one
				.each( function() {

					var select = GVImport.selectFields.clone();

					// Set the value
					select.val( $(this).val() );

					// Enable the option with the current value
					select.find('option:selected').attr('disabled', null );

					// Set the ID and name to what they should be
					select.attr('id', $( this ).attr('id') );
					select.attr('name', $( this ).attr('name') );

					// Replace the select with the generated one
					$( this ).replaceWith( select );
				});

		},
	};

	$( document )
		.on( 'ready', GVImport.init )
		.on('importer-complete', GVImport.importerComplete )
		.on('importer-added', GVImport.importerAdded );

})( jQuery );
