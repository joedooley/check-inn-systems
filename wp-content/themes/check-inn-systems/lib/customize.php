<?php
/**
 * Check Inn Systems
 *
 * This file adds the Customizer additions to the Check Inn Systems Theme.
 *
 * @package Check Inn Systems
 */


/**
 * Get default link color for Customizer.
 *
 * Abstracted here since at least two functions use it.
 *
 * @since 2.2.3
 *
 * @return string Hex color code for link color.
 */
function check_inn_systems_customizer_get_default_link_color() {
	return '#c3251d';
}

/**
 * Get default accent color for Customizer.
 *
 * Abstracted here since at least two functions use it.
 *
 * @since 2.2.3
 *
 * @return string Hex color code for accent color.
 */
function check_inn_systems_customizer_get_default_accent_color() {
	return '#c3251d';
}

add_action( 'customize_register', 'check_inn_systems_customizer_register' );
/**
 * Register settings and controls with the Customizer.
 *
 * @since 2.2.3
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function check_inn_systems_customizer_register() {

	global $wp_customize;

	$wp_customize->add_setting(
		'check_inn_systems_link_color',
		array(
			'default'           => check_inn_systems_customizer_get_default_link_color(),
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'check_inn_systems_link_color',
			array(
				'description' => __( 'Change the default color for linked titles, menu links, post info links and more.', 'check-inn-systems' ),
			    'label'       => __( 'Link Color', 'check-inn-systems' ),
			    'section'     => 'colors',
			    'settings'    => 'check_inn_systems_link_color',
			)
		)
	);

	$wp_customize->add_setting(
		'check_inn_systems_accent_color',
		array(
			'default'           => check_inn_systems_customizer_get_default_accent_color(),
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'check_inn_systems_accent_color',
			array(
				'description' => __( 'Change the default color for button hovers.', 'check-inn-systems' ),
			    'label'       => __( 'Accent Color', 'check-inn-systems' ),
			    'section'     => 'colors',
			    'settings'    => 'check_inn_systems_accent_color',
			)
		)
	);

}
