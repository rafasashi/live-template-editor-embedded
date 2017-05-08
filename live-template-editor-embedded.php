<?php
/*
 * Plugin Name: Live Template Editor Embedded
 * Version: 1.1.0
 * Plugin URI: https://github.com/rafasashi
 * Description: Embedded Live Template Editor.
 * Author: Rafasashi
 * Author URI: https://github.com/rafasashi
 * Requires at least: 4.6
 * Tested up to: 4.7
 *
 * Text Domain: ltple-embedded
 * Domain Path: /lang/
 *
 * GitHub Plugin URI: rafasashi/live-template-editor-embedded
 * GitHub Branch:     master
 *
 * @package WordPress
 * @author Rafasashi
 * @since 1.0.0
 */
 
	/**
	* Add documentation link
	*
	*/
	
	if ( ! defined( 'ABSPATH' ) ) exit;
	
	if(!function_exists('is_dev_env')){
		
		function is_dev_env( $dev_ip = '109.28.69.143' ){ 
			
			if( $_SERVER['REMOTE_ADDR'] == $dev_ip || ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == $dev_ip ) ){
				
				return true;
			}

			return false;		
		}			
	}	
	
	if(!function_exists('ltple_row_meta')){
	
		function ltple_row_meta( $links, $file ){
			
			if ( strpos( $file, basename( __FILE__ ) ) !== false ) {
				
				$new_links = array( '<a href="https://github.com/rafasashi" target="_blank">' . __( 'Documentation', 'cleanlogin' ) . '</a>' );
				$links = array_merge( $links, $new_links );
			}
			return $links;
		}
	}
	
	add_filter('plugin_row_meta', 'ltple_row_meta', 10, 2);
	
	$mode = ( is_dev_env() ? '-dev' : '');
	
	if( $mode == '-dev' ){
		
		ini_set('display_errors', 1);
	}
	
	// Load plugin config
	require_once( 'config.php' );	
	
	// Load plugin functions
	require_once( 'includes'.$mode.'/functions.php' );	
	
	// Load plugin class files

	require_once( 'includes'.$mode.'/class-ltple-embedded.php' );
	require_once( 'includes'.$mode.'/class-ltple-embedded-settings.php' );
	require_once( 'includes'.$mode.'/class-ltple-embedded-object.php' );
		
	// Autoload plugin libraries
	
	$lib = glob( __DIR__ . '/includes'.$mode.'/lib/class-ltple-embedded-*.php');
	
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
		
		if ( is_null( $instance->_dev ) ) {
			
			$instance->_dev = ( is_dev_env() ? '-dev' : '');
		}				
 
		if ( is_null( $instance->settings ) ) {
			
			$instance->settings = LTPLE_Embedded_Settings::instance( $instance );
		}

		return $instance;
	}
	
	// start plugin
	
	if( $mode == '-dev' ){
		
		LTPLE_Embedded('1.1.1');
	}
	else{
		
		LTPLE_Embedded('1.1.0');
	}