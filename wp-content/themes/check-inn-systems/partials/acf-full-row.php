<?php
/**
 * Default code for a ACF Flexible Content Full
 * Row field
 *
 * @package    SportPort Active
 * @author     Developing Designs - Joe Dooley
 * @link       https://www.developingdesigns.com
 * @copyright  Joe Dooley, Developing Designs
 * @license    GPL-2.0+
 */


$add_heading     = get_sub_field( 'add_section_heading' );
$content_section = get_sub_field( 'content_section' );
$bg_color        = get_sub_field( 'background_color' );
$css_class       = get_sub_field( 'css_class' ); ?>

<section class="row  <?php echo $css_class; ?>" style="background-color: <?php echo $bg_color; ?>;">
	<div class="wrap">

		<?php
		if ( $add_heading ) {
			the_sub_field( 'section_heading' );
		}

		if ( $content_section ) {
			the_sub_field( 'content_section' );
		} ?>

	</div>
</section>
