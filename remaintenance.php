<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              red-made.com
 * @since             1.0.0
 * @package           Remaintenance
 *
 * @wordpress-plugin
 * Plugin Name:       ReMaintenance
 * Plugin URI:        red-made.com/remaintenance
 * Description:       Close the website for maintenance except for logged Admin and for browser that have set a specific cookie.
 * Version:           1.0.0
 * Author:            red-made
 * Author URI:        red-made.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       remaintenance
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'REMAINTENANCE_VERSION', '1.0.0' );


class Remaintenance
{
 
    public function init()
    {
        add_action('get_header', array($this, 'check'));
		add_filter( 'query_vars', function( $query_vars ) {
		    $query_vars[] = 'reaccess';
		    return $query_vars;
		} );
    }

	public function getConfig()
	{
		return get_option( 're_option_name', array() );
	}

	public function setAccess($options)
	{
			
		$reAccess = get_query_var( 'reaccess', 0 );

		if ( $reAccess === $options['re_cookie_name'] ){
			setcookie($options['re_cookie_name'], time(), time() + $options['re_cookie_duration'], "/"); // 86400 = 1 day

            global $successMessage; 
            $successMessage = 'Access Granted. Go to <a href="'.get_home_url().'">'.get_home_url().'</a> and have fun!';
            
            include( 'theme/cookie-confirm.php' ); 
            exit;
		}

		return $reAccess;
	}	

	public function check()
	{


		$options = $this->getConfig();

		$reAccess = $this->setAccess($options);

		if ($options['re_enable'] == 1){
	
			$ican = 0;
		    if ( current_user_can('edit_themes') ) {
		    	$ican++;
		    }

		    if ( isset($_COOKIE[$options['re_cookie_name']]) ) {
		    	$ican++;
		    }

		    if ( $ican < 1 )  {

                global $reThemeContent; 
                $reThemeContent = $options['re_message'];

                include( 'theme/default.php' ); 

		        exit;

			}

		}


	}
   
}
 
$maintenance = new Remaintenance();
$maintenance->init();















class Remaintenance_settings_page
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'ReMaintenance options', 
            'ReMaintenance options', 
            'manage_options', 
            'red-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 're_option_name' );
        ?>
        <div class="wrap">
            <h1>Remaintenance options</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 're_option_group' );
                do_settings_sections( 're-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            're_option_group', // Option group
            're_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            're_section', // ID
            'Remaintenance options', // Title
            array( $this, 'print_section_info' ), // Callback
            're-setting-admin' // Page
        );  

        add_settings_field(
            're_enable', // ID
            'Enable maintenance?',
            array( $this, 're_enable_callback' ), // Callback
            're-setting-admin', // Page
            're_section' // Section 
        );  

        add_settings_field(
            're_cookie_name', // ID
            'Cookie auth name', // Title 
            array( $this, 're_cookie_name_callback' ), // Callback
            're-setting-admin', // Page
            're_section' // Section 
        );      

        add_settings_field(
            're_cookie_duration', 
            'Cookie duration in seconds', 
            array( $this, 're_cookie_duration_callback' ), 
            're-setting-admin', 
            're_section'
        ); 

         add_settings_field(
            're_message', 
            'Message', 
            array( $this, 're_message_callback' ), 
            're-setting-admin', 
            're_section'
        ); 
                    
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['re_cookie_duration'] ) )
            $new_input['re_cookie_duration'] = absint( $input['re_cookie_duration'] );

        if( isset( $input['re_cookie_name'] ) )
            $new_input['re_cookie_name'] = sanitize_text_field( $input['re_cookie_name'] );

        if( isset( $input['re_message'] ) )
            $new_input['re_message'] = sanitize_text_field( $input['re_message'] );

        if( isset( $input['re_enable'] ) ){
            $new_input['re_enable'] = absint( $input['re_enable'] );
        } else{
        	$new_input['re_enable'] = 0;
        }     

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {

    	$maintenance = new Remaintenance();
    	$options = $maintenance->getConfig();
        print 'Your website be accessible only for logged in user or guest with specified cookie. </br>Click on this link (or paste in browser) to set the cookie: <a style="color:#ff0000; font-weight:bold; font-size:1.1em;" href="'.get_home_url().'/?reaccess='.$options['re_cookie_name'].'">'.get_home_url().'/?reaccess='.$options['re_cookie_name'].'</a>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function re_cookie_name_callback()
    {
        printf(
            '<input type="text" id="re_cookie_name" name="re_option_name[re_cookie_name]" value="%s" />',
            isset( $this->options['re_cookie_name'] ) ? esc_attr( $this->options['re_cookie_name']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function re_cookie_duration_callback()
    {
        printf(
            '<input type="text" id="re_cookie_duration" name="re_option_name[re_cookie_duration]" value="%s" />',
            isset( $this->options['re_cookie_duration'] ) ? esc_attr( $this->options['re_cookie_duration']) : ''
        );
    }

    public function re_enable_callback()
    {
        printf(
            '<input type="checkbox" id="re_enable" name="re_option_name[re_enable]" value=1 %s />',
            $this->options['re_enable'] == 1 ? 'checked' : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function re_message_callback()
    {
        printf(
            '<input type="text" id="re_message" name="re_option_name[re_message]" value="%s" />',
            isset( $this->options['re_message'] ) ? esc_attr( $this->options['re_message']) : ''
        );
    }

}

if( is_admin() ){
    $re_settings_page = new Remaintenance_settings_page();
}