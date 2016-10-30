<?php
/**
 * Default code for a ACF CTA Flexible Content field
 *
 * @package Check Inn Systems
 */


$cta_heading = get_sub_field( 'cta_heading' );
$cta_button  = get_sub_field( 'cta_button_text' );
$cta_url     = get_sub_field( 'cta_button_url' );
$cta_bgcolor = get_sub_field( 'cta_bg_color' );

$bg_color    = get_sub_field( 'background_color' );
$css_class   = get_sub_field( 'css_class' );

?>

<section class="row <?php echo $css_class; ?>" style = "background-color: <?php echo $bg_color; ?>;">
	<div class="wrap">

		<?php

		if ( $cta_heading ) {
			echo '<div class="cta-heading"><h2>' . $cta_heading . '</h2></div>';
		}

		if ( $cta_button ) {
			echo '<div class="cta-button">';
			echo '<a href="' . $cta_url . '" class="button" style="' . $cta_bgcolor . '">' . $cta_button . '</a>';
			echo '</div>';
		} ?>

	</div>
</section>

