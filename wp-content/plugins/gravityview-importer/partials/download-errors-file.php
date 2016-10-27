<?php
/**
 * @global GravityView_Entry_Exporter $this
 */
?>

<h2><?php esc_html_e('A file has been generated with the error-generating rows.', 'gravityview-importer'); ?></h2>

<ul class="ul-square large">
	<li>Download the file</li>
	<li>Edit the file and fix any issues</li>
	<li>Import the edited file</li>
</ul>

<a href="<?php echo esc_url( $this->download_url ); ?>" class="button button-large button-primary"><?php echo sprintf( __('Download %s', 'gravityview-importer'), $this->get_filename() ); ?></a>

<div class="hr-divider"></div>