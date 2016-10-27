<?php

class GravityView_Import_History {

	/**
	 * @var GravityView_Import_History
	 */
	static private $instance;

	var $last_added = array();

	/**
	 * @return GravityView_Import_History
	 */
	static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {
		add_action( 'gravityview-importer/end-of-file', array( $this, 'log_import' ), 1, 2 );
	}

	/**
	 * In the future, we'll want to be able to roll back imports. Keeping a log of imports will help achieve that.
	 *
	 * @return void
	 */
	function log_import( GravityView_Handle_Import $import_handler, $last_line_number = 0 ) {

		$added = $import_handler->Entry_Importer->Reporter->getAddedSize();

		$last_added = array(
			'time'                 => current_time( 'timestamp' ),
			'added'                => $added,
			'form'                 => $import_handler->Addon->get_form_id(),
			'user_agent'           => sprintf( $import_handler->Entry_Importer->get_user_agent(), $import_handler->Entry_Importer->get_previous_entry_id() ),
			'previous_entry_count' => $import_handler->Entry_Importer->get_previous_entry_id(),
			'current_entry_count'  => $import_handler->Entry_Importer->get_max_entry_id(),
		);

		$this->last_added = $last_added;

		// Only add an import log if there were added entries
		if ( empty( $added ) ) {
			return;
		}

		// Previous imports
		$imports = get_option( 'gravityview-importer/imports', array() );

		// If empty, create option
		if ( empty( $imports ) ) {
			add_option( 'gravityview-importer/imports', array(), '', 'no' );
		} else {
			// Limit log length to reasonable number
			$number_to_store = absint( apply_filters( 'gravityview/importer/log_count', 50 ) );
			$imports         = array_slice( $imports, ( $number_to_store * - 1 ), $number_to_store, true );
		}

		$imports[] = $last_added;

		// Update to use new data
		update_option( 'gravityview-importer/imports', $imports );
	}

}

GravityView_Import_History::get_instance();