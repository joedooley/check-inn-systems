/*global jQuery, document, ajaxurl */
(function( $ ) {
	"use strict";

	var GVImportSettings = {

		message: '',
		license_field: $('#license_key'),
		activate_button : $( '[data-edd_action=activate_license]' ),
		deactivate_button: $( '[data-edd_action=deactivate_license]' ),
		check_button: $( '[data-edd_action=check_license]' ),

		init: function() {

			GVImportSettings.message_fadeout();
			GVImportSettings.add_status_container();

			$( document )
				.on( 'ready keyup', GVImportSettings.license_field, GVImportSettings.key_change )
				.on( 'click', ".gv-edd-action", GVImportSettings.clicked )
				.on( 'gv-edd-failed gv-edd-invalid', GVImportSettings.failed )
				.on( 'gv-edd-valid', GVImportSettings.valid )
				.on( 'gv-edd-deactivated', GVImportSettings.deactivated )
				.on( 'gv-edd-inactive gv-edd-other', GVImportSettings.other );

		},

		/**
		 * Hide the "Settings Updated" message after save
		 */
		message_fadeout: function() {
			setTimeout( function() {
				$('#gform_tab_group #message' ).toggle('scale');
			}, 2000 );
		},

		add_status_container: function() {
			$( GVImporter.license_box ).insertBefore( GVImportSettings.license_field );
		},

		/**
		 * When the license key changes, change the button visibility
		 * @todo refactor- no need having this, plus all the separate methods
		 * @param e
		 */
		key_change: function( e ) {

			//return;
			var license_key = $('#license_key').val();

			var showbuttons = false;
			var hidebuttons = false;

			//buttons.show();

			if( license_key.length > 0 ) {

				switch( $('#license_key_status' ).val() ) {
					case 'valid':
						hidebuttons = $('[data-edd_action=activate_license]' );
						showbuttons = $('[data-edd_action=deactivate_license],[data-edd_action=check_license]' );
						break;
					case 'deactivated':
					case 'site_inactive':
					default:
						hidebuttons = $('[data-edd_action=deactivate_license]' );
						showbuttons = $('[data-edd_action=activate_license],[data-edd_action=check_license]' );
						break;
				}
			} else if ( license_key.length === 0 ) {
				hidebuttons = $('[data-edd_action*=_license]');
			}

			console.log( hidebuttons );
			console.log( showbuttons );

			// On load, no animation. Otherwise, 100ms
			var speed = ( e.type === 'ready' ) ? 0 : 'fast';

			if( hidebuttons ) {
				hidebuttons.filter(':visible').fadeOut( speed );
			}
			if( showbuttons ) {
				showbuttons.filter( ':hidden' ).removeClass( 'hide' ).hide().fadeIn( speed );
			}
		},

		/**
		 * Show the HTML of the message
		 * @param message HTML for new status
		 */
		update_status: function( message ) {
			if( message !== '' ) {
				$( '#gv-edd-status' ).replaceWith( message );
			}
		},

		set_pending_message: function( message ) {
			$( '#gv-edd-status' )
				.addClass('pending')
				.html( '<p>' + message + '</p>');
		},

		clicked: function( e ) {
			e.preventDefault();

			var $that = $( this );

			var theData = {
				license: $('#license_key').val(),
				edd_action: $that.attr( 'data-edd_action' ),
				field_id: $that.attr( 'id' ),
			};

			$that.not( GVImportSettings.check_button ).addClass('button-disabled');

			$( '#gform-settings,#gform-settings .button').css('cursor', 'wait');

			GVImportSettings.set_pending_message( $that.attr('data-pending_text') );

			GVImportSettings.post_data( theData );

		},

		post_data: function( theData ) {

			$.post( ajaxurl, {
				'action': 'gravityview_importer_license',
				'data': theData
			}, function ( response ) {

				response = $.parseJSON( response );

				GVImportSettings.message = response.message;

				if( theData.edd_action !== 'check_license' ) {
					$( '#license_key_status' ).val( response.license );
					$( '#license_key_response' ).val( JSON.stringify( response ) );
					$( document ).trigger( 'gv-edd-' + response.license, response );
				}

				GVImportSettings.update_status( response.message );

				$( '#gform-settings')
					.css('cursor', 'default')
					.find('.button')
					.css('cursor', 'pointer');
			} );

		},

		valid: function( e ) {
			GVImportSettings.activate_button
				.fadeOut( 'medium', function () {
					GVImportSettings.activate_button.removeClass( 'button-disabled' );
					GVImportSettings.deactivate_button.fadeIn().css( "display", "inline-block" );
				} );
		},

		failed: function( e ) {
			GVImportSettings.deactivate_button.removeClass( 'button-disabled' );
			GVImportSettings.activate_button.removeClass( 'button-disabled' );
		},

		deactivated: function( e ) {
			GVImportSettings.deactivate_button
				.css('min-width', function() {
					return $(this ).width();
				})
				.fadeOut( 'medium', function () {
					GVImportSettings.deactivate_button.removeClass( 'button-disabled' );
					GVImportSettings.activate_button.fadeIn(function() {
						$(this).css( "display", "inline-block" );
					})
				} );

		},

		other: function( e ) {
			GVImportSettings.deactivate_button.fadeOut( 'medium', function () {
				GVImportSettings.activate_button
					.removeClass( 'button-disabled' )
					.fadeIn()
					.css( "display", "inline-block" );
			} );
		}
	};

	GVImportSettings.init();

})(jQuery);
