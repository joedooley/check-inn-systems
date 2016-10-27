<?php

if( !function_exists( 'mb_convert_encoding' ) ) {

	/**
	 * Some people don't have mb_convert_encoding()
	 *
	 * @see http://php.net/manual/en/function.mb-convert-encoding.php
	 *
	 * @since 1.0.7
	 */
	function mb_convert_encoding( $string = '', $to_encoding = "UTF-8", $from_encoding = '' ) {

		$converted = iconv( $from_encoding, $to_encoding . '//TRANSLIT', $string );

		return $converted;
	}
}

/**
 * Tell the server to reset the time limit during the loop so there is no timeout
 */
function gv_importer_reset_time_limits() {

	if ( apply_filters( 'gravityview-importer/ignore-user-abort', false ) ) {
		// Don't let closing the window stop the import process
		ignore_user_abort( true );
	}

	$disabled = gv_importer_is_func_disabled( 'set_time_limit' );

	if ( ! $disabled && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	@ini_set( 'max_input_time', 300 );
	@ini_set( 'max_execution_time', 600 );
}

/**
 * Check whether a function is enabled.
 *
 * @see edd_is_func_disabled()
 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/misc-functions.php
 * @param string $function Name of the function
 *
 * @return bool True: disabled; False: enabled
 */
function gv_importer_is_func_disabled( $function = '' ) {

	$disabled = explode( ',', ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}