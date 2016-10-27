<?php
/**
 * @global GravityView_Import_Report $this
 */
	$errors = $this->get_error_messages();

	$warnings = $this->getWarnings();

	$added = $this->get_added_messages();

	if( !empty( $added ) || !empty( $errors ) ) { ?>
<div class="gravityview-importer-report">
	<?php

		if ( $errors ) {

			if ( $added ) {
				echo '<div class="two-col">';
			}

			echo '<h3>' . esc_html__( 'Skipped Rows', 'gravityview-importer' ) . '</h3>';

			$error_count_message = _n( __( 'A row was skipped during import.', 'gravityview-importer' ), __( 'There were %d skipped rows during import.', 'gravityview-importer' ), sizeof( $errors ) );

			$message = sprintf( $error_count_message, sizeof( $errors ) );

			echo '<h4 class="subtitle">' . $message . '</h4>';

			do_action( 'gravityview-import/print-ul', $errors, 'error' );

			if ( $added ) {
				echo '</div>';
			}
		}

		if ( $added ) {

			if ( $errors ) {
				echo '<div class="two-col">';
			}

			echo '<h3>' . esc_html__( 'Entries Added', 'gravityview-importer' ) . '</h3>';

			echo '<h4 class="subtitle">' . sprintf( _x( '%d %s been imported.', 'The number of entries that were imported', 'gravityview-importer' ), sizeof( $added ), _n( __( 'entry has', 'gravityview-importer' ), __( 'entries have', 'gravityview-importer' ), sizeof( $added ) ) ) . '</h4>';

			do_action( 'gravityview-import/print-ul', $added );

			if ( $errors ) {
				echo '</div>';
			}
		}

	?>
	</div>
	<div class="hr-divider"></div>
	<?php
	}

	do_action('gravityview-import/report/after', $added, $errors );
