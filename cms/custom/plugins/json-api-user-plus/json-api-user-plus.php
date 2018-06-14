<?php



/*



  Plugin Name: JSON API User Plus



  Plugin URI: http://www.parorrey.com/solutions/json-api-user-plus/



  Description: Extends the JSON API for RESTful user registration, authentication, password reset, Facebook Login, user meta and BuddyPress Profile related functions



  Version: 2.8.5



  Author: Ali Qureshi



  Author URI: http://www.parorrey.com/



  License: GPLv3



 */





define('JAUP_VERSION', '2.8.5');



include_once( ABSPATH . 'wp-admin/includes/plugin.php' );



define('JAUP_HOME', dirname(__FILE__));



if (!defined('JAUP_VERSION_KEY'))

    define('JAUP_VERSION_KEY', 'jaup_version');



if (!defined('JAUP_VERSION_NUM'))

    define('JAUP_VERSION_NUM', JAUP_VERSION);



//add_option(JAUP_VERSION_KEY, JAUP_VERSION_NUM);





if (!is_plugin_active('json-api/json-api.php')) {



    add_action('admin_notices', 'wpjaup_draw_notice_json_api');



    return;



}



function wpjaup_draw_notice_json_api() {



    echo '<div id="message" class="error fade"><p style="line-height: 150%">';



    _e('<strong>JSON API User Plus</strong></a> requires the JSON API plugin to be activated. Please <a href="https://wordpress.org/plugins/json-api/‎">install / activate JSON API</a> first.', 'json-api-user-plus');



    echo '</p></div>';



}



function wpjaupJsonApiController($aControllers) {



    $aControllers[] = 'UserPlus';

    return $aControllers;



}



function wpjaupSetUserControllerPath($sDefaultPath) {



    return dirname(__FILE__) . '/controllers/UserPlus.php';



}



add_filter('json_api_controllers', 'wpjaupJsonApiController');



add_filter('json_api_userplus_controller_path', 'wpjaupSetUserControllerPath');





include_once( JAUP_HOME . '/functions.php');



/**

 * Creates the default options

 */

register_activation_hook( __FILE__, 'wp_jaup_setup_options' );



function wp_jaup_setup_options(){

 

    //the default options

    $wp_jaup_settings = array( 

	JAUP_VERSION_KEY => JAUP_VERSION_NUM,       

		'wp_jaup_api' => uniqid()			       

    );

 

    //check to see if present already

    if(!get_option('wp_jaup_settings')) {

        //option not found, add new

        add_option('wp_jaup_settings', $wp_jaup_settings);

    } else {

        //option already in the database

        //so we get the stored value and merge it with default

        $old_op = get_option('wp_jaup_settings');

        $wp_jaup_settings = wp_parse_args($old_op, $wp_jaup_settings);

 

        //update it

        update_option('wp_jaup_settings', $wp_jaup_settings);

    }

  

}



function wp_jaup_restore_options() {

        delete_option('wp_jaup_settings');

        wp_jaup_setup_options();

    }





define('JAUP_SHORTNAME', 'User Plus'); // used to prefix the individual setting field

define('JAUP_FULLNAME', 'JSON API User Plus'); // 

define('JAUP_PAGE_BASENAME', 'jaup-settings'); // the settings page slug



/*

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'jaup_plugin_settings' );



function jaup_plugin_settingss( $settings ) {

   $settings[] = '<a href="'. get_admin_url(null, 'options-general.php?page='.JAUP_PAGE_BASENAME) .'">Settings</a>';

   return $settings;

}

*/

/*

 * Add the admin page

 */

add_action('admin_menu', 'wp_jaup_admin_page');

function wp_jaup_admin_page(){

   // add_menu_page(JAUP_FULLNAME.' Settings', JAUP_SHORTNAME, 'administrator', JAUP_PAGE_BASENAME, 'wp_jaup_admin_page_callback');

     add_submenu_page('options-general.php',JAUP_FULLNAME.' Settings', JAUP_SHORTNAME, 'administrator', JAUP_PAGE_BASENAME, 'wp_jaup_admin_page_callback');

	

	}



/*

 * Register the settings

 */

add_action('admin_init', 'wp_jaup_register_settings');

function wp_jaup_register_settings(){

    //this will save the option in the wp_options table as 'wp_jaup_settings'

    //the third parameter is a function that will validate your input values

    register_setting('wp_jaup_settings', 'wp_jaup_settings', 'wp_jaup_settings_validate');

}



function wp_jaup_settings_validate($args){

    //$args will contain the values posted in your settings form, you can validate them as no spaces allowed, no special chars allowed or validate values etc.

    if(!isset($args['wp_jaup_api']) || empty($args['wp_jaup_api'])){

        //add a settings error because the value is invalid and make the form field blank, so that the user can enter again

        $args['wp_jaup_api'] = '';

    add_settings_error('wp_jaup_settings', 'wp_jaup_invalid_value', 'Please enter a value for api key!', $type = 'error');   

    }

	

     //make sure you return the args

    return $args;

}



//Display the validation errors and update messages

/*

 * Admin notices

 */

add_action('admin_notices', 'wp_jaup_admin_notices');

function wp_jaup_admin_notices(){

   settings_errors();

}



//The markup for your plugin settings page

function wp_jaup_admin_page_callback(){ 



 echo   '<div class="wrap">

    <h2>'.JAUP_FULLNAME.' Settings</h2>

    <form action="options.php" method="post">';

	

        settings_fields( 'wp_jaup_settings' );

        do_settings_sections( __FILE__ );



        //get the older values, wont work the first time

        $jaup_options = get_option( 'wp_jaup_settings' ); 

		

		

echo '<table class="form-table">

            <tr>

                <th scope="row">License Key</th>

                <td>

                    <fieldset>

                        <label>

                            <input name="wp_jaup_settings[jaup_license_key]" type="text" id="jaup_license_key" value="';

							

	echo (isset($jaup_options['jaup_license_key']) && $jaup_options['jaup_license_key'] != '') ? $jaup_options['jaup_license_key'] : '';

		echo '"/>

                            <br />

                            <span class="description">Add your license key. (Your Paypal transaction id is the license key).</span>

                        </label>

                    </fieldset>

                </td>

            </tr>

			 

			<tr>

                <th scope="row">API Key</th>

                <td>

                    <fieldset>

                        <label>

                            <input name="wp_jaup_settings[wp_jaup_api]" type="text" id="wp_jaup_api" value="';

							

	echo (isset($jaup_options['wp_jaup_api']) && $jaup_options['wp_jaup_api'] != '') ? $jaup_options['wp_jaup_api'] : '';

		echo '"/>

                            <br />

                            <span class="description">Change your api key.</span>

                        </label>

                    </fieldset>

                </td>

            </tr>

			<tr>

                <th scope="row">Secret Key</th>

                <td>

                    <fieldset>

                        <label>

                            <input name="wp_jaup_settings[secret]" type="text" id="secret" value="';

							

	echo (isset($jaup_options['secret']) && $jaup_options['secret'] != '') ? $jaup_options['secret'] : '';

		echo '"/>

                            <br />

                            <span class="description">Add your secret key. This will be used for specific endpoints.</span>

                        </label>

                    </fieldset>

                </td>

            </tr>

			 <tr>

                <th scope="row">Disable Nonce</th>

                <td>

                    <fieldset>

                        <label>

						<input type="checkbox" id="nonce" name="wp_jaup_settings[nonce]" value="1"' . checked( 1, $jaup_options['nonce'], false ) . '/>

						<label for="nonce">Disable Nonce requirement.</label>



                            

                    </fieldset>

                </td>

            </tr>
			
			 <tr>

                <th scope="row">Allow User Registration</th>

                <td>

                    <fieldset>

                        <label>

						<input type="checkbox" id="bypass_can_register" name="wp_jaup_settings[bypass_can_register]" value="1"' . checked( 1, $jaup_options['bypass_can_register'], false ) . '/>

						<label for="nonce">Bypass user registration requirement from WordPress Settings > General.</label>



                            

                    </fieldset>

                </td>

            </tr>
					
			

			 <tr>

                <th scope="row">Allow Post Submission</th>

                <td>

                    <fieldset>

                        <label>

						<input type="checkbox" id="authoring" name="wp_jaup_settings[authoring]" value="1"' . checked( 1, $jaup_options['authoring'], false ) . '/>

						<label for="authoring">Disable Post editing capability requirement for `add_post`, `update_post`, `delete_post` endpoints.</label>



                            

                    </fieldset>

                </td>

            </tr>

			<tr>

                <th scope="row">Notify New Post</th>

                <td>

                    <fieldset>

                        <label>

						<input type="checkbox" id="new_post" name="wp_jaup_settings[new_post]" value="1"' . checked( 1, $jaup_options['new_post'], false ) . '/>

						<label for="authoring">Enable/disable sending admin email notification for new post submission via `add_post` endpoint.</label>



                            

                    </fieldset>

                </td>

            </tr>

	        </table>

        <input type="submit" value="Save" />

    </form>

</div>';

 }