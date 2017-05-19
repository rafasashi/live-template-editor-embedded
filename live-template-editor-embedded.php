<?php
/*
 * Plugin Name: Live Template Editor
 * Version: 1.1.3
 * Plugin URI: https://ltple.recuweb.com
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
	
	$dev_ip = '';
	$dev_ip = '109.28.69.143';

	$mode = ( ( ($_SERVER['REMOTE_ADDR'] == $dev_ip || ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == $dev_ip  ) || ( isset($_GET['debug']) && $_GET['debug'] == '1') ) && is_dir('includes-dev') ) ? '-dev' : '');
	
	if( $mode == '-dev' ){
		
		ini_set('display_errors', 1);
	}
	
	if( !defined('LTPLE_EMBEDDED_SLUG') ){

		define('LTPLE_EMBEDDED_SLUG',pathinfo(__FILE__, PATHINFO_FILENAME));
	}

	// Load plugin class files
	
	require_once( 'includes'.$mode.'/class-ltple-embedded.php' );
	require_once( 'includes'.$mode.'/class-ltple-embedded-settings.php' );
	require_once( 'includes'.$mode.'/class-ltple-embedded-object.php' );
		
	// Autoload plugin libraries
	
	$lib = glob( __DIR__ . '/includes'.$mode.'/lib/class-*.php');
	
	foreach($lib as $file){
		
		require_once( $file );
	}
	
	/**
	 * Returns the main instance of LTPLE_Embedded to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object LTPLE_Embedded
	 */
	function LTPLE_Embedded ( $version = '1.0.0', $mode = '' ) {
		
		register_activation_hook( __FILE__, array( 'LTPLE_Embedded', 'install' ) );
		
		$instance = LTPLE_Embedded::instance( __FILE__, $version );
		
		if ( is_null( $instance->_dev ) ) {
			
			$instance->_dev = $mode;
		}				
 
		if ( is_null( $instance->settings ) ) {
			
			$instance->settings = LTPLE_Embedded_Settings::instance( $instance );
		}

		return $instance;
	}
	
	// start plugin
	
	if( $mode == '-dev' ){
		
		LTPLE_Embedded( '1.1.1', $mode );
	}
	else{
		
		LTPLE_Embedded( '1.1.0', $mode );
	}
