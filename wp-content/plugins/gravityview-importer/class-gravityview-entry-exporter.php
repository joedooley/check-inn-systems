<?php

use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

use Goodby\CSV\Export\Standard\Collection\CallbackCollection;

$config = new ExporterConfig();
$exporter = new Exporter($config);

class GravityView_Entry_Exporter {

	/**
	 * @var GravityView_Entry_Importer
	 */
	var $GravityView_Entry_Importer;

	var $headers = array();

	var $rows = array();

	/**
	 * URL to download generated file
	 *
	 * @var string
	 */
	var $download_url = '';

	function __construct() {
		$this->add_hooks();
	}

	function add_hooks() {

		add_action( 'gravityview-importer/invalid-row', array( $this, 'add_row' ), 10, 3 );

		add_action( 'gravityview-importer/end-of-file', array( $this, 'export' ), 100, 2 );

		add_action( 'gravityview-import/report/after-errors', array( $this, 'print_download_link' ) );
	}

	function set_headers( $headers ) {
		$this->headers = $headers;
	}

	function add_row( $entry = array(), $line_number = 0, GravityView_Entry_Importer $GravityView_Entry_Importer ) {
		if ( ! isset( $this->GravityView_Entry_Importer ) ) {
			$this->GravityView_Entry_Importer = $GravityView_Entry_Importer;
		}

		$this->rows[] = $entry;
	}

	/**
	 * @return string
	 */
	function get_filename() {

		$form = gravityview_importer()->getImporter()->Entry_Importer->get_form();

		$filename = sanitize_title_with_dashes( $form['title'] . '-' . __( 'Errors', 'gravityview-importer' ) ) . '-' . gmdate( 'Y-m-d', GFCommon::get_local_timestamp( time() ) ) . '.csv';

		return $filename;
	}

	/**
	 * Processes after the log is made
	 */
	function export() {

		if ( empty( $this->rows ) ) {
			return;
		}

		try {

			$Import_Handler = GravityView_Handle_Import::getInstance();

			$config = new ExporterConfig();

			$config->setFromCharset( $Import_Handler->getBlogCharset() );
			$config->setToCharset( $Import_Handler->getBlogCharset() );

			$exporter = new Exporter( $config );

			// Don't show errors, even though we want to really...
			$exporter->unstrict();

			$filename = $this->get_filename();

			$target = GFFormsModel::get_file_upload_path( gravityview_importer()->getImporter()->Entry_Importer->get_form_id(), $filename );

			$headers = $Import_Handler->getHeaderRowFieldMap();

			$name_and_mapped_id = array_filter( gravityview_importer()->_get_field_map_fields() );

			$header_row = array();

			// Create an array of the header row labels that are being mapped
			foreach ( $name_and_mapped_id as $field_name => $field_id ) {
				foreach ( $headers as $header ) {
					if ( $header['name'] === $field_name ) {
						$header_row[ $field_id ] = $header['label'];
					}
				}
			}

			array_unshift( $this->rows, $header_row );

			$exporter->export( $target['path'], $this->rows );

			$this->download_url = $target['url'];

		} catch ( Exception $e ) {
			echo __( 'There was an error generating an export file.', 'gravityview-importer' );

			return;
		}

	}

	/**
	 * Show the download link to the generated CSV
	 */
	function print_download_link() {

		if ( empty( $this->download_url ) || empty( $this->rows ) || ! $this->GravityView_Entry_Importer->Reporter->getAddedSize() ) {
			return;
		}

		/** @define "$base_path" "./" */
		$base_path = trailingslashit( gravityview_importer()->get_base_path() );

		include( $base_path . 'partials/download-errors-file.php' );

	}
}

new GravityView_Entry_Exporter();