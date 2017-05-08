<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_Embedded_Settings {

	/**
	 * The single instance of LTPLE_Embedded_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */

	public $plugin;
	
	public $settings;
	public $tabs;
	public $addons;
	
	public function __construct ( $parent ) {

		$this->parent = $parent;
		
		$this->plugin 			= new stdClass();
		$this->plugin->slug  	= LTPLE_EMBEDDED_SLUG;
		$this->plugin->title 	= LTPLE_EMBEDDED_TITLE;
		$this->plugin->short 	= LTPLE_EMBEDDED_SHORT;
		
		// get options
		$this->options 				 = new stdClass();
		$this->options->postTypes 	 = get_option( $this->parent->_base . 'post_types');
					
		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'add_menu_items' ) );	
		
		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings(){
		
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_items () {
		
		//add menu in wordpress settings
		
		//$page = add_options_page( __( $this->plugin->title, $this->plugin->slug ) , __( $this->plugin->short, $this->plugin->slug ) , 'manage_options' , $this->parent->_token . '_settings' ,  array( $this, 'settings_page' ) );
		//add_action( 'admin_print_styles' . $page, array( $this, 'settings_assets' ) );
		
		//add menu in wordpress dashboard
		
		add_menu_page($this->plugin->short, $this->plugin->short, 'manage_options', $this->plugin->slug, array($this, 'settings_page'),'dashicons-layout');

	}
	
	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets ( $version = '1.0.1' ) {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the cbp-admin-js script below
		wp_enqueue_style( 'farbtastic' );
    	wp_enqueue_script( 'farbtastic' );

    	// We're including the WP media scripts here because they're needed for the image upload field
    	// If you're not including an image upload then you can leave this function call out
    	wp_enqueue_media();
		
    	wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), $version );
    	wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', $this->plugin->slug ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {
		
		$settings['settings'] = array(
			'title'					=> __( 'General settings', $this->plugin->slug ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'post_types',
					'label'			=> __( 'Post Types' , $this->plugin->slug ),
					'description'	=> '',
					'type'			=> 'checkbox_multi',
					'options'		=> array(
					
						'post' 			=> 'Post',
						'page' 			=> 'Page',
					),
				),
			)
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					if(!isset($field['label'])){
						
						$field['label'] = '';
					}
				
					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->parent->_base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->parent->_base ) );

				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {

		// Build page HTML
		
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			
			$html .= '<h1>' . __( $this->plugin->title , $this->plugin->slug ) . '</h1>' . "\n";

			$tab = '';
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				
				$tab .= $_GET['tab'];
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					
					if ( isset( $_GET['settings-updated'] ) ) {
						
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}
			
			$html .= '<div class="col-xs-9">' . "\n";

				$html .= '<form style="margin:15px;" method="post" action="options.php" enctype="multipart/form-data">' . "\n";

					// Get settings fields
					
					ob_start();
					
					settings_fields( $this->parent->_token . '_settings' );
					
					do_settings_sections( $this->parent->_token . '_settings' );

					$html .= ob_get_clean();

					$html .= '<p class="submit">' . "\n";
						$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
						$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , $this->plugin->slug ) ) . '" />' . "\n";
					$html .= '</p>' . "\n";
					
				$html .= '</form>' . "\n";
				
			$html .= '</div>' . "\n";
			
			$html .= '<div class="col-xs-3">' . "\n";
			
				
			
			$html .= '</div>' . "\n";
			
		$html .= '</div>' . "\n";

		echo $html;
	}

	public function do_settings_fields($page, $section) {
		
		global $wp_settings_fields;

		if ( !isset($wp_settings_fields) ||
			 !isset($wp_settings_fields[$page]) ||
			 !isset($wp_settings_fields[$page][$section]) )
			return;

		foreach ( (array) $wp_settings_fields[$page][$section] as $field ) {
			
			echo '<div class="settings-form-row row">';

				if ( !empty($field['title']) ){
			
					echo '<div class="col-xs-3" style="margin-bottom:15px;">';
					
						if ( !empty($field['args']['label_for']) ){
							
							echo '<label style="font-weight:bold;" for="' . $field['args']['label_for'] . '">' . $field['title'] . '</label>';
						}
						else{
							
							echo '<b>' . $field['title'] . '</b>';		
						}
					
					echo '</div>';
					echo '<div class="col-xs-9" style="margin-bottom:15px;">';
						
						call_user_func($field['callback'], $field['args']);
							
					echo '</div>';
				}
				else{
					
					echo '<div class="col-xs-12" style="margin-bottom:15px;">';
						
						call_user_func($field['callback'], $field['args']);
							
					echo '</div>';					
				}
					
			echo '</div>';
		}
	}	
	
	public function set_default_editor() {
		
		$r = 'html';
		return $r;
	}
	
	public function set_admin_edit_page_js(){
		
		echo '  <style type="text/css">
		
					#content-tmce, #content-tmce:hover, #qt_content_fullscreen{
						display:none;
					}
					
				</style>';
				
		echo '	<script type="text/javascript">
		
				jQuery(document).ready(function(){
					jQuery("#content-tmce").attr("onclick", null);
				});
				
				</script>';
	}

	public function schema_TinyMCE_init($in){
		
		/**
		 *   Edit extended_valid_elements as needed. For syntax, see
		 *   http://www.tinymce.com/wiki.php/Configuration:valid_elements
		 *
		 *   NOTE: Adding an element to extended_valid_elements will cause TinyMCE to ignore
		 *   default attributes for that element.
		 *   Eg. a[title] would remove href unless included in new rule: a[title|href]
		 */
		
		if(!isset($in['extended_valid_elements']))
			$in['extended_valid_elements']= '';
		
		if(!empty($in['extended_valid_elements']))
			$in['extended_valid_elements'] .= ',';

		$in['extended_valid_elements'] .= '@[id|class|style|title|itemscope|itemtype|itemprop|datetime|rel],div,dl,ul,ol,dt,dd,li,span,a|rev|charset|href|lang|tabindex|accesskey|type|name|href|target|title|class|onfocus|onblur]';

		return $in;
	}

	
	/**
	 * Main LTPLE_Embedded_Settings Instance
	 *
	 * Ensures only one instance of LTPLE_Embedded_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see LTPLE_Embedded()
	 * @return Main LTPLE_Embedded_Settings instance
	 */
	public static function instance ( $parent ) {
		
		if ( is_null( self::$_instance ) ) {
			
			self::$_instance = new self( $parent );
		}
		
		return self::$_instance;
		
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
