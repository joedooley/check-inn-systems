<?php
/*
Template Name: Content Only
*/

// Enqueue Genesis Framework Stylesheet
if ( function_exists('genesis_enqueue_main_stylesheet') ) {
	genesis_enqueue_main_stylesheet();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<title><?php bloginfo('name'); ?> | <?php is_home() ? bloginfo('description') : wp_title(''); ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
		<?php
		wp_head();
		// Enqueue Beaver Builder Theme Stylesheets
		if ( class_exists('FLTheme') ) {
			FLTheme::head();
		}
		?>
	</head>
	<body <?php body_class(); ?>>
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) : the_post();
				the_content();
			endwhile;
		endif;
		wp_footer();
		?>
	</body>
</html>
