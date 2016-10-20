<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Beaver_Tunnels_Settings {

    public function __construct() {
		add_action( 'admin_init', array( $this, 'initialize' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( BEAVER_TUNNELS_PLUGIN_FILE ), array( $this, 'action_links' ) );

		if ( Beaver_Tunnels()->is_network_active() ) {
			add_action( 'admin_init', array( $this, 'network_initialize' ) );
			add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
			add_filter( 'network_admin_plugin_action_links_' . plugin_basename( BEAVER_TUNNELS_PLUGIN_FILE ), array( $this, 'action_links' ) );
		}
    }

	public function action_links( $links = array() ) {

		if ( Beaver_Tunnels()->is_network_active() ) {
			$license_data = get_site_option( 'beaver_tunnels_license_data' );
		} else {
			$license_data = get_option( 'beaver_tunnels_license_data' );
		}

		$action_links = array();

		if ( ! is_object( $license_data ) || ! isset( $license_data->license ) || '' === $license_data->license ) {
			if ( Beaver_Tunnels()->is_network_active() && is_network_admin() ) {
				$action_links[] = '<a href="' . network_admin_url( 'settings.php?page=beaver-tunnels' ) . '">' . __('Enter License Key', 'beaver-tunnels') . '</a>';
			} else {
				$action_links[] = '<a href="' . admin_url( 'options-general.php?page=beaver-tunnels' ) . '">' . __('Enter License Key', 'beaver-tunnels') . '</a>';
			}
		} else {
			if ( Beaver_Tunnels()->is_network_active() && is_network_admin() ) {
				$action_links[] = '<a href="' . network_admin_url( 'settings.php?page=beaver-tunnels' ) . '">' . __('Settings', 'beaver-tunnels') . '</a>';
			} else {
				$action_links[] = '<a href="' . admin_url( 'options-general.php?page=beaver-tunnels' ) . '">' . __('Settings', 'beaver-tunnels') . '</a>';
			}
		}

		return array_merge( $action_links, $links );
	}

    public function admin_menu() {

		$branding = Beaver_Tunnels_White_Label::get_branding();

		add_options_page(
			$branding,
			$branding,
			'manage_options',
			'beaver-tunnels',
			array( $this, 'display' ) );

    }

	public function network_admin_menu() {

		$branding = Beaver_Tunnels_White_Label::get_branding();

		add_submenu_page(
			'settings.php',
			$branding,
			$branding,
			'manage_network_options',
			'beaver-tunnels',
			array( $this, 'network_display' ) );

	}

    public function display() {
		$branding = Beaver_Tunnels_White_Label::get_branding();
	    ?>
	    <div class="wrap">
			<h1><?php echo $branding; ?></h1>
			<form method="post" action="options.php">
		    	<table class="form-table">
					<?php
					settings_fields( 'beaver_tunnels' );
					do_settings_sections( 'beaver_tunnels' );
					submit_button();
					?>
				</table>
			</form>
	    </div>
	    <?php
    }

	public function network_display() {

		if ( isset( $_POST['beaver_tunnels'] ) ) {

			if ( ! isset( $_POST['beaver_tunnels-nonce'] ) || ! wp_verify_nonce( $_POST['beaver_tunnels-nonce'], 'beaver_tunnels-nonce' )  ) {
				wp_die('That is not allowed.');
			}

			$beaver_tunnels = $_POST['beaver_tunnels'];

			//use array map function to sanitize option values
        	$beaver_tunnels = array_map( function( $input ) {
				if ( isset( $input['license_key'] ) && 0 == strlen( trim( $input['license_key'] ) ) ) {
					delete_site_option( 'beaver_tunnels_license_data' );
				}
				return $input;
			}, $beaver_tunnels );

			//save option values
        	update_site_option( 'beaver_tunnels', $beaver_tunnels );

        //just assume it all went according to plan
		echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>Settings saved.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        // echo '<div class="updated notice is-dismissable"><p><strong>Settings saved.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';

		}

		$branding = Beaver_Tunnels_White_Label::get_branding();
	    ?>
	    <div class="wrap">
			<h1><?php echo $branding; ?></h1>
			<form method="post" action="settings.php?page=beaver-tunnels">
		    	<table class="form-table">
					<?php
					settings_fields( 'beaver_tunnels_network' );
					do_settings_sections( 'beaver_tunnels_network' );
					submit_button();
					?>
				</table>
			</form>
	    </div>
	    <?php
    }

	public function network_initialize() {

		$branding = Beaver_Tunnels_White_Label::get_branding();

		$beaver_tunnels = get_site_option( 'beaver_tunnels' );
		// If the option does not exist, then add it
		if ( false === $beaver_tunnels ) {
			add_site_option( 'beaver_tunnels', array() );
		}

		// First, we register a section. This is necessary since all future options must belong to one.
		add_settings_section(
			'beaver_tunnels_license_section',
			__( 'License Key', 'beaver-tunnels' ),
			array( $this, 'license_section_callback' ),
			'beaver_tunnels_network'
		);

		add_settings_field(
			'license_key',
			'<strong>' . __('License Key', 'beaver-tunnels') . '</strong>',
			array( $this, 'license_key_callback' ),
			'beaver_tunnels_network',
			'beaver_tunnels_license_section',
			array(
				'field_id'  => 'license_key',
				'page_id'   => 'beaver_tunnels',
				'size'      => 'regular',
			)
		);

		add_settings_section(
    		'beaver_tunnels_options_section',
    		__( 'Options', 'beaver-tunnels' ),
    		array( $this, 'options_section_callback' ),
    		'beaver_tunnels_network'
    	);

		add_settings_field(
    		'remove_data',
    		'<strong>' . __('Remove Data on Uninstall?', 'beaver-tunnels') . '</strong>',
    		array( $this, 'checkbox_callback' ),
    		'beaver_tunnels_network',
    		'beaver_tunnels_options_section',
    		array(
    			'field_id'  => 'remove_data',
    			'page_id'   => 'beaver_tunnels',
    			'label'     => sprintf( '%1s <strong>%2s</strong> %3s', __('Would you like', 'beaver-tunnels'), $branding, __('to completely remove all of its data when the plugin is deleted?', 'beaver-tunnels') ),
    		)
    	);

		// Finally, we register the fields with WordPress
    	register_setting(
    		'beaver_tunnels_network',			// The group name of the settings being registered
    		'beaver_tunnels',			// The name of the set of options being registered
    		array( $this, 'sanitize_callback' )	// The name of the function responsible for validating the fields
    	);

	}

    public function initialize() {

		$branding = Beaver_Tunnels_White_Label::get_branding();

		$beaver_tunnels = get_option( 'beaver_tunnels' );
		// If the option does not exist, then add it
		if ( false === $beaver_tunnels ) {
			add_option( 'beaver_tunnels' );
		}

		if ( ! Beaver_Tunnels()->is_network_active() ) :

			// First, we register a section. This is necessary since all future options must belong to one.
	    	add_settings_section(
	    		'beaver_tunnels_license_section',
	    		__( 'License Key', 'beaver-tunnels' ),
	    		array( $this, 'license_section_callback' ),
	    		'beaver_tunnels'
	    	);

			add_settings_field(
	    		'license_key',
	    		'<strong>' . __('License Key', 'beaver-tunnels') . '</strong>',
	    		array( $this, 'license_key_callback' ),
	    		'beaver_tunnels',
	    		'beaver_tunnels_license_section',
	    		array(
	    			'field_id'  => 'license_key',
	    			'page_id'   => 'beaver_tunnels',
	                'size'      => 'regular',
	    		)
	    	);

		endif;

		add_settings_section(
    		'beaver_tunnels_options_section',
    		__( 'Options', 'beaver-tunnels' ),
    		array( $this, 'options_section_callback' ),
    		'beaver_tunnels'
    	);

		add_settings_field(
    		'hide_hook_guide',
    		'<strong>' . __('Hide the Hook Guide?', 'beaver-tunnels') . '</strong>',
    		array( $this, 'checkbox_callback' ),
    		'beaver_tunnels',
    		'beaver_tunnels_options_section',
    		array(
    			'field_id'  => 'hide_hook_guide',
    			'page_id'   => 'beaver_tunnels',
    			'label'     => __('Hide the Hook Guide in the Admin bar menu.', 'beaver-tunnels'),
    		)
    	);

		$page_builder_branding = 'Page Builder';
		if ( class_exists('FLBuilderModel') ) {
			$page_builder_branding = FLBuilderModel::get_branding();
		}

		add_settings_field(
    		'disable_template_override',
    		'<strong>' . __('Disable Template Override?', 'beaver-tunnels') . '</strong>',
    		array( $this, 'checkbox_callback' ),
    		'beaver_tunnels',
    		'beaver_tunnels_options_section',
    		array(
    			'field_id'  => 'disable_template_override',
    			'page_id'   => 'beaver_tunnels',
				'label'		=> sprintf( '%1s %2s %3s.', __('Disable the minimal page template when viewing saved', 'beaver-tunnels'), $page_builder_branding, __( 'Templates', 'beaver-tunnels' ) ),
    		)
    	);

		if ( ! Beaver_Tunnels()->is_network_active() ) :

			add_settings_field(
	    		'remove_data',
	    		'<strong>' . __('Remove Data on Uninstall?', 'beaver-tunnels') . '</strong>',
	    		array( $this, 'checkbox_callback' ),
	    		'beaver_tunnels',
	    		'beaver_tunnels_options_section',
	    		array(
	    			'field_id'  => 'remove_data',
	    			'page_id'   => 'beaver_tunnels',
	    			'label'     => sprintf( '%1s <strong>%2s</strong> %3s', __('Would you like', 'beaver-tunnels'), $branding, __('to completely remove all of its data when the plugin is deleted?', 'beaver-tunnels') ),
	    		)
	    	);

		endif;

        // Finally, we register the fields with WordPress
    	register_setting(
    		'beaver_tunnels',			// The group name of the settings being registered
    		'beaver_tunnels',			// The name of the set of options being registered
    		array( $this, 'sanitize_callback' )	// The name of the function responsible for validating the fields
    	);

    }

    public function license_section_callback() {
		$branding = Beaver_Tunnels_White_Label::get_branding();
		$default = __( 'Beaver Tunnels', 'beaver-tunnels' );
		if ( $branding != $default ) {
			return;
		}
		echo '<p>' . sprintf('<a href="https://beavertunnels.com/account/" target="_blank">%1s</a> %2s', __('Log in to your account', 'beaver-tunnels'), __('to lookup your license.', 'beaver-tunnels') ) . '</p>';
	}

	public function options_section_callback() {
        echo sprintf('<p>%1s %2s.</p>', __('Here you will find various settings for', 'beaver-tunnels'), Beaver_Tunnels_White_Label::get_branding() );
    }

    public function sanitize_callback( $input ) {
        // Define all of the variables that we'll be using
    	$output = array();

    	// Loop through each of the incoming options
    	foreach ( $input as $key => $value ) {

    		// Check to see if the current option has a value. If so, process it.
    		if ( isset( $input[$key] ) ) {

				if ( 'license_key' == $key && 0 == strlen( trim( $value ) ) ) {
					delete_option( 'beaver_tunnels_license_data' );
				}

    			// Strip all HTML and PHP tags and properly handle quoted strings
    			$output[$key] = strip_tags( stripslashes( $input[$key] ) );

    		}

    	}

    	// Return the array
    	return $output;

    }

	/**
     * License Key Field
     *
     * @package    Beaver_Tunnels
     * @since      1.0.0
     *
     * @param	array	$args	Arguments to pass to the function. (See below).
	 *
	 * string	$args[ 'field_id' ]
	 * string	$args[ 'page_id' ]
	 * string	$args[ 'label' ]
     *
     * @return	string	HTML to display the field.
     */
    public function license_key_callback( $args ) {

        // Set the defaults
		$defaults = array(
			'field_id'		=> NULL,
			'page_id'		=> NULL,
			'label'      	=> NULL,
		);

		// Parse the arguments
		$args = wp_parse_args( $args, $defaults );

		if ( Beaver_Tunnels()->is_network_active() ) {
			$options = get_site_option( $args['page_id'] );
			$license_data = get_site_option( 'beaver_tunnels_license_data' );
		} else {
			$options = get_option( $args['page_id'] );
			$license_data = get_option( 'beaver_tunnels_license_data' );
		}

        // Start the output buffer
        ob_start();
        ?>
		<?php wp_nonce_field( 'beaver_tunnels-nonce', 'beaver_tunnels-nonce' ); ?>
		<input type="password" id="<?php echo esc_attr( $args['page_id'] ); ?>[<?php echo esc_attr( $args['field_id'] ); ?>]" name="<?php echo esc_attr( $args['page_id'] ); ?>[<?php echo esc_attr( $args['field_id'] ); ?>]" value="<?php echo ( isset( $options[ $args['field_id'] ] ) ? $options[ $args['field_id'] ] : '' ); ?>" class="regular-text" />
		<?php if ( is_object( $license_data ) && isset( $license_data->license ) && 'valid' === $license_data->license ) : ?>
			<input type="submit" class="button-secondary" name="<?php echo esc_attr( 'beaver_tunnels_license_deactivate' ); ?>" value="<?php echo esc_attr( __('Deactivate License', 'beaver-tunnels') ); ?>">
		<?php endif; ?>
        <?php if ( $args['label'] != '' ) : ?>
            <p class="description"><?php echo $args['label']; ?></p>
        <?php endif; ?>
		<?php if ( true === false && is_object( $license_data ) && isset( $license_data->license ) && isset( $license_data->expires ) && isset( $license_data->customer_email ) ) : ?>
		<br /><br />
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="padding: 8px 10px;"><?php _e( 'License', 'beaver-tunnels' ); ?></th>
					<th style="padding: 8px 10px;"><?php _e( 'Expires', 'beaver-tunnels' ); ?></th>
					<th style="padding: 8px 10px;"><?php _e( 'Customer Email', 'beaver-tunnels' ); ?></th>
				</tr>
				<tr>
					<td><?php echo ucwords( $license_data->license ); ?></td>
					<td><?php echo ucwords( $license_data->expires ); ?></td>
					<td><?php echo $license_data->customer_email; ?></td>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
        <?php
		endif;
    	// Print the output
        echo ob_get_clean();

    } // license_key_callback()

	/**
     * Checkbox Input Field
     *
     * @package    CCBPress_Core
     * @since      1.0.0
     *
     * @param	array	$args	Arguments to pass to the function. (See below).
	 *
	 * string	$args[ 'field_id' ]
	 * string	$args[ 'page_id' ]
	 * string	$args[ 'label' ]
     *
     * @return	string	HTML to display the field.
     */
    public function checkbox_callback( $args ) {

        // Set the defaults
		$defaults = array(
			'field_id'		=> NULL,
			'page_id'		=> NULL,
			'value'			=> '1',
			'label'      	=> NULL,
            'before'        => NULL,
            'after'         => NULL,
		);

		// Parse the arguments
		$args = wp_parse_args( $args, $defaults );

        // Get the saved values from WordPress
		if ( Beaver_Tunnels()->is_network_active() ) {
			$options = get_site_option( $args['page_id'] );
		} else {
			$options = get_option( $args['page_id'] );
		}

        // Start the output buffer
        ob_start();
        ?>
        <?php echo $args['before']; ?>
        <input type="checkbox" id="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $args['page_id'] ); ?>[<?php echo esc_attr( $args['field_id'] ); ?>]" value="<?php echo esc_attr( $args['value'] ); ?>" <?php isset( $options[ $args['field_id'] ] ) ? checked( $options[ $args['field_id'] ] ) : '' ?>/>
		<?php if ( $args['label'] != '' ) : ?>
            <label for="<?php echo esc_attr( $args['field_id'] ); ?>" class="description"><?php echo $args['label']; ?></label>
        <?php endif; ?>
		<?php echo $args['after']; ?>

        <?php
    	// Print the output
        echo ob_get_clean();

    } // input_callback()

	/**
	 * Text
	 *
	 * @package    Beaver_Tunnels
	 * @since      1.0.0
	 *
	 * @param	array	$args	Arguments to pass to the function. (See below).
	 *
	 * string	$args[ 'header_type' ]
	 * string	$args[ 'title' ]
	 * string	$args[ 'content' ]
	 *
	 * @return	string	HTML to display the field.
	 */

	public function text_callback( $args ) {

		// Set the defaults
		$defaults = array(
			'header'	=> 'h2',
			'title'		=> NULL,
			'content'	=> NULL,
		);

		// Parse the arguments
		$args = wp_parse_args( $args, $defaults );

		ob_start();
		// Check that the title and header_type are not blank
		if ( ! is_null( $args['title'] ) ) {
			echo '<' . $args['header'] . '>' . $args['title'] . '</' . $args['header'] . '>';
	    }

	    // Check that the content is not blank
		if ( ! is_null ( $args['content'] ) ) {
			echo $args['content'];
	    }

		// Print the output
	    echo ob_get_clean();

	} // text_callback()

}
new Beaver_Tunnels_Settings();
