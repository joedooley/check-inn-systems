<?php
/**
 * Display the sharing field type.
 *
 * @package GravityView_Sharing
 */

	$sharing_class_name = GravityView_View::getInstance()->getCurrentFieldSetting('sharing_service');

	$sharing = '';

	if( !empty( $sharing_class_name ) && class_exists( $sharing_class_name ) ) {
		$sharing = $sharing_class_name::getInstance()->output();
	}

	// No result, or the shortcode didn't process, get outta here.
	if( empty( $sharing ) ) {
		return;
	}

?>
<div class="gv-sharing-container">
	<?php echo $sharing; ?>
</div>
