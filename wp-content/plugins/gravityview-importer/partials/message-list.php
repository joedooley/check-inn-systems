<?php
/**
 * @global GravityView_Import_Report $this
 */

// .below-h2 is there to prevent WP from moving the div to the top
?>
<div class="<?php echo esc_attr( $class ); ?> below-h2">
	<ul>
	<?php
		$message_string = '';
		foreach( $items as $line => $item ) {
			$message_string .= '<li>';
			$message_string .= $item;
			if( $warnings = $this->getWarningsAtLine( $line ) ) {
				$message_string .= '<strong>'.__(', with warnings:', 'gravityview-importer').'</strong>';
				$message_string .= '<ul class="import-warning"><li>'.implode('</li><li>', $warnings ).'</li></ul>';
			}
			$message_string .= '</li>';
		}
		echo $message_string;
	?>
	</ul>
</div>