<?php

class GravityView_Import_Report {

	private $added = array();

	/**
	 * @var WP_Error[]
	 */
	private $warnings = array();

	private $errors = array();

	static $instance;

	private function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {

		if ( ! gravityview_importer()->is_import() ) {
			return;
		}

		add_action( 'gravityview-import/print-ul', array( $this, 'display_print_ul' ), 10, 2 );

		add_action( 'gravityview-importer/end-of-file', array( $this, 'end_of_file' ) );
		add_action( 'gravityview-import/after-import', array( $this, 'display_admin_messages' ) );
		add_action( 'gravityview-import/report/after', array( $this, 'display_after_import' ), 10, 2 );

	}

	/**
	 * Trigger jQuery importer complete action
	 */
	function end_of_file() {
		echo '<script>jQuery(document).trigger("importer-complete");</script>';
	}

	function display_admin_messages() {

		// Hide the settings if there are messages to be shown
		if ( ! empty( $this->errors ) || ! empty( $this->added ) ) {
			add_filter( 'gravityview-import/show-settings', '__return_false' );
		}

		include( gravityview_importer()->get_base_path() . '/partials/report-main.php' );

	}

	/**
	 * Print an unordered list of messages
	 *
	 * @param array $items
	 * @param string $class
	 */
	public function display_print_ul( $items, $class = 'updated' ) {
		include( gravityview_importer()->get_base_path() . '/partials/message-list.php' );
	}

	/**
	 * Print an unordered list of messages
	 *
	 * @param $items
	 * @param string $class
	 */
	public function display_after_import( $added, $errors ) {

		/** @define "$base_path" "./" */
		$base_path = trailingslashit( gravityview_importer()->get_base_path() );

		if ( empty( $added ) && empty( $errors ) ) {
			include( $base_path . 'partials/report-after-empty.php' );
		} else if ( $errors ) {
			include( $base_path . 'partials/report-after-errors.php' );
		} else if ( $added ) {
			include( $base_path . 'partials/report-after-success.php' );
		}

	}

	/**
	 * Add added entries to Gravity Forms Admin Messages
	 *
	 * @see GFCommon::display_admin_message()
	 *
	 * @param array $messages Existing messages, if any
	 *
	 * @return array Errors, with stored errors added
	 */
	public function get_added_messages( $messages = array() ) {

		/**
		 * @var array $added
		 */
		foreach ( $this->added as $added ) {
			$messages[] = $this->get_added_message( $added );
		}

		return $messages;
	}

	function get_added_message( $added ) {

		$entry_link = admin_url( sprintf( 'admin.php?page=gf_entries&amp;view=entry&id=%d&lid=%d', $added['entry']['form_id'], $added['id'] ) );

		$entry_link_text = sprintf( __( 'Entry #%s', 'gravityview-importer' ), $added['id'] );

		if( 'add' === $added['action'] ) {
			$message = __('Created %s from Row %s', 'gravityview-importer');
		} else {
			$message = __('Updated %s from Row %s', 'gravityview-importer');
		}

		$message = sprintf( $message, '<a href="' . $entry_link . '" target="_blank">' . $entry_link_text . '</a>', $added['row'] );

		if ( ! empty( $added['post'] ) ) {
			$post_link      = admin_url( 'post.php?action=edit&amp;post=' . intval( $added['post'] ) );
			$post_link_text = sprintf( __( 'Post #%d', 'gravityview-importer' ), $added['post'] );
			$message .= ' ' . sprintf( _x( 'and %s', 'and Post #', 'gravityview-importer' ), '<a href="' . $post_link . '" target="_blank">' . $post_link_text . '</a>' );
		}

		return $message;
	}

	/**
	 * Add errors to Gravity Forms Admin Messages
	 *
	 * @see GFCommon::display_admin_message()
	 *
	 * @param array $errors Existing errors, if any
	 *
	 * @return array Errors, with stored errors added
	 */
	public function get_error_messages( $errors = array() ) {

		foreach ( $this->errors as $key => $error ) {

			if ( is_array( $error ) ) {
				/** @var WP_Error $e */
				foreach ( $error as $k => $e ) {
					$errors[ $key . '-' . $k ] = $this->get_error_message_output( $e );
				}
			} else {
				/** @var WP_Error $error */
				$errors[ $key ] = $this->get_error_message_output( $error );
			}
		}

		return $errors;
	}

	/**
	 * @param WP_Error $error
	 *
	 * @return string Error message
	 */
	private function get_error_message_output( $error ) {

		$message = $error->get_error_message();

		$data = $error->get_error_data();

		if ( ! empty( $data ) ) {
			$message .= '<br />' . __( 'Additional info:', 'gravityview-importer' ) . ' ';
			$message .= is_string( $data ) ? $data : json_encode( $data, true );
		}

		return $message;
	}

	public static function get_instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * @return WP_Error[]
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @param WP_Error $errors
	 *
	 * @return void
	 */
	public function addError( WP_Error $error, $entry = array(), $line_number = 0, $output_script = true ) {

		if ( $line_number ) {
			if ( ! isset( $this->errors[ $line_number ] ) ) {
				$this->errors[ $line_number ] = array( $error );
			} else {
				$this->errors[ $line_number ][] = $error;
			}
		} else {
			$this->errors[] = $error;
		}


		if ( $output_script ) {
			$this->print_js_message( $error->get_error_message(), 'import-error' );
		}
	}

	private function print_js_message( $message, $css = 'success', $trigger = 'importer-added' ) {

		$data = array(
			'message' => esc_js( strip_tags( $message, '<br /><ul><li>' ) ),
			'css'     => esc_attr( $css )
		);

		?>
		<script>
			<?php
				if( sizeof( $this->added ) === 1 ) {
					echo "jQuery( '.no-entries-imported:visible' ).hide();";
				}
			?>
			jQuery( '<p class="<?php echo $data['css']; ?>"><?php echo $data['message']; ?></p>' ).prependTo( '.gravityview-importer-console' );
		</script>
<?php
#		printf( "<script>jQuery(document).trigger( '%s', '%s' );</script>", $trigger, json_encode( $data ) );

	}

	/**
	 * @param int $int Entry ID
	 * @param array $entry Entry Array
	 * @param int $line_number Number of the current line number (0-index)
	 * @param boolean $output_script Print JS message or not
	 * @param string $action `add` or `update`
	 */
	public function addAdded( $int, $entry, $line_number, $output_script = true, $action = 'add' ) {

		$added = array(
			'id'    => $int,
			'entry' => $entry,
			'row'   => $line_number,
			'post'  => rgget( 'post_id', $entry ),
			'action' => $action,
		);

		$this->added[ $line_number ] = $added;

		if ( $output_script ) {
			$this->print_js_message( $this->get_added_message( $added ) );
		}
	}

	/**
	 * @return array
	 */
	public function getAdded() {
		return $this->added;
	}

	/**
	 * @return int
	 */
	public function getAddedSize() {
		return sizeof( $this->added );
	}

	/**
	 * @return array
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * @param $line
	 *
	 * @return array
	 */
	public function getWarningsAtLine( $line ) {
		$line_warnings = array();

		foreach ( $this->warnings as $warning_details ) {
			if ( $warning_details['line'] === $line ) {
				$line_warnings[] = $warning_details['warning']->get_error_message();
			}
		}

		return $line_warnings;
	}

	/**
	 * @param WP_Error $warning
	 *
	 * @return void
	 */
	public function addWarning( WP_Error $warning, $line_number = 0, $output_script = true ) {

		$this->warnings[] = array(
			'line'    => $line_number,
			'warning' => $warning
		);

		if ( $output_script ) {
			$this->print_js_message( $warning->get_error_message(), 'import-warning' );
		}
	}

}