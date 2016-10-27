<?php

echo '<h3>'.__('No entries were imported.', 'gravityview-importer') . '</h3>';

?>

<h4><?php esc_html_e('Don&rsquo;t give up!', 'gravityview-importer'); ?></h4>

<p>
	<a href="<?php echo esc_url( add_query_arg( array() ) ); ?>" class="button button-primary button-hero"><?php esc_html_e('Return to the Import Configuration', 'gravityview-importer'); ?></a>
	<span class="description">or</span>
	<a href="<?php echo wp_nonce_url( add_query_arg(array()), 'remove-file' ); ?>" class="button button-secondary button-hero"><?php esc_html_e('Start a New Import', 'gravityview-importer'); ?></a>
</p>