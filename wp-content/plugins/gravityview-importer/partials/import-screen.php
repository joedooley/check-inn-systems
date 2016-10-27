<h3><?php esc_html_e( 'Importer Status:', 'gravityview-importer' ); ?> <span class="gravityview-importer-status processing"><?php esc_html_e('Importing&hellip;', 'gravityview-importer'); ?></span><span class="gravityview-importer-status complete hide-if-js hide-if-no-js"><i class="dashicons dashicons-flag"></i> <?php esc_html_e('Complete', 'gravityview-importer'); ?></span></h3>

<div class="gravityview-importer-console"><p class="no-entries-imported"><?php esc_html_e('No entries have been imported.', 'gravityview-importer'); ?></p></div>

<a href="#" class="button button-small aligncenter gv-importer-hide-console"><?php esc_html_e('Hide Console', 'gravityview-importer'); ?></a>

<?php

do_action( 'gravityview-importer/import');

?>

<div class="hr-divider"></div>