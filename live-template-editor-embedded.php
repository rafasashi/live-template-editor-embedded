<?php
/*
 * Plugin Name: Live Template Editor Embedded
 * Version: 1.1.5
 * Plugin URI: https://github.com/rafasashi/live-template-editor-embedded
 * Description: Setup your Live Editor customer key to start importing and editing any template directly from your wordpress installation.
 * Author: Rafasashi
 * Author URI: https://github.com/rafasashi
 * Requires at least: 4.6
 * Tested up to: 4.7
 *
 */
 
	/**
	* Add documentation link
	*
	*/
	
	if ( ! defined( 'ABSPATH' ) ) exit;
	
	if( !defined('LTPLE_EMBEDDED_SLUG') ){

		define('LTPLE_EMBEDDED_SLUG',pathinfo(__FILE__, PATHINFO_FILENAME));
	}

	// Load plugin class files
	
	require_once( 'includes/class-ltple-embedded.php' );
	require_once( 'includes/class-ltple-embedded-settings.php' );
	require_once( 'includes/class-ltple-embedded-object.php' );
		
	// Autoload plugin libraries
	
	$lib = glob( __DIR__ . '/includes/lib/class-*.php');
	
	foreach($lib as $file){
		
		require_once( $file );
	}
	
	/**
	 * Returns the main instance of LTPLE_Embedded to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object LTPLE_Embedded
	 */
	function LTPLE_Embedded ( $version = '1.0.0' ) {
		
		register_activation_hook( __FILE__, array( 'LTPLE_Embedded', 'install' ) );
		
		$instance = LTPLE_Embedded::instance( __FILE__, $version );		
 
		if ( empty( $instance->settings ) ) {
			
			$instance->settings = LTPLE_Embedded_Settings::instance( $instance );
		}

		return $instance;
	}
	
	// start plugin
	
	LTPLE_Embedded( '1.1.0' );
