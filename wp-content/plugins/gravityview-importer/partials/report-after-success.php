<h3><?php esc_html_e('The import processed without errors.', 'gravityview-importer'); ?></h3>

<p>
	<a href="<?php echo esc_url( add_query_arg( array() ) ); ?>" class="button button-primary button-large gv-complete-import"><?php esc_html_e('Start a New Import', 'gravityview-importer'); ?></a>
	<span class="description">or</span>
	<a href="<?php echo admin_url( 'admin.php?page=gf_entries&amp;id='. GV_Import_Entries_Addon::get_instance()->get_form_id() ); ?>" class="button button-secondary gv-complete-import"><?php esc_html_e('I&rsquo;m done - Show Me the Entries!', 'gravityview-importer'); ?></a>
</p>