<?php
/**
 * Display a list of links for each form's Import Entries screen to display on the Import/Export Gravity Forms tab
 *
 * @package gravityview-importer
 * @subpackage partials
 */
?>
<h3><?php _e('Where should entries be imported?', 'gravityview-importer' ); ?></h3>

<p class="subtitle"><?php esc_html_e('Select the Gravity Forms form where you would like the entries to be imported.', 'gravityview-importer' ); ?></p>

<div class="hr-divider"></div>

<ul id="export_form_list">
	<?php
	$forms = RGFormsModel::get_forms( null, 'title' );
	foreach ( $forms as $form ) {

		$link = admin_url( sprintf( 'admin.php?page=gf_edit_forms&amp;view=settings&amp;subview=gravityview-importer&id=%d', $form->id ) );
		$form_name = empty( $form->title ) ? sprintf( __('Untitled Form #%d', 'gravityview-importer'), $form->id ) : $form->title;
		?>
		<li>
			<a href="<?php echo $link; ?>"><?php echo esc_html( $form_name ); ?></a>
		</li>
	<?php
	}
	?>
</ul>