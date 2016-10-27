<?php

abstract class GravityView_Importer_Field_Type {

	/**
	 * @var string The name of the field type
	 */
	protected $type;

	/**
	 * Add any hooks that may be required for the field type
	 */
	function add_hooks() {}

	abstract function fill_value( &$entry );

	abstract function prepare_value( &$entry );

	abstract function after_insert( &$entry );

}