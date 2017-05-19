<?php
/*
 * Plugin Name: Live Template Editor
 * Version: 1.1.1
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
	
	$mode = ( ($_SERVER['REMOTE_ADDR'] == $dev_ip || ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] == $dev_ip  ) || ( isset($_GET['debug']) && $_GET['debug'] == '1') ) ? '-dev' : '');
	
	if( $mode == '-dev' ){
		
		ini_set('display_errors', 1);
	}
	
	if( !defined('LTPLE_EMBEDDED_SLUG') ){

		define('LTPLE_EMBEDDED_SLUG',pathinfo(__FILE__, PATHINFO_FILENAME));
	}
	
	require_once( 'updater.php' );
	
	if (is_admin()) { 
	
		$slug = plugin_basename(__FILE__);
		
		$name = pathinfo( $slug, PATHINFO_FILENAME);
	
		new WP_GitHub_Updater(array(
		
			'slug' 					=> $slug, // this is the slug of your plugin
			'proper_folder_name' 	=> $name, // this is the name of the folder your plugin lives in
			'api_url' 				=> 'https://api.github.com/repos/rafasashi/'.$name, // the GitHub API url of your GitHub repo
			'raw_url' 				=> 'https://raw.github.com/rafasashi/'.$name.'/master', // the GitHub raw url of your GitHub repo
			'github_url' 			=> 'https://github.com/rafasashi/'.$name, // the GitHub url of your GitHub repo
			'zip_url' 				=> 'https://github.com/rafasashi/'.$name.'/zipball/master', // the zip url of the GitHub repo
			'sslverify' 			=> true, // whether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
			'requires' 				=> '4.6', // which version of WordPress does your plugin require?
			'tested' 				=> '4.7', // which version of WordPress is your plugin tested up to?
			'readme' 				=> 'README.md', // which file to use as the readme for the version number
			'access_token' 			=> '', // Access private repositories by authorizing under Appearance > GitHub Updates when this example plugin is installed
		));
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
