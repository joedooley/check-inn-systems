<?php
/**
 * White Label class
 *
 * @package Beaver_Tunnels
 * @since 1.2.0
 */
class Beaver_Tunnels_White_Label {

    public function __construct() {
		add_filter( 'all_plugins', array( $this, 'plugins_page' ) );
    }

	static public function get_branding() {

		if ( ! class_exists('FLBuilderModel') ) {
			return __( 'Beaver Tunnels', 'beaver-tunnels' );
		}

		$fl_builder_branding = FLBuilderModel::get_branding();
		$fl_builder_default  = __( 'Page Builder', 'fl-builder' );

		if ( $fl_builder_branding != $fl_builder_default ) {
			return $fl_builder_branding . ' ' . __( 'Hooks', 'beaver-tunnels' );
		}

		return __( 'Beaver Tunnels', 'beaver-tunnels' );

	}

    public function plugins_page( $plugins ) {

		if ( ! class_exists('FLBuilderModel') ) {
			return $plugins;
		}

		$default  = __( 'Page Builder', 'fl-builder' );
		$branding = FLBuilderModel::get_branding();
		$key	  = plugin_basename( BEAVER_TUNNELS_PLUGIN_FILE );

		if ( isset( $plugins[ $key ] ) && $branding != $default ) {
			$plugins[ $key ]['Name']	   = $this->get_branding();
			$plugins[ $key ]['Title']	   = $this->get_branding();
			$plugins[ $key ]['Description'] = str_replace( 'Beaver Builder', $branding, $plugins[ $key ]['Description'] );
			$plugins[ $key ]['Author']	   = '';
			$plugins[ $key ]['AuthorName'] = '';
			$plugins[ $key ]['PluginURI']  = '';
		}

		return $plugins;

	}

}
new Beaver_Tunnels_White_Label();
