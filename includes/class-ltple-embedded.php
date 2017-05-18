<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_Embedded {

	/**
	 * The single instance of LTPLE_Embedded.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;
	
	public $_dev = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;
	public $_base;
	
	public $_time;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	public $user;
	public $layer;
	public $message;
	public $dialog;
	public $key;
	public $data;
	
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	 
	public function __construct ( $file = '', $version = '1.0.0' ) {
		
		$this->_version = $version;
		$this->_token 	= 'ltple';
		$this->_base 	= 'ltple_';				
		$this->dialog 	= new stdClass();
		$this->urls 	= new stdClass();
		
		if( isset($_GET['_']) && is_numeric($_GET['_']) ){
			
			$this->_time = intval($_GET['_']);
		}
		else{
			
			$this->_time = time();
		}

		$this->message = '';
		
		// Load plugin environment variables
		$this->file 		= $file;
		$this->dir 			= dirname( $this->file );
		$this->views   		= trailingslashit( $this->dir ) . 'views';
		$this->vendor  		= WP_CONTENT_DIR . '/vendor';
		$this->assets_dir 	= trailingslashit( $this->dir ) . 'assets';
		$this->assets_url 	= esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		
		//$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->script_suffix = '';
		
		register_activation_hook( $this->file, array( $this, 'install' ) );
		
		// Handle localisation
		$this->load_plugin_textdomain();

		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	
		$this->request 	= new LTPLE_Embedded_Request( $this );
		
		// Load API for generic admin functions
		
		$this->admin 	= new LTPLE_Embedded_Admin_API( $this );
		
		// get embedded key
		
		$keys = get_option( $this->_base . 'embedded_key', array());
		
		$keys = explode('_', $keys);
		
		// set embedded user key
		
		if(!empty($keys[1])){
		
			$this->key 	= $keys[1];
		}		

		// get urls
		
		$this->urls->current 	= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$this->urls->home 		= home_url();
		$this->urls->data 		= ( !empty($keys[2]) ? $this->ltple_decrypt_str($keys[2],$this->_base) : '' );			

		if( filter_var($this->urls->data, FILTER_VALIDATE_URL) != FALSE){
			
			$this->data = $this->get_embedded_data();
			
			if( filter_var($this->data->editor_url, FILTER_VALIDATE_URL) != FALSE ){
				
				$this->urls->editor = $this->data->editor_url;

				if( !defined('LTPLE_EMBEDDED_PREFIX') ){

					define('LTPLE_EMBEDDED_PREFIX' , $this->data->prefix );
				}				
				
				if( !defined('LTPLE_EMBEDDED_EDITOR_URL') ){

					define('LTPLE_EMBEDDED_EDITOR_URL' , $this->urls->editor );
				}

				if( !defined('LTPLE_EMBEDDED_SHORT') ){

					define('LTPLE_EMBEDDED_SHORT' , $this->data->short_title );
				}

				if( !defined('LTPLE_EMBEDDED_TITLE') ){

					define('LTPLE_EMBEDDED_TITLE' , $this->data->long_title );
				}

				if( !defined('LTPLE_EMBEDDED_DESCRIPTION') ){

					define('LTPLE_EMBEDDED_DESCRIPTION' , $this->data->description );
				}				
				
				add_action( 'add_meta_boxes', function(){

					global $post;	
				
					if( in_array( $post->post_type, $this->settings->options->postTypes ) ){

						$this->admin->add_meta_box (
						
							'default_layer_id',
							__( LTPLE_EMBEDDED_SHORT, LTPLE_EMBEDDED_SLUG ), 
							array($post->post_type),
							'advanced'
						);				
						
						if( in_array( $post->post_type, $this->settings->options->postTypes ) ){
						
							// get default layer id
							
							$post->layer_id = intval(get_post_meta( $post->ID, 'defaultLayerId', true));
							
							if( $post->layer_id == 0 ){
								
								return;
							}
							else{
								
								remove_post_type_support($post->post_type, 'editor');
							}
						}
					}
				});		
				
				if( is_admin() ) {		
					
					add_action( 'init', array( $this, 'init_backend' ));			
				}
				else{
					
					add_action( 'init', array( $this, 'init_frontend' ));
				}
			}
		}
		
		if( !defined('LTPLE_EMBEDDED_SHORT') ){

			define('LTPLE_EMBEDDED_SHORT' , 'Live Editor' );
		}
		
		if( !defined('LTPLE_EMBEDDED_DESCRIPTION') ){

			define('LTPLE_EMBEDDED_DESCRIPTION' , 'Use your Customer Key to activate the plugin' );
		}	
		
	} // End __construct ()

	public function get_remote_data( $url, $transient='', $flush = false ){

		$result = get_transient( $transient );

		if( empty( $result ) || $flush === true ) {
			
			$response = wp_remote_get(  $url );
			
			if( is_wp_error( $response ) ) {
				return array();
			}

			$result = json_decode( wp_remote_retrieve_body( $response ) );

			if( empty( $result ) ) {
				
				return array();
			}
			
			if( !empty($transient) ){
				
				set_transient( $transient, $result, HOUR_IN_SECONDS );
			}
		}

		return $result;	
	}
	
	public function get_embedded_data( $version = '1.0' ){

		return $this->get_remote_data( $this->urls->data,'embedded_data'.$version );	
	}	
	
	private function ltple_get_secret_iv(){
		
		//$secret_iv = md5( $this->user_agent . $this->user_ip );
		//$secret_iv = md5( $this->user_ip );
		$secret_iv = md5( 'another-secret' );	

		return $secret_iv;
	}	
	
	private function ltple_encrypt_str( $string, $secret_key = '' ){
		
		$output = false;

		$encrypt_method = "AES-256-CBC";
		
		if( empty($secret_key) ){
			
			$secret_key = md5( $this->embedded->key );
		}
		
		$secret_iv = $this->ltple_get_secret_iv();
		
		// hash
		$key = hash('sha256', $secret_key);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', $secret_iv), 0, 16);

		$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
		$output = $this->base64_urlencode($output);

		return $output;
	}
	
	private function ltple_decrypt_str($string, $secret_key=''){
		
		$output = false;

		$encrypt_method = "AES-256-CBC";
		
		if( empty($secret_key) ){
			
			$secret_key = md5( $this->embedded->key );
		
		}
		
		$secret_iv = $this->ltple_get_secret_iv();

		// hash
		$key = hash( 'sha256', $secret_key);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr( hash( 'sha256', $secret_iv ), 0, 16);

		$output = openssl_decrypt($this->base64_urldecode($string), $encrypt_method, $key, 0, $iv);

		return $output;
	}
	
	public function ltple_encrypt_uri($uri,$len=250,$separator='/'){
		
		$uri = wordwrap($this->ltple_encrypt_str($uri),$len,$separator,true);
		
		return $uri;
	}
	
	public function ltple_decrypt_uri($uri,$separator='/'){
		
		$uri = $this->ltple_decrypt_str(str_replace($separator,'',$uri));
		
		return $uri;
	}
	
	public function base64_urlencode($inputStr=''){

		return strtr(base64_encode($inputStr), '+/=', '-_,');
	}

	public function base64_urldecode($inputStr=''){

		return base64_decode(strtr($inputStr, '-_,', '+/='));
	}
	
	public function init_frontend(){
		
		// Load frontend JS & CSS
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		//add_action( 'wp_head', array( $this, 'get_header') );

		//add_action( 'wp_footer', array( $this, 'get_footer') );				

		// Custom default layer template

		add_filter('template_include', array( $this, 'editor_templates'), 1 );
	
		//get current user

		$this->user = wp_get_current_user();
		
		$this->user->loggedin = is_user_logged_in();		
		
		if($this->user->loggedin){
		
			// get is admin
			
			$this->user->is_admin = current_user_can( 'administrator', $this->user->ID );
		}
	}	
	
	public function init_backend(){	

		// Load admin JS & CSS
		
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
		
		//get current user
		
		$this->user = wp_get_current_user();
		
		// get is admin
		
		$this->user->is_admin = current_user_can( 'administrator', $this->user->ID );

		// register layer fields
		
		if( !empty($this->settings->options->postTypes) ){
			
			foreach( $this->settings->options->postTypes as $post_type ){

				add_filter( $post_type . '_custom_fields', array( $this, 'get_user_layer_fields' ));
			}
		}
		
		if( strpos($_SERVER['SCRIPT_NAME'],'post.php') > 0 && !empty($_GET['action']) && !empty($_GET['post']) ){
			
			$layer_id = intval($_GET['post']);
			
			if( $layer_id > 0 && $_GET['action'] == 'edit' ){
				
				$defaultLayerId = 0;
				$userLayerId 	= 0;
				
				if(!empty($_POST['defaultLayerId']) && is_numeric($_POST['defaultLayerId'])){

					// update default Layer Id
					
					$defaultLayerId = intval($_POST['defaultLayerId']);
					
					update_post_meta($layer_id, 'defaultLayerId', $defaultLayerId);
					
					update_post_meta($layer_id, 'userLayerId', '');
				}
				elseif( !empty($_GET['uli']) && is_numeric($_GET['uli']) && !empty($_GET['ulk']) && !empty($_GET['ult']) && $_GET['ulk'] == md5('userLayerId'.$_GET['uli'].$_GET['ult']) ){
					
					// update user Layer title

					wp_update_post( array(
					
						'ID'           => $layer_id,
						'post_title'   => $_GET['ult'],
					));					
					
					// update user Layer Id
					
					$userLayerId = intval($_GET['uli']);
					
					update_post_meta($layer_id, 'userLayerId', $userLayerId);
				}
				else{
					
					//$defaultLayerId = intval(get_post_meta( $layer_id, 'defaultLayerId', true));
					//$userLayerId 	= intval(get_post_meta( $layer_id, 'userLayerId', true));					
				}

				if( $defaultLayerId > 0 || $userLayerId > 0 ){
				
					// redirect to edior url
					
					$layer_url = add_query_arg( array(
						
						LTPLE_EMBEDDED_PREFIX . 'edit' => '',
					
					), get_permalink($layer_id) );
					
					wp_redirect($layer_url);
					echo 'Redirecting editor...';
					exit;
				}				
			}
		}
	}
	
	public function editor_templates( $template_path ){
		
		global $post;

		if($this->user->loggedin && isset($_GET[LTPLE_EMBEDDED_PREFIX . 'edit'])){		

			if( empty($post->ID) && !empty($_GET['p']) && !empty($_GET['post_type']) ){
				
				// insert draft
				
				$post_id = wp_insert_post(array(
					
					'ID'    		=> $_GET['p'],
					'post_title'    => '',
					'post_content'  => '',
					'post_type'  	=> $_GET['post_type'],
					'post_status'   => 'draft',
					'post_author'   => $this->user->ID,
				));
				
				$post = get_post($post_id);
			}
		
			if( current_user_can( 'edit_post', $post->ID ) ){
				
				// get user layer id
					
				$this->userLayerId = intval(get_post_meta( $post->ID, 'userLayerId', true ));
				
				// get default layer id
				
				$this->defaultLayerId = intval(get_post_meta( $post->ID, 'defaultLayerId', true ));
				
				// get embedded url
			
				$this->embedded_url = add_query_arg( array(
				
					'uri' 		=> ( $this->userLayerId > 0 ? $this->userLayerId : $this->defaultLayerId ),
					'le' 		=> urlencode( $this->urls->home . '/?p=' . $post->ID ),
					'title' 	=> urlencode( $post->post_title ),
					'key' 		=> $this->key,
					'output' 	=> 'embedded',
					
				), LTPLE_EMBEDDED_EDITOR_URL );
				
				$template_path = $this->views . $this->_dev . '/editor.php';
			}
		}
		elseif( is_single() || is_page() ){

			// get user layer id
					
			$this->userLayerId = intval(get_post_meta( $post->ID, 'userLayerId', true ));
							
			if( $this->userLayerId > 0 ){

				// get layer content 
				
				$resourceUrl = add_query_arg( array(
				
					'api' 	=> 'layer/show',
					'uid' 	=> $this->userLayerId,
					'debug' => ( !empty($this->_dev) ? '1' : ''),
					
				), LTPLE_EMBEDDED_EDITOR_URL );	
				
				$ch = curl_init($resourceUrl);
				
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
			
				// Turn off the server and peer verification (TrustManager Concept).
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; LTPLE/1.0; +' . $this->urls->home . ')');
				
				$result = curl_exec($ch);
				
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
				curl_close($ch);				

				if( $httpcode < 400 && !empty($result) ){
					
					// output layer content

					echo $result;
					exit;
				}
			}
		}
	
		return $template_path;
	}

	public function get_header(){

	}

	public function get_footer(){

	}
	
	public function get_editor_shortcode(){
		
		if($this->user->loggedin){		

			include( $this->views . $this->_dev .'/navbar.php' );	

			include( $this->views . $this->_dev .'/editor.php' );
		}
	}
	
	public function get_user_layer_fields(){
				
		$fields=[];
		
		/*
		$fields[]=array(
		
			"metabox" =>
			
				array('name'=>"layer-css"),
				'id'=>"layerCss",
				'label'=>"",
				'type'=>'textarea',
				'placeholder'=>"Internal CSS style sheet",
				'description'=>'<i>without '.htmlentities('<style></style>').'</i>'
		);
		
		$fields[]=array(
		
			"metabox" =>
			
				array('name'=>"layer-js"),
				'id'=>"layerJs",
				'label'=>"",
				'type'=>'textarea',
				'placeholder'=>"Additional Javascript",
				'description'=>'<i>without '.htmlentities('<script></script>').'</i>'
		);
		*/

		$fields[]=array(
			"metabox" =>
			
				array('name'=>"default_layer_id"),
				'id'=>"defaultLayerId",
				'label'=>"",
				'type'=>'edit_layer',
				'placeholder'=>"",
				'description'=>''
		);
		
		return $fields;
	}	
	
	public function update_user_layer(){	

	
	}
	
	public static function get_absolute_url($u, $source){
		
		$parse = parse_url($source);
		
		if( !empty($u) && $u[0] != '#' && parse_url($u, PHP_URL_SCHEME) == ''){
		
			if( !empty($u[1]) && $u[0].$u[1] == '//'){

				$u =  $parse['scheme'].'://'.substr($u, 2);
			}
			elseif( $u[0] == '/' ){
				
				$u = $parse['scheme'].'://'.$parse['host']. $u;
			}
			elseif( !empty($u[1]) && $u[0].$u[1] == './'){
				
				$u = dirname($source) . substr($u, 2);
			}
			elseif( !empty($u[1]) && !empty($u[2]) && $u[0].$u[1].$u[2] == '../'){
				
				$u = dirname(dirname($source)) . substr($u, 2);
			}
			elseif( substr($source, -1) == '/' ){
				
				$u = $source . $u;
			}
			else{
				
				$u = dirname($source) . '/' . $u;
			}
		}
		
		if( strpos($u,'#') ){
		
			$u = strstr($u, '#', true);
		}

		return $u;		
	}
	
	function in_array_field($needle, $needle_field, $haystack, $strict = false) { 

		if(!empty($haystack)){
	
			if ($strict) { 
				foreach ($haystack as $item) 
					if (isset($item->$needle_field) && $item->$needle_field === $needle) 
						return true; 
			} 
			else { 
				foreach ($haystack as $item) 
					if (isset($item->$needle_field) && $item->$needle_field == $needle) 
						return true; 
			}
		}
		
		return false; 
	}
	
	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new LTPLE_Embedded_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new LTPLE_Embedded_Taxonomy( $this, $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {

		wp_register_style( $this->_token . '-jquery-ui', esc_url( $this->assets_url ) . 'css/jquery-ui.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-jquery-ui' );		
	
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );

	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		
		//wp_enqueue_script('jquery-ui-dialog');
		
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );

	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		

		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
		
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		
		//wp_enqueue_script('jquery-ui-sortable');
		
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
		
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( LTPLE_EMBEDDED_SLUG, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
	    
		$domain = LTPLE_EMBEDDED_SLUG;

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Main LTPLE_Embedded Instance
	 *
	 * Ensures only one instance of LTPLE_Embedded is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see LTPLE_Embedded()
	 * @return Main LTPLE_Embedded instance
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	}

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public static function install() {
		
		// store version number
		
		//$this->_log_version_number();
	}

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		
		update_option( $this->_token . '_version', $this->_version );
	}
}