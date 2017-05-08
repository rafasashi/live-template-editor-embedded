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
	
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	 
	public function __construct ( $file = '', $version = '1.0.0' ) {
		
		$this->_version = $version;
		$this->_token 	= LTPLE_EMBEDDED_TOKEN;
		$this->_base 	= LTPLE_EMBEDDED_PREFIX;
		$this->dialog 	= new stdClass();	
		
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
	
		$this->request 		= new LTPLE_Embedded_Request( $this );
		$this->urls 		= new LTPLE_Embedded_Urls( $this );
		
		// Load API for generic admin functions
		
		$this->admin 	= new LTPLE_Embedded_Admin_API( $this );

		add_action( 'add_meta_boxes', function(){

			global $post;
			
			if( in_array( $post->post_type, $this->settings->options->postTypes ) ){

				$this->admin->add_meta_box (
				
					'default_layer_id',
					__( LTPLE_EMBEDDED_TITLE, $this->settings->plugin->slug ), 
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
				
				/*
				$this->admin->add_meta_box (
					
					'layer-css',
					__( 'Layer CSS', $this->settings->plugin->slug ), 
					array($post->post_type),
					'advanced'
				);
				
				$this->admin->add_meta_box (
					
					'layer-js',
					__( 'Layer Javascript', $this->settings->plugin->slug ), 
					array($post->post_type),
					'advanced'
				);
				*/
			}
		});		
		
		if( is_admin() ) {		
			
			add_action( 'init', array( $this, 'init_backend' ));			
		}
		else{
			
			add_action( 'init', array( $this, 'init_frontend' ));
		}

	} // End __construct ()
	
	private function ltple_get_secret_iv(){
		
		//$secret_iv = md5( $this->user_agent . $this->user_ip );
		//$secret_iv = md5( $this->user_ip );
		$secret_iv = md5( 'another-secret' );	

		return $secret_iv;
	}	
	
	private function ltple_encrypt_str($string){
		
		$output = false;

		$encrypt_method = "AES-256-CBC";
		
		$secret_key = md5( $this->embedded->key );
		
		$secret_iv = $this->ltple_get_secret_iv();
		
		// hash
		$key = hash('sha256', $secret_key);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', $secret_iv), 0, 16);

		$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
		$output = $this->base64_urlencode($output);

		return $output;
	}
	
	private function ltple_decrypt_str($string){
		
		$output = false;

		$encrypt_method = "AES-256-CBC";
		
		$secret_key = md5( $this->embedded->key );
		
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

		add_action( 'wp_head', array( $this, 'get_header') );
		add_filter( 'wp_nav_menu', array( $this, 'get_menu' ), 10, 2);
		add_action( 'wp_footer', array( $this, 'get_footer') );				

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
				}
				elseif( !empty($_GET['uli']) && is_numeric($_GET['uli']) && !empty($_GET['ulk']) && $_GET['ulk'] == md5('userLayerId'.$_GET['uli']) ){
					
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

			if( current_user_can( 'edit_post', $post->ID ) ){
				
				// get user layer id
					
				$this->userLayerId = intval(get_post_meta( $post->ID, 'userLayerId', true ));
				
				// get default layer id
				
				$this->defaultLayerId = intval(get_post_meta( $post->ID, 'defaultLayerId', true ));
				
				// get embedded url
			
				$this->embedded_url = add_query_arg( array(
				
					'uri' 		=> ( $this->userLayerId > 0 ? $this->userLayerId : $this->defaultLayerId ),
					'le' 		=> urlencode($post->guid),
					'output' 	=> 'embedded',
					
				), LTPLE_EMBEDDED_EDITOR_URL );					
				
				$template_path = $this->views . $this->_dev . '/editor.php';
			}
		}
		elseif(is_single()){

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
	
		wp_register_style( $this->_token . '-bootstrap-table', esc_url( $this->assets_url ) . 'css/bootstrap-table.min.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-bootstrap-table' );	
		
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		
		wp_enqueue_script('jquery-ui-dialog');
		
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
		
		wp_register_script($this->_token . '-lazyload', esc_url( $this->assets_url ) . 'js/lazyload.min.js', array( 'jquery' ), $this->_version);
		wp_enqueue_script( $this->_token . '-lazyload' );	

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
		
		wp_register_style( $this->_token . '-bootstrap', esc_url( $this->assets_url ) . 'css/bootstrap.min.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-bootstrap' );	
		
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		
		wp_enqueue_script('jquery-ui-sortable');
		
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );

		//wp_register_script($this->_token . '-bootstrap', esc_url( $this->assets_url ) . 'js/bootstrap.min.js', array( 'jquery' ), $this->_version);
		//wp_enqueue_script( $this->_token . '-bootstrap' );		
		
		wp_register_script($this->_token . '-lazyload', esc_url( $this->assets_url ) . 'js/lazyload.min.js', array( 'jquery' ), $this->_version);
		wp_enqueue_script( $this->_token . '-lazyload' );	
		
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'live-template-editor-embedded', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
	    $domain = 'live-template-editor-embedded';

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