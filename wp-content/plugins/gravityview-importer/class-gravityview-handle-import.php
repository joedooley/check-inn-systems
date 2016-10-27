<?php

use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\Interpreter;
use Goodby\CSV\Import\Standard\LexerConfig;

class GravityView_Handle_Import {

	var $delimiter;

	/**
	 * @var GravityView_Entry_Importer
	 */
	var $Entry_Importer;

	/**
	 * @var Goodby\CSV\Import\Standard\Lexer
	 */
	var $Lexer;

	/**
	 * @var array
	 */
	var $file = array();

	var $_read_only = false;

	var $field_map = array();

	var $header = array();

	/**
	 * @var bool
	 */
	private $_header_only = false;

	/**
	 * @var int
	 */
	private $_counter = 0;

	/**
	 * @var GV_Import_Entries_Addon
	 */
	var $Addon;

	static $instance;

	private function __construct() {

		$this->Addon = gravityview_importer();

		$this->Entry_Importer = GravityView_Entry_Importer::getInstance();

		$this->add_hooks();
	}

	function add_hooks() {
		add_action( 'gravityview-importer/import', array( $this, 'gfImport' ) );
	}

	public static function getInstance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	function setFile( array $file ) {
		$this->file = $this->Addon->get_file();
	}

	function getFilePath() {

		$file = $this->getFileArray();

		return $file ? $file['file'] : NULL;
	}

	/**
	 * @return string|null The mime type of the file. NULL: no file array
	 */
	function getFileType() {

		$file = $this->getFileArray();

		return $file ? strtolower( $file['type'] ) : NULL;
	}

	function getFileArray() {
		return $this->Addon->get_file();
	}

	/**
	 * Get the header row for a file
	 * @return array
	 */
	public function getHeaderRow() {

		// Already set.
		if ( $this->header ) {
			return $this->header;
		}

		$this->field_map = $this->Addon->_get_field_map_fields();

		$this->_read_only = true;

		$this->_header_only = true;

		$this->parseFile();

		if ( empty( $this->header ) ) {
			return array();
		}

		$this->header = array_values( $this->header );

		return $this->header;
	}

	/**
	 * Take the CSV header row and convert it into an array used by GFFeedAddon
	 * @return array
	 */
	public function getHeaderRowFieldMap() {

		$headers = $this->getHeaderRow();

		$fields = array();
		$names  = array();

		$i = 1;
		foreach ( $headers as $header ) {

			// Don't allow for empty labels
			if ( empty( $header ) ) {
				$header = sprintf( __( 'Empty (Column %d)', 'gravityview-importer' ), $i );
			}

			$name = sanitize_title_with_dashes( $header );

			// Make sure there's a unique name
			if ( in_array( $name, $names ) ) {
				$name .= '-' . ( $i - 1 );
			}

			$names[] = $name;

			$fields[] = array(
				'name'  => $name,
				'label' => $header,
			);

			$i ++;
		}

		unset( $names );

		return $fields;
	}

	function gfImport() {

		$this->header = $this->getHeaderRow();

		$this->field_map = $this->Addon->_get_field_map_fields();

		$this->_read_only = false;

		$this->_header_only = false;

		$this->parseFile();

	}

	/**
	 * Parse the file
	 */
	function parseFile() {

		$file_path = $this->getFilePath();

		if ( empty( $file_path ) ) {
			return;
		}

		// Prevent timeout overall
		gv_importer_reset_time_limits();

		$this->Lexer = new Lexer( $this->getLexerConfig() );

		$Interpreter = new Interpreter;

		$Interpreter->unstrict();

		$lineNumber = - 1;

		$Interpreter->addObserver( function ( array $row ) use ( &$lineNumber ) {

			$continue_running = GravityView_Handle_Import::getInstance()->parseRow( $row, $lineNumber );

			if ( false === $continue_running ) {
				return;
			}

		} );

		$this->Lexer->parse( $file_path, $Interpreter );

		// Make sure there are no leftover entries after batches have been processed
		if ( ! $this->_header_only ) {
			do_action( 'gravityview-importer/end-of-file', $this, $lineNumber );
		}

	}

	/**
	 *
	 * @param $row
	 * @param $lineNumber
	 *
	 * @return boolean Continue running observer?
	 */
	function parseRow( $row, &$lineNumber ) {

		// Reset timeout on each row
		gv_importer_reset_time_limits();

		$lineNumber ++;

		// If we're only getting the header...
		if ( $this->_header_only ) {
			if ( $lineNumber === 0 && empty( $this->header ) ) {

				/**
				 * Fix BOM added by Gravity Forms export.php
				 * @see http://stackoverflow.com/questions/5601904/encoding-a-string-as-utf-8-with-bom-in-php
				 */
				$row[0] = str_replace( chr( 239 ) . chr( 187 ) . chr( 191 ), '', $row[0] );
				$row[0] = trim( $row[0], '"' );

				foreach ( $row as $key => $col ) {
					$row["{$key}"] = str_replace( '""', '"', $col ); // Replace double escaped quotes
				}

				$this->header = $row;
			}

			return false;
		}

		$combined_row = array();

		$header_row_map = $this->getHeaderRowFieldMap();

		// The arrays should be the same size.
		if ( sizeof( $row ) !== sizeof( $header_row_map ) ) {

			/**
			 * @filter `gravityview-importer/unstrict` Whether to allow importing rows that aren't the same size as expected
			 * @param bool $allow_mismatched_rows Default: false
			 */
			$allow_mismatched_rows = apply_filters( 'gravityview-importer/unstrict', false );

			if ( $allow_mismatched_rows ) {

				// The row counts don't match. This is likely an issue with escaping quotes.
				gravityview_importer()->log_debug( sprintf( '$header_row_map and $row are different lengths: $header_row_map %s $row %s', print_r( $header_row_map, true ), print_r( $row, true ) ) );

				if ( sizeof( $row ) < sizeof( $header_row_map ) ) {
					$row = array_pad( $row, sizeof( $header_row_map ), '' );
				} else {
					$header_row_map = array_pad( $header_row_map, sizeof( $row ), '' );
				}

			} else {
				// The row counts don't match. This is likely an issue with escaping quotes.
				gravityview_importer()->log_error( sprintf( '$input_ids and $row are different lengths: $input_ids %s $row %s', print_r( $input_ids, true ), print_r( $row, true ) ) );

				return true;
			}
		}

		/**
		 * @var int $key
		 * @var array $row_map
		 */
		foreach ( $header_row_map as $key => $row_map ) {

			$name = $row_map['name'];

			// If the field has been mapped, add the value to the combined entry
			if ( ! empty( $this->field_map[ $name ] ) ) {

				// The field mapping stores the field value
				$input_id = $this->field_map[ $name ];

				// If the Row value at the header key exists, set it for the combined entry
				if ( isset( $row[ $key ] ) && '' !== $row[ $key ] ) {
					$combined_row[ $input_id ] = $row[ $key ];
				}
			}
		}

		// Any unmapped columns will have the key of ''. Unset them (only import mapped columns)
		unset( $combined_row[''] );

		do_action( 'gravityview-importer/process-row', (array) $combined_row, $lineNumber );
	}

	/**
	 * Check whether the name of the file passes any charset information.
	 *
	 * This way, users can name a file to include the charset, like:
	 * `example-utf-16.txt` or `example-Windows-1252.csv`
	 *
	 * @link http://docs.gravityview.co/article/258-exporting-a-csv-from-excel
	 * @link http://php.net/manual/en/mbstring.supported-encodings.php
	 *
	 * @param string $name File name
	 *
	 * @return string Charset name, `UTF-8` by default
	 */
	private function getCharsetFromName( $name ) {

		$charsets = array(

			'UCS-4BE',
			'UCS-4LE',
			'UCS-2BE',
			'UCS-2LE',
			'UCS-2',
			'UTF-32BE',
			'UTF-32LE',
			'UTF-32',
			'UTF-16BE',
			'UTF-16LE',
			'UTF-16',
			'UTF7-IMAP',
			'UTF-7',
			'EUC-JP',
			'eucJP-win',
			'SJIS-Mobile#DOCOMO',
			'SJIS-DOCOMO',
			'SJIS-Mobile#KDDI',
			'SJIS-KDDI',
			'SJIS-Mobile#SOFTBANK',
			'SJIS-SOFTBANK',
			'SJIS-win',
			'SJIS-mac',
			'SJIS',
			'ISO-2022-JP-MOBILE#KDDI',
			'ISO-2022-JP-KDDI',
			'ISO-2022-JP-MS',
			'ISO-2022-JP',
			'ISO-2022-KR',
			// Group with longer first so -10 matches before -1
			'ISO-8859-10',
			'ISO-8859-13',
			'ISO-8859-14',
			'ISO-8859-15',
			'ISO-8859-1',
			'ISO-8859-2',
			'ISO-8859-3',
			'ISO-8859-4',
			'ISO-8859-5',
			'ISO-8859-6',
			'ISO-8859-7',
			'ISO-8859-8',
			'ISO-8859-9',
			// Group with longer first so -8-Mobile matches before -8
			'UTF-8-Mobile#DOCOMO',
			'UTF-8-Mobile#KDDI-A',
			'UTF-8-Mobile#KDDI-B',
			'UTF-8-Mobile#SOFTBANK',
			'UTF-8-SOFTBANK',
			'UTF-8-DOCOMO',
			'UTF-8-KDDI',
			'UTF-8',
			'JIS-ms',
			'JIS',
			'CP50220raw',
			'CP50220',
			'CP50221',
			'CP50222',
			'MacJapanese',
			'ASCII',
			'CP932',
			'CP51932',
			'byte2be',
			'byte2le',
			'byte4be',
			'byte4le',
			'BASE64',
			'HTML-ENTITIES',
			'7bit',
			'8bit',
			'EUC-CN',
			'CP936',
			'GB18030',
			'HZ',
			'EUC-TW',
			'CP950',
			'BIG-5',
			'EUC-KR',
			'UHC',
			'CP1251',
			'CP1252',
			'Windows-1252',
			'Windows-1251',
			'CP866',
			'IBM866',
			'KOI8-R',
		);

		/**
		 * @filter `gravityview-importer/default-charset` Modify the default charset for files
		 *
		 * @param string $charset PHP Charset string. Default: the current blog charset (UTF-8 by default)
		 * @param string $name File name
		 */
		$default_charset = apply_filters( 'gravityview-importer/default-charset', get_option( 'blog_charset' ), $name );

		$charsets_quote = array_map( 'preg_quote', $charsets );

		$charsets_string = implode( '|', $charsets_quote );

		preg_match( '/' . $charsets_string . '/i', $name, $matches );

		// No charset data was found
		if ( empty( $matches[0] ) ) {
			$blog_charset = get_bloginfo( 'charset' );
			$return       = in_array( $default_charset, $charsets ) ? $default_charset : $blog_charset;
		} else {
			// Charset data was found
			$return = esc_attr( $matches[0] );
		}

		unset( $charsets, $charsets_quote, $charsets_string );

		return $return;
	}

	/**
	 * Get the charset from the file name. If file name has no encoding, assume blog charset
	 *
	 * @since 1.0.8
	 *
	 * @return string Default: UTF-8
	 */
	public function getFileCharset() {

		$file = $this->getFileArray();

		$charset = $this->getCharsetFromName( basename( $file['file'] ) );

		return $charset;
	}

	/**
	 * Get the charset for the blog. Currently an alias for get_bloginfo('charset')
	 *
	 * @since 1.0.8
	 *
	 * @return string Default: UTF-8
	 */
	public function getBlogCharset() {
		return get_bloginfo( 'charset' );
	}

	/**
	 * Get the LexerConfig object with character set, delimiter info (CSV/TSV), and whether to ignore header line
	 *
	 * @return LexerConfig
	 */
	private function getLexerConfig() {

		$config = new LexerConfig;

		$file = $this->getFileArray();

		if ( in_array( $file['type'], array( 'text/tab-separated-values', 'tsv', 'text/plain' ) ) ) {
			$config->setDelimiter( "\t" );
		}

		if ( false === $this->_header_only ) {
			$config->setIgnoreHeaderLine( true );
		}

		$config->setFromCharset( $this->getFileCharset() );

		$config->setToCharset( $this->getBlogCharset() );

		do_action( 'gravityview-importer/config', $config );

		return $config;
	}

}