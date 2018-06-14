<?php

/*
  Controller name: UserPlus
  Controller description: User Registration, User Authentication, User Info, User Meta, FB Login, Avatar Update, BuddyPress xProfile Fields methods
  Controller Author: Ali Qureshi
  Controller Author Twitter: @parorrey
  Controller Author Website: parorrey.com
  
  last updated: 2018-05-07
*/
class JSON_API_UserPlus_Controller {
	var $disable_nonce;
	var $disable_author_check;
	var $notify_new_post;
	var $secret;
	
public function __construct() {
		global $json_api;
		
	$jaup_options = get_option( 'wp_jaup_settings' );
	$this->enable_https = $jaup_options['https'];
	$this->disable_nonce = $jaup_options['nonce'];
	$this->secret  = $jaup_options['secret'];
	$this->disable_author_check = $jaup_options['authoring'];
	$this->notify_new_post  = $jaup_options['new_post'];
	$this->bypass_can_register  = $jaup_options['bypass_can_register'];
	
	
	if ($json_api->query->data_format) {
			$data_format = $json_api->query->data_format;
		}
		
		if ($data_format == 'json'){
$inputJSON = file_get_contents('php://input');
$json_input= json_decode( $inputJSON, TRUE ); //convert JSON into array
$data_array = $json_input;
}else $data_array = $_REQUEST;
		
		foreach($data_array as $k=>$val) {
			if (isset($data_array[$k])) {
				if($k=='cookie') $val = urldecode($val);
				else $json_api->query->$k = $val;
			}
		}
	
		if (!$json_api->query->key || $json_api->query->key != $jaup_options['wp_jaup_api']) {
			$json_api->error("You must include a valid 'key' var in your request.");
		}
		
		if( $this->enable_https){
	    	if (empty($_SERVER['HTTPS']) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off')) {

		$json_api->error("HTTPS requirement is enabled. Either use https protocol or disable HTTPS requirement from Settings > User Plus.");	

		  }
		}
		
		
		
	}
	
public function info(){	  

	  	global $json_api;

   		return array(
				"version" => JAUP_VERSION_NUM				
		   );	   

	  } 	

public function email_exists(){
  global $json_api;	
  
   if (!$json_api->query->email) {
			$json_api->error("You must include 'email' var in your request. ");
		}
	else {
		$email = $json_api->query->email;
	if ( !is_email( $email ) ) {
   	 $json_api->error("E-mail address is invalid.");
             }
    elseif (email_exists($email)) {

	 $json_api->error("E-mail address is already in use.");

          }	
   else $response = $email.' address is available for registration.';	
	 
	 
	}  
  
  return array('msg'=>$response);
  
  }
  
public function username_exists(){
  global $json_api;	
  
   if (!$json_api->query->username) {
			$json_api->error("You must include 'username' var in your request.");
		}
	else {
	
	//Add usernames we don't want used

$invalid_usernames = array( 'admin' );
	
		$username = sanitize_user($json_api->query->username);
	
	if ( !validate_username( $username ) || in_array( $username, $invalid_usernames ) ) {
  $json_api->error("Username is invalid.");  
        }
	elseif ( username_exists( $username ) )  $json_api->error("Username already exists.");
      	
   else $response = $username.' is available for registration.';
	 
	}  
  
  return array('msg'=>$response);
  
 }

public function register(){

	global $json_api, $WishListMemberInstance;	  

if (!get_option('users_can_register') && !$this->bypass_can_register) {
            $json_api->error("User registration is disabled. Please enable it in Settings > General or enable only for API in Settings > User Plus");            
        }

   if (!$json_api->query->username) {
			$json_api->error("You must include 'username' var in your request. ");
		}
	else $username = sanitize_user( $json_api->query->username );
	
 
  if (!$json_api->query->email) {
			$json_api->error("You must include 'email' var in your request. ");
		}
	else $email = sanitize_email( $json_api->query->email );

 
 if (!$json_api->query->reference) $reference = $json_api->query->reference;
 
 
 if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'register');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }
 
 
 if ($json_api->query->display_name) $display_name = sanitize_text_field( $json_api->query->display_name );

$user_pass = sanitize_text_field( $_REQUEST['user_pass'] );

//Add usernames we don't want used

$invalid_usernames = array( 'admin' );

//Do username validation

	if ( !validate_username( $username ) || in_array( $username, $invalid_usernames ) ) {

  $json_api->error("Username is invalid.");
  
        }

    elseif ( username_exists( $username ) ) {

    $json_api->error("Username already exists.");

           }			

	else{


	if ( !is_email( $email ) ) {
   	 $json_api->error("E-mail address is invalid.");
             }
    elseif (email_exists($email)) {

	 $json_api->error("E-mail address is already in use.");

          }			

else {

	//Everything has been validated, proceed with creating the user

//Create the user

if( !isset($_REQUEST['user_pass']) ) {
	 $user_pass = wp_generate_password();
	 $_REQUEST['user_pass'] = $user_pass;
}

 $_REQUEST['user_login'] = $username;
 $_REQUEST['user_email'] = $email;

$allowed_params = array('user_login', 'user_email', 'user_pass', 'display_name', 'user_nicename', 'user_url', 'nickname', 'first_name',
                         'last_name', 'description', 'rich_editing', 'user_registered', 'jabber', 'aim', 'yim',
						 'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front'
                   );


foreach($_REQUEST as $field => $value){
		
	if( in_array($field, $allowed_params) ) $user[$field] = trim(sanitize_text_field($value));
	
    }


$user_id = wp_insert_user( $user );


if($_REQUEST['skip_role']) {
	
	$u = new WP_User( $user_id );

    $u->remove_role( get_option('default_role') );
	
	}
/*Send e-mail to admin and new user - 
You could create your own e-mail instead of using this function*/

if( isset($_REQUEST['user_pass']) && $_REQUEST['notify']=='no') {
	$notify = '';	
  }elseif($_REQUEST['notify']!='no') $notify = $_REQUEST['notify'];


if($user_id) wp_new_user_notification( $user_id, '',$notify );  

			}
		} 

if($user_id && $reference)  update_user_meta(  $user_id, 'reference', $reference);
	
if($user_id){	
if($_REQUEST['role']) update_user_meta($user_id, 'role', trim(sanitize_text_field($_REQUEST['role'])));
if($_REQUEST['shipping_first_name']) update_user_meta($user_id, 'shipping_first_name', trim(sanitize_text_field($_REQUEST['shipping_first_name'])));
if($_REQUEST['shipping_last_name']) update_user_meta($user_id, 'shipping_last_name', trim(sanitize_text_field($_REQUEST['shipping_last_name'])));
if($_REQUEST['shipping_address_1']) update_user_meta($user_id, 'shipping_address_1', trim(sanitize_text_field($_REQUEST['shipping_address_1'])));
if($_REQUEST['shipping_city']) update_user_meta($user_id, 'shipping_city', trim(sanitize_text_field($_REQUEST['shipping_city'])));
if($_REQUEST['shipping_postcode']) update_user_meta($user_id, 'shipping_postcode', trim(sanitize_text_field($_REQUEST['shipping_postcode'])));
if($_REQUEST['order_comments']) update_user_meta($user_id, 'order_comments', trim(sanitize_text_field($_REQUEST['order_comments'])));
if($_REQUEST['billing_first_name']) update_user_meta($user_id, 'billing_first_name', trim(sanitize_text_field($_REQUEST['billing_first_name'])));	
if($_REQUEST['billing_last_name']) update_user_meta($user_id, 'billing_last_name', trim(sanitize_text_field($_REQUEST['billing_last_name'])));
if($_REQUEST['billing_address_1']) update_user_meta($user_id, 'billing_address_1', trim(sanitize_text_field($_REQUEST['billing_address_1'])));
if($_REQUEST['billing_email']) update_user_meta($user_id, 'billing_email', trim(sanitize_text_field($_REQUEST['billing_email'])));
if($_REQUEST['billing_phone']) update_user_meta($user_id, 'billing_phone', trim(sanitize_text_field($_REQUEST['billing_phone'])));	
if($_REQUEST['billing_city']) update_user_meta($user_id, 'billing_city', trim(sanitize_text_field($_REQUEST['billing_city'])));
if($_REQUEST['billing_cf']) update_user_meta($user_id, 'billing_cf', trim(sanitize_text_field($_REQUEST['billing_cf'])));	

}
 


	 if(is_array($_REQUEST['wpm_useraddress'])){
		 
		 if(!class_exists(WishListMemberDBMethods)) $json_api->error("You must install and activate 'wishlist-member' plugin before adding wpm field.");
		 
			foreach ((array) $_REQUEST['wpm_useraddress'] as $k => $v) {
				$_REQUEST['wpm_useraddress'][$k] = stripslashes($v);
			}
			
		$data['wpm_useraddress'] = $WishListMemberInstance->Update_UserMeta( $user_id, 'wpm_useraddress', $_REQUEST['wpm_useraddress']);
	 }
			// wlm custom fields
	if(is_array($_REQUEST['wlm_custom_fields_profile'])){
		if(!class_exists(WishListMemberDBMethods)) $json_api->error("You must install and activate 'wishlist-member' plugin before adding wpm field.");
		 
	foreach ($_REQUEST['wlm_custom_fields_profile'] as $field=>$val) {
					$data['custom_'.$field] = $WishListMemberInstance->Update_UserMeta( $user_id, 'custom_' . $field, $val );
				
				}
			}
	 // wlm levels
	 if(is_array($_REQUEST['wlm_level'])){
	 if(!class_exists(WLMAPIMethods))  $json_api->error("You must install and activate 'wishlist-member' plugin before using this endpoint.");	
	 
	 if(!$_REQUEST['wlm_level']['level_id']) $json_api->error("You must provide valid 'level_id' in wlm_level array.");	
	else $level_id = (int) $_REQUEST['wlm_level']['level_id'];
	
	if($_REQUEST['wlm_level']['pending']) $level_pending = $_REQUEST['wlm_level']['pending'];
	else  $level_pending = false;
	
	if($_REQUEST['wlm_level']['confirm_email']) $level_confirm_email = $_REQUEST['wlm_level']['confirm_email'];
	else  $level_confirm_email = false;
			
		$level_args = array(
          'Users' => array($user_id),
          'Pending' => $level_pending,
		  'UnConfirmed' => $level_confirm_email
     );
	 
 wlmapi_add_member_to_level($level_id, $level_args);
		
			}
	
	
	$expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user_id, true);

	$cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');

 return array( 
          "cookie" => $cookie,	
		  "user_id" => $user_id	
		  ); 		  

  }    

public function get_avatar(){	  

	  	global $json_api;

if (function_exists('bp_is_active')) {	

    if (!$json_api->query->user_id) {
			$json_api->error("You must include 'user_id' var in your request. ");
		}
		
	  if (!$json_api->query->type) {
			$json_api->error("You must include 'type' var in your request. possible values 'full' or 'thumb' ");
		}

		
$avatar	= bp_core_fetch_avatar ( array( 'item_id' => $json_api->query->user_id, 'type' => $json_api->query->type, 'html'=>false ));

        return array('avatar'=>$avatar);	
   } else {
	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	  
	  }
  
	 } 

public function get_userinfo(){	  

	  	global $json_api;

    if (!$json_api->query->user_id) {
			$json_api->error("You must include 'user_id' var in your request. ");
		}

		$user = get_userdata($json_api->query->user_id);

        preg_match('|src="(.+?)"|', get_avatar( $user->ID, 32 ), $avatar);		

		return array(
				"id" => $user->ID,
				//"username" => $user->user_login,
				"nicename" => $user->user_nicename,
				//"email" => $user->user_email,
				"url" => $user->user_url,
				"roles" => $user->roles,
				"displayname" => $user->display_name,
				"firstname" => $user->user_firstname,
				"lastname" => $user->last_name,
				"nickname" => $user->nickname,
				"avatar" => $avatar[1]
		   );	   

	  }   

public function retrieve_password(){

    global $wpdb, $json_api, $wp_hasher;  

   if (!$json_api->query->user_login) {

			$json_api->error("You must include 'user_login' var in your request. ");

		}

    $user_login = $json_api->query->user_login;

  if ( strpos( $user_login, '@' ) ) {

        $user_data = get_user_by( 'email', trim( $user_login ) );

        if ( empty( $user_data ) )

        

	 $json_api->error("Your email address not found! ");

		

    } else {

        $login = trim($user_login);

        $user_data = get_user_by('login', $login);

    }



    // redefining user_login ensures we return the right case in the email

    $user_login = $user_data->user_login;

    $user_email = $user_data->user_email;


    do_action('retrieve_password', $user_login);


    $allow = apply_filters('allow_password_reset', true, $user_data->ID);

    if ( ! $allow )  $json_api->error("password reset not allowed! ");        

    elseif ( is_wp_error($allow) )  $json_api->error("An error occured! "); 



    $key = wp_generate_password( 20, false );

    do_action( 'retrieve_password_key', $user_login, $key );



    if ( empty( $wp_hasher ) ) {

        require_once ABSPATH . 'wp-includes/class-phpass.php';

        $wp_hasher = new PasswordHash( 8, true );

    }

    $hashed = $wp_hasher->HashPassword( $key );

    $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_login ) );



    $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";

    $message .= network_home_url( '/' ) . "\r\n\r\n";

    $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";

    $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";

    $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";

    $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";



    if ( is_multisite() )

        $blogname = $GLOBALS['current_site']->site_name;

    else

        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);



    $title = sprintf( __('[%s] Password Reset'), $blogname );



    $title = apply_filters('retrieve_password_title', $title);

    $message = apply_filters('retrieve_password_message', $message, $key);

add_filter( 'wp_mail_content_type', function( $content_type ) { return 'text/plain'; });

    if ( $message && !wp_mail($user_email, $title, $message) )

       $json_api->error("The e-mail could not be sent. Possible reason: your host may have disabled the mail() function...");

	else {   
		remove_filter( 'wp_mail_content_type', 'set_html_content_type' );

   return array(
    "msg" => 'Link for password reset has been emailed to you. Please check your email.',
     );	
	}

     }  
	 
public function update_password() {
	 
	  global $json_api;
	  
	   if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');

	if (!$user_id) 	$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		
		
   if (!$json_api->query->password) $json_api->error("You must include a 'password' var in your request.");
		
		else $password = $json_api->query->password;	
  
   
  $data['updated'] = wp_set_password( $password, $user_id ); 
	   
	   return $data;	    
	  
	  }	 
	  
public function reset_password() {
	 
	  global $json_api;
	  
	   if (!$json_api->query->secret) {
			$json_api->error("You must include 'secret' var in your request. ");
		}elseif($json_api->query->secret != $this->secret) {
			$json_api->error("Error invalid 'secret'. You must include valid 'secret' value in your request.");
		}
		
   if (!$json_api->query->user_id) {
			$json_api->error("You must include 'user_id' var in your request. ");
		}else $user_id = (int) $json_api->query->user_id;
		
		
   if (!$json_api->query->password) $json_api->error("You must include a 'password' var in your request.");
		
		else $password = $json_api->query->password;	
  
   $userdata = array('ID'=>$user_id, 'user_pass'=>$password);
  $data['updated'] = wp_update_user( $userdata );//wp_set_password( $password, $user_id ); 
	   
	   return $data;	    
	  
	  }	 	  
	  
public function update_user() {
	 
	  global $json_api;
	  
	   if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');

	if (!$user_id) 	$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		

   $userdata = array('ID' => $user_id);
  
 if($json_api->query->user_nicename) $userdata['user_nicename'] = $json_api->query->user_nicename;
  if($json_api->query->user_url) $userdata['user_url'] = $json_api->query->user_url;
   if($json_api->query->user_email) {
	  $email =  $json_api->query->user_email;
	   if ( !is_email( $email ) ) 	 $json_api->error("E-mail address is invalid.");
         elseif (email_exists($email)) $json_api->error("E-mail address is already in use.");
		 else $userdata['user_email'] =  $email;		
   }
    if($json_api->query->user_pass) $userdata['user_pass'] = $json_api->query->user_pass;
	 if($json_api->query->display_name) $userdata['display_name'] = $json_api->query->display_name;
	  if($json_api->query->nickname) $userdata['nickname'] = $json_api->query->nickname;
    if($json_api->query->last_name) $userdata['last_name'] = $json_api->query->last_name;
	
   
  $data['updated'] = wp_update_user( $userdata ); 
	   
	   return $data;	    
	  
	  }	  

public function validate_auth_cookie() {

		global $json_api;
		
		foreach($_POST as $k=>$val) {

			if (isset($_POST[$k])) {

				$json_api->query->$k = $val;

			}

		}


		if (!$json_api->query->cookie) {

			$json_api->error("You must include a 'cookie' authentication cookie. Use the `generate_auth_cookie` method.");

		}		

    	$valid = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in') ? true : false;

		return array(

			"valid" => $valid

		);

	}

public function generate_auth_cookie() {
		
		global $json_api;
		
	if ($json_api->query->data_format) {
			$data_format = $json_api->query->data_format;
		}
		
		if ($data_format == 'json'){
$inputJSON = file_get_contents('php://input');
$json_input= json_decode( $inputJSON, TRUE ); //convert JSON into array
$data_array = $json_input;
}else $data_array = $_REQUEST;
		
		foreach($data_array as $k=>$val) {
			if (isset($data_array[$k])) {
				$json_api->query->$k = $val;
			}
		}


		if (!$json_api->query->username && !$json_api->query->email) {

			$json_api->error("You must include 'username' or 'email' var in your request to generate cookie.");

		}


		if (!$json_api->query->password) {

			$json_api->error("You must include a 'password' var in your request.");

		}	
		
		if ($json_api->query->seconds) 	$seconds = (int) $json_api->query->seconds;

		else $seconds = 1209600;//14 days

       if ( $json_api->query->email ) {
		   
		 
		 if ( is_email(  $json_api->query->email ) ) {
		  if( !email_exists( $json_api->query->email))  {
			 $json_api->error("email does not exist."); 
			  }
		 }else  $json_api->error("Invalid email address."); 
		   
        $user_obj = get_user_by( 'email', $json_api->query->email );
		
		
		$user = wp_authenticate($user_obj->data->user_login, $json_api->query->password);
    }else {
		
		 $user = wp_authenticate($json_api->query->username, $json_api->query->password);
		}


    	if (is_wp_error($user)) {

    		$json_api->error("Invalid username/email and/or password.", 'error', '401');

    		remove_action('wp_login_failed', $json_api->query->username);

    	}


    	$expiration = time() + apply_filters('auth_cookie_expiration', $seconds, $user->ID, true);

    	$cookie = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in');

		preg_match('|src="(.+?)"|', get_avatar( $user->ID, 512 ), $avatar);	
		
		return array(
			"cookie" => $cookie,
			"cookie_name" => LOGGED_IN_COOKIE,
			"user" => array(
				"id" => $user->ID,
				"username" => $user->user_login,
				"nicename" => $user->user_nicename,
				"email" => $user->user_email,
				"url" => $user->user_url,
				"registered" => $user->user_registered,
				"displayname" => $user->display_name,
				"firstname" => $user->user_firstname,
				"lastname" => $user->last_name,
				"nickname" => $user->nickname,
				"description" => $user->user_description,
				"capabilities" => $user->wp_capabilities,
				"avatar" => $avatar[1]

			),
		);
	}

public function get_currentuserinfo() {

		global $json_api;

		if (!$json_api->query->cookie) {

			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");

		}
		
		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		

		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}

		$user = get_userdata($user_id);

        preg_match('|src="(.+?)"|', get_avatar( $user->ID, 32 ), $avatar);

		

		return array(

			"user" => array(

				"id" => $user->ID,

				"username" => $user->user_login,

				"nicename" => $user->user_nicename,

				"email" => $user->user_email,

				"url" => $user->user_url,

				"registered" => $user->user_registered,

				"displayname" => $user->display_name,

				"firstname" => $user->user_firstname,

				"lastname" => $user->last_name,

				"nickname" => $user->nickname,

				"description" => $user->user_description,

				"capabilities" => $user->wp_capabilities,

				"avatar" => $avatar[1]

			)

		);

	}	
	
public function get_posts() {
    global $json_api;
 $oReturn = new stdClass();
    $url = parse_url($_SERVER['REQUEST_URI']);
	
	
	 if (!$json_api->query->per_page) {
			$per_page = 10;
		}else $per_page = (int) $json_api->query->per_page;
		
		 if (!$json_api->query->offset) $offset = 0;
     else $offset = (int) $json_api->query->offset;	
	 
	 	 if (!$json_api->query->post_type) $post_type ='post'; 
		 else $post_type =  $json_api->query->post_type;	
	 
	 	 if (!$json_api->query->post_status) $post_status ='publish'; 
		 else $post_status =  $json_api->query->post_status;	
	
	if ($json_api->query->category) $category = $json_api->query->category;	 
	
	if ($json_api->query->user_id) $user_id = (int) $json_api->query->user_id;
	
	if ($json_api->query->category_name) $category_name = $json_api->query->category_name;	 
 
 	if ($json_api->query->include) $include = $json_api->query->include;	 

	if ($json_api->query->exclude) $exclude = $json_api->query->exclude;	 
	
		if ($json_api->query->meta_key) $meta_key = $json_api->query->meta_key;	
			if ($json_api->query->meta_value) $meta_value = $json_api->query->meta_value;	
	
	 
	  if (!$json_api->query->order) $order = 'DESC';
     else $order = $json_api->query->order;
	 
	   if (!$json_api->query->orderby) $orderby = 'date';
     else $orderby = $json_api->query->orderby;

///$offset = ($page * $per_page) - $per_page;
	
	

$args = array(
    'post_type'=>$post_type,
	'posts_per_page'=>$per_page,
    'offset'=>$offset,
	'post_status'      => $post_status,
'category'         => $category,
'category_name'    => $category_name,
'include'          => $include,
	'exclude'          => $exclude,
	'meta_key'         => $meta_key,
	'meta_value'       => $meta_value,
	'orderby'=>$orderby,
	'order'  => $order

);

	if($user_id)  $args['author'] = $user_id;
    
	$query = new WP_Query($args);
	
     $oReturn->posts =  $query->posts;
	 $oReturn->offset = $offset;
	 $oReturn->per_page = $per_page;
$oReturn->found_posts = (int) $query->found_posts;
    $oReturn->total_pages = $query->max_num_pages;   
    return  $oReturn;
  }	
	
public function add_post() {
    global $json_api;

foreach($_REQUEST as $key=>$val) $_REQUEST[$key] = urldecode($val);

	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
	
	
	if(!$this->disable_author_check){
		
	if (!user_can($user_id,'edit_posts')) {
    $json_api->error("You need to login with a user capable of creating posts.");
      }
	  
	}
		
	
    if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'add_post');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }
 
	if (!$json_api->query->title) {
      $json_api->error("You must include 'title' value to create post.");
    }
	
	if (!$json_api->query->content) {
      $json_api->error("You must include 'content' value to create post.");
    }
	
	if (!$json_api->query->status) {
      $json_api->error("You must include 'status' value to create post. 'draft' or 'publish'");
    }
	
   
	$post = new JSON_API_Post();
	
	$user_info = get_userdata($user_id);	
	$_REQUEST['author'] = $user_info->user_nicename;
	
	$id = $post->create($_REQUEST);	
	
	
    if (empty($id)) {
      $json_api->error("Could not create post.");
    }else {
		
		
	if ($json_api->query->taxonomy_ids) {
			 $taxonomy_ids = explode(',',$json_api->query->taxonomy_ids);
		}
		 
	 if ($json_api->query->taxonomy) {
			$taxonomy = $json_api->query->taxonomy;
		}
	
	if ($json_api->query->append) {
			$append = true;
		}else $append = false;
		
	
   if($taxonomy_ids && $taxonomy) $term_taxonomy_ids = wp_set_object_terms( $post->id, $taxonomy_ids, $taxonomy, $append );	
		
	add_post_meta($post->id, "_thumbnail_id", $post->attachments[0]->id);
	
	 if($this->notify_new_post)	pim_notify_admin_on_post($user_id);	
	 
		}
	
	//print_r($post);
	
		
    return array(
      'post' => $post
    );
  }  
  
public function update_post() {
    global $json_api;

foreach($_REQUEST as $key=>$val) $_REQUEST[$key] = urldecode($val);

 	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}	
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
	
	  if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'update_post');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }
	 $post = $json_api->introspector->get_current_post();
    if (empty($post)) {
      $json_api->error("Post not found. wrong post_id provided.");
    }	

   if (!$json_api->query->post_id ) {
      $json_api->error("You must include a 'post_id' to update post. ");
    }
	
	
	$author = get_post_field( 'post_author', $json_api->query->post_id );
	
	if(!$this->disable_author_check){
		
	if (!user_can($user_id,'edit_posts') ) {
    $json_api->error("You need to login with a user capable of editing posts. Only author of post or admin can update posts.");
      }
	  
	}
	
	if ($author!=$user_id ) {
    $json_api->error("You need to login with a user capable of editing posts. Only author of post or admin can update posts.");
      }
	
	if ($json_api->query->post_media=='delete') {
		
      $attachments = get_posts( array(
	        'post_type'      => 'attachment',
	        'posts_per_page' => -1,
	        'post_status'    => 'any',
	        'post_parent'    => $json_api->query->post_id
	    ) );

	    foreach ( $attachments as $attachment ) {
	        if ( false === wp_delete_attachment( $attachment->ID ) ) {
	            // Log failure to delete attachment.
	        }
	    }
    }
		
	
   nocache_headers();
    	
	$post = new JSON_API_Post( $post );
	
	$id = $post->update($_REQUEST);		
	
    if (empty($id)) {
      $json_api->error("Could not update post.");
    }else {	
		
		if ($json_api->query->taxonomy_ids) {
			 $taxonomy_ids = explode(',',$json_api->query->taxonomy_ids);
		}
		 
	 if ($json_api->query->taxonomy) {
			$taxonomy = $json_api->query->taxonomy;
		}
	
	if ($json_api->query->append) {
			$append = true;
		}else $append = false;
		
	
    if($taxonomy_ids && $taxonomy) $term_taxonomy_ids = wp_set_object_terms( $post->id, $taxonomy_ids, $taxonomy, $append );	


	$sizeof = sizeof($post->attachments); 		
	if($sizeof) update_post_meta($post->id, "_thumbnail_id", $post->attachments[($sizeof-1)]->id);	
		}
	
	//print_r($post);
	
		
    return array(
      'post' => $post
    );
  }  

public function update_cpt() {
    global $json_api;

 	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}	
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
	
	  if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'update_cpt');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }
	 
   if (!$json_api->query->post_id ) {
      $json_api->error("You must include a 'post_id' to update post. ");
    }
	
	
	$author = get_post_field( 'post_author', $json_api->query->post_id );
	
	if(!$this->disable_author_check){
		
	if (!user_can($user_id,'edit_posts') ) {
    $json_api->error("You need to login with a user capable of editing posts. Only author of post or admin can update posts.");
      }
	  
	}
	
	if ($author!=$user_id ) {
    $json_api->error("You need to login with a user capable of editing posts. Only author of post or admin can update posts.");
      }
	
	if ($json_api->query->post_media=='delete') {
		
      $attachments = get_posts( array(
	        'post_type'      => 'attachment',
	        'posts_per_page' => -1,
	        'post_status'    => 'any',
	        'post_parent'    => $json_api->query->post_id
	    ) );

	    foreach ( $attachments as $attachment ) {
	        if ( false === wp_delete_attachment( $attachment->ID ) ) {
	            // Log failure to delete attachment.
	        }
	    }
    }
		
	
   
  $post_args = array(
      'ID'           => $json_api->query->post_id,
      'post_title'   => $json_api->query->title,
      'post_content' => $json_api->query->content,
	  'post_excerpt' => $json_api->query->excerpt,
  );


 $id =  wp_update_post( $post_args );
		
	
    if (empty($id)) {
      $json_api->error("Could not update post.");
    }else {	
     
		if ($json_api->query->taxonomy_ids) {
			 $taxonomy_ids = explode(',',$json_api->query->taxonomy_ids);
		}
		 
	 if ($json_api->query->taxonomy) {
			$taxonomy = $json_api->query->taxonomy;
		}
	
	if ($json_api->query->append) {
			$append = true;
		}else $append = false;
		
	
   if($taxonomy_ids && $taxonomy) $term_taxonomy_ids = wp_set_object_terms( $post->id, $taxonomy_ids, $taxonomy, $append );	

	$sizeof = sizeof($post->attachments); 		
	if($sizeof) update_post_meta($post->id, "_thumbnail_id", $post->attachments[($sizeof-1)]->id);	
		}
	
	//print_r($post);
	
		
    return array(
      'post' => $post
    );
  }  
    
public function delete_post() {
    global $json_api;

foreach($_REQUEST as $key=>$val) $_REQUEST[$key] = urldecode($val);

 	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}	
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
	
	  if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'delete_post');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }
		
   $post = $json_api->introspector->get_current_post();
    if (empty($post)) {
      $json_api->error("Post not found. wrong post_id provided.");
    }
  
  
    if (!$json_api->query->post_id ) {
      $json_api->error("You must include a 'post_id' to delete post. ");
    }

	//$author = get_post_field( 'post_author', $json_api->query->post_id );
	
	if(!$this->disable_author_check){
	if ( !user_can($user_id, 'delete_posts') ) {
    $json_api->error("You need to login with a user capable of deleting posts.");
      }
	}
	
	
  if ($json_api->query->post_media=='delete') {
		
      $attachments = get_posts( array(
	        'post_type'      => 'attachment',
	        'posts_per_page' => -1,
	        'post_status'    => 'any',
	        'post_parent'    => $json_api->query->post_id
	    ) );

	    foreach ( $attachments as $attachment ) {
	        if ( false === wp_delete_attachment( $attachment->ID ) ) {
	            // Log failure to delete attachment.
	        }
	    }
   
  }
	
   nocache_headers();
    	
	$post = new JSON_API_Post( $post );
	
	  if ($json_api->query->force_delete) {
		 $id =  wp_delete_post($json_api->query->post_id, true);
		  
	  }else {
		  $id =  wp_delete_post($json_api->query->post_id);
	  }
	
    if (empty($id)) {
      $json_api->error("Could not delete post.");
    }	//print_r($post);
			
    return array('post'=>$id);
  }
	
public function delete_cpt() {
    global $json_api;

foreach($_REQUEST as $key=>$val) $_REQUEST[$key] = urldecode($val);

 	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}	
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
	
	  if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'delete_cpt');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }

  
    if (!$json_api->query->post_id ) {
      $json_api->error("You must include a 'post_id' to delete post. ");
    }

	//$author = get_post_field( 'post_author', $json_api->query->post_id );
	
	if(!$this->disable_author_check){
	if ( !user_can($user_id, 'delete_posts') ) {
    $json_api->error("You need to login with a user capable of deleting posts.");
      }
	}
	
	
  if ($json_api->query->post_media=='delete') {
		
      $attachments = get_posts( array(
	        'post_type'      => 'attachment',
	        'posts_per_page' => -1,
	        'post_status'    => 'any',
	        'post_parent'    => $json_api->query->post_id
	    ) );

	    foreach ( $attachments as $attachment ) {
	        if ( false === wp_delete_attachment( $attachment->ID ) ) {
	            // Log failure to delete attachment.
	        }
	    }
   
  }
	
	
	  if ($json_api->query->force_delete) {
		 $id =  wp_delete_post($json_api->query->post_id, true);
		  
	  }else {
		  $id =  wp_delete_post($json_api->query->post_id);
	  }
	
    if (empty($id)) {
      $json_api->error("Could not delete post.");
    }	//print_r($post);
			
    return array('post'=>$id);
  }
	
	
	
public function get_post_meta() {
	 
	  global $json_api;
	  
	  if (!$json_api->query->post_id) {
			$json_api->error("You must include a 'post_id' var in your request.");
		}else $post_id = (int) $json_api->query->post_id;

			
     $meta_key = sanitize_text_field($json_api->query->meta_key);	
  
		  
		if($meta_key) $meta[$meta_key] =  get_post_meta( $post_id, $meta_key);
		else $meta = get_post_meta( $post_id );
     	
		$data =  array_map( function( $a ) {
					return $a[0];
					}, $meta );
//d($data);
	   return array('post_meta'=>$data);
	    
	  
	  } 
	
public function update_post_taxonomy() {
	 
	  global $json_api;	

	  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
//	echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}
	
	 if (!$json_api->query->post_id) {
			$json_api->error("You must include a 'post_id' var in your request.");
		}else $post_id = (int) $json_api->query->post_id;	
		
	$author = get_post_field( 'post_author', $json_api->query->post_id );
	
	$allowed_roles = array('editor', 'administrator', 'author');
    $allowed = false;
if ($author!=$user_id ) {
         $user = new WP_User( $user_id );
if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
	foreach ( $user->roles as $role ){

	if( array_intersect($allowed_roles, $role ) ) {  
	   $allowed = true;
	   break;
          	
	     }
	   }//end foreach
	
	if( !$allowed ) $json_api->error("You need to login with same user who created post. Only author of post or admin can update post meta.");
    
	}
 }
	
	
	 if (!$json_api->query->taxonomy_ids) {
			$json_api->error("You must include 'taxonomy_ids' var in your request. To attach to post, you may povide multiple taxonomy ids separated by comma.");
		}else{
		 
		 $taxonomy_ids = explode(',',$json_api->query->taxonomy_ids);
	 }
	
	 if (!$json_api->query->taxonomy) {
			$json_api->error("You must include 'taxonomy' var in your request such a taxonomy=category. Any custom taxonomy slug is a valid value.");
		}else $taxonomy = $json_api->query->taxonomy;
	
	if ($json_api->query->append) {
			$append = true;
		}else $append = false;
		
	
    $term_taxonomy_ids = wp_set_object_terms( $post_id, $taxonomy_ids, $taxonomy, $append );
	
	if ( is_wp_error( $term_taxonomy_ids ) ) {
	$result['post_taxonomy'] = 'There was an error setting post\'s category';
} else {
	$result['post_taxonomy'] = 'The post\'s categories were set.';
}
 


	 return $result;
   

  }	
	  
public function update_post_meta() {
	 
	  global $json_api;	

	  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
//	echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}
	
	 if (!$json_api->query->post_id) {
			$json_api->error("You must include a 'post_id' var in your request.");
		}else $post_id = (int) $json_api->query->post_id;	
		
	$author = get_post_field( 'post_author', $json_api->query->post_id );
	
	$allowed_roles = array('editor', 'administrator', 'author');
    $allowed = false;
if ($author!=$user_id ) {
         $user = new WP_User( $user_id );
if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
	foreach ( $user->roles as $role ){

	if( array_intersect($allowed_roles, $role ) ) {  
	   $allowed = true;
	   break;
          	
	     }
	   }//end foreach
	
	if( !$allowed ) $json_api->error("You need to login with same user who created post. Only author of post or admin can update post meta.");
    
	}
 }
	if( sizeof($_REQUEST) <=1) $json_api->error("You must include one or more vars in your request to add or update post_meta. e.g. 'name', 'website', 'skills'. You must provide multiple meta_key vars in this format: &name=Ali&website=parorrey.com&skills=php,css,js,web design");

//d($_REQUEST);
foreach($_REQUEST as $meta_key => $value){
		
	if($meta_key=='cookie' || $meta_key=='key' || $meta_key=='secret' || $meta_key=='post_id') continue;
	
	
	if( strpos($value,'#') !== false ) {
		$values = explode("#", $value);
	   $values = array_map('trim',$values);
	   }
	else $values = trim($value);
	
   $result['post_meta'][$meta_key]['updated'] =  update_post_meta(  $post_id, $meta_key, $values);
 
}

	 return $result;
   

  }
  
public function delete_post_meta() {
	 
	  global $json_api;	

	  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
//	echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}
	
	 if (!$json_api->query->post_id) {
			$json_api->error("You must include a 'post_id' var in your request.");
		}else $post_id = (int) $json_api->query->post_id;	
		
	$author = get_post_field( 'post_author', $json_api->query->post_id );
	
	
	if ($author!=$user_id ) {
    $json_api->error("You need to login with same user who created post. Only author of post can delete post meta.");
      }	
		
		
	if (!$json_api->query->meta_keys) $json_api->error("You must include 'meta_keys' var in your request to delete post_meta. Provide comma separated meta_key to delete from post_meta.");
	
$meta_keys = explode(",", $json_api->query->meta_keys);

foreach($meta_keys as $k){
		
	//if($meta_key=='cookie' || $meta_key=='key' || $meta_key=='secret' || $meta_key=='post_id') continue;
	
	 $meta_key = trim($k);
	
   $result['post_meta'][$meta_key]['deleted'] =  delete_post_meta(  $post_id, $meta_key);
 
}

	 return $result;
   

  }
  
public function get_user_meta_id() {
	 
	  global $json_api;
	  
	  if (!$json_api->query->secret) {

		$json_api->error("You must include 'secret' var in your request.");

		}elseif($json_api->query->secret != $this->secret) $json_api->error("Error invalid 'secret'. You must include valid 'secret' var value in your request.");
	  
	  if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		}else $user_id = (int) $json_api->query->user_id;

		
	
 $meta_key = sanitize_text_field($json_api->query->meta_key);	
  
		  
		if($meta_key) $data[$meta_key] = get_user_meta(  $user_id, $meta_key);
		else {
		// Get all user meta data for $user_id
			$meta = get_user_meta( $user_id );

			// Filter out empty meta data
			$data = array_filter( array_map( function( $a ) {
					return $a[0];
					}, $meta ) );

     	 }
//d($data);
	   return $data;
	    
	  
	  }   
	   
public function get_user_meta() {
	 
	  global $json_api;
	  
	  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');

	if (!$user_id) 	$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
	
 $meta_key = sanitize_text_field($json_api->query->meta_key);	
  
		  
		if($meta_key) $data[$meta_key] = get_user_meta(  $user_id, $meta_key);
		else {
		// Get all user meta data for $user_id
			$meta = get_user_meta( $user_id );

			// Filter out empty meta data
			$data = array_filter( array_map( function( $a ) {
					return $a[0];
					}, $meta ) );

     	 }
//d($data);
	   return $data;
	    
	  
	  } 
	  
public function update_user_meta() {
	 
	  global $json_api;
	  
	   if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');

	if (!$user_id) 	$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		
		
   if (!$json_api->query->meta_key) $json_api->error("You must include a 'meta_key' var in your request.");
		
		else $meta_key = $json_api->query->meta_key;	
  
   if (!$json_api->query->meta_value) {
			$json_api->error("You must include a 'meta_value' var in your request. You may provide multiple values separated by comma for 'meta_value' var.");
		}
		else $meta_value = sanitize_text_field($json_api->query->meta_value);
  
  if( strpos($meta_value,',') !== false ) {
		$meta_values = explode(",", $meta_value);
	   $meta_values = array_map('trim',$meta_values);

	   $data['updated'] = update_user_meta(  $user_id, $meta_key, $meta_values);
	   }
 else $data['updated'] = update_user_meta(  $user_id, $meta_key, $meta_value); 
	   
	   return $data;	    
	  
	  }
	  
public function update_user_meta_vars() {
	 
	  global $json_api;	

	  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
//	echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}
		
	if( sizeof($_REQUEST) <=1) $json_api->error("You must include one or more vars in your request to add or update as user_meta. e.g. 'name', 'website', 'skills'. You must provide multiple meta_key vars in this format: &name=Ali&website=parorrey.com&skills=php,css,js,web design");

//d($_REQUEST);
foreach($_REQUEST as $field => $value){
		
	if($field=='cookie') continue;
	
	$field_label = str_replace('_',' ',$field);
	
	if( strpos($value,',') !== false ) {
		$values = explode(",", $value);
	   $values = array_map('trim',$values);
	   }
	else $values = trim($value);
	//echo 'field-values: '.$field.'=>'.$value;
	//d($values);

   $result[$field_label]['updated'] =  update_user_meta(  $user_id, $field, $values);
 
}

	 return $result;
   

  }	  

public function update_user_meta_admin() {
	 
	  global $json_api;	

	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

	$admin_user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
		if (!$admin_user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}else{
			
		if ( !is_super_admin($admin_user_id) ) {
	$json_api->error("Your user does not have Admin access.");
       }	
			
			}
//	echo '$user_id: '.$user_id;	

 if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		}else $user_id = (int) $json_api->query->user_id;
		
		
	 if ($json_api->query->data_format) {
			$data_format = $json_api->query->data_format;
		}
		
		 if ($json_api->query->meta_key) {
			$meta_key = $json_api->query->meta_key;
		}

if ($data_format == 'json'){
$inputJSON = file_get_contents('php://input');
$json_input= json_decode( $inputJSON, TRUE ); //convert JSON into array
$data_array = $json_input;
}else $data_array = $_REQUEST;

if($meta_key) {
	$result[$meta_key]['updated'] =  update_user_meta(  $user_id, $meta_key, $data_array);
}else{

foreach($data_array as $field => $value){
		
	if($field=='cookie' || $field=='secret' || $field=='user_id' || $field=='key') continue;
	
	$field_label = str_replace('_',' ',$field);
	
	if( strpos($value,',') !== false ) {
		$values = explode(",", $value);
	   $values = array_map('trim',$values);
	   }
	else $values = trim($value);
	//echo 'field-values: '.$field.'=>'.$value;
	//d($values);

   $result[$field_label]['updated'] =  update_user_meta(  $user_id, $field, $values);
  }
}

	 return $result;
   

  }	  
	  
public function delete_user_meta() {
	 
	  global $json_api;
	  
	   if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');

	if (!$user_id) 	$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		
		
   if (!$json_api->query->meta_key) $json_api->error("You must include a 'meta_key' var in your request.");
		
		else $meta_key = $json_api->query->meta_key;	
  
   if (!$json_api->query->meta_value) {
			$json_api->error("You must include a 'meta_value' var in your request.");
		}
		else $meta_value = sanitize_text_field($json_api->query->meta_value);
  

		$data['deleted'] = delete_user_meta(  $user_id, $meta_key, $meta_value);
		
	   return $data;	    
	  
	  }
	  
public function user_role() {

		global $json_api;

		if (!$json_api->query->secret) {

			$json_api->error("You must include 'secret' var in your request.");

		}elseif($json_api->query->secret != $this->secret) $json_api->error("Error invalid 'secret'. You must include valid 'secret' var value in your request.");
		
		 if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = $json_api->query->user_id;
	
			
		 if (!$json_api->query->role) {
			$json_api->error("You must include a valid 'role' var in your request.");
		}
	

        $user = new WP_User( $user_id );

        $user->set_role( $json_api->query->role );		

		return array(

			"user_id" => $user->ID

		);

	}	
	  
public function xprofile() {
	 
	  global $json_api;
	  
if (function_exists('bp_is_active')) {	

	  if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		}
		else $user_id = $json_api->query->user_id;
	
		
   if (!$json_api->query->field) {
			$json_api->error("You must include a 'field' var in your request. Use 'field=default' for all default fields.");
		}
	  elseif ($json_api->query->field=='default') {
			$field_label='First Name, Last Name, Bio';/*you should add your own field labels here for quick viewing*/
		}	
		else $field_label = sanitize_text_field($json_api->query->field);	
  
  preg_match('|src="(.+?)"|', get_avatar( $user_id, 512 ), $avatar);
  
  $fields_data['avatar'] = $avatar[1];
  
  $fields = explode(",", $field_label);
  
  if(is_array($fields)){
	  
	  foreach($fields as $k){
		  
		  $fields_data[$k] = xprofile_get_field_data( $k, $user_id );
		  
		  }
	
	
	   return $fields_data;
	    
	  
	  }
	
   }
   
  else {
	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	  
	  }

  }

public function xprofile_update() {
	 
	  global $json_api;	

if (function_exists('bp_is_active')) {	
	
	  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
//	echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}


foreach($_REQUEST as $field => $value){
		
	if($field=='cookie' || $field=='key' || $field=='secret' ) continue;
	
	$field_label = str_replace('_',' ',$field);
	
	if( strpos($value,',') !== false ) {
		$values = explode(",", $value);
	   $values = array_map('trim',$values);
	   }
	else $values = trim($value);
	//echo 'field-values: '.$field.'=>'.$value;
	//print_r($values);
  
  $result[$field_label]['updated'] = xprofile_set_field_data( $field_label,  $user_id, $values, $is_required = true ); 
  
}

	 return $result;
   }
   
  else {
	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	  
	  }

  }  
  
public function fb_connect(){
	  
	    global $json_api;
		
		if ($json_api->query->fields) {

			$fields = $json_api->query->fields;

		}else $fields = 'id,name,first_name,last_name,email';
		
		if ($json_api->query->ssl) {
			 $enable_ssl = $json_api->query->ssl;
		}else $enable_ssl = true;
	
	if (!$json_api->query->access_token) {
			$json_api->error("You must include a 'access_token' variable. Get the valid access_token for this app from Facebook API.");
		}else{
			
$url='https://graph.facebook.com/me/?fields='.$fields.'&access_token='.$json_api->query->access_token;
	
	//  Initiate curl
$ch = curl_init();
// Enable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $enable_ssl);
// Will return the response, if false it print the response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Set the url
curl_setopt($ch, CURLOPT_URL,$url);
// Execute
$result=curl_exec($ch);
// Closing
curl_close($ch);

	$result = json_decode($result, true);
	
   if(isset($result["email"])){
          
            $user_email = $result["email"];
           	$email_exists = email_exists($user_email);
			
			if($email_exists) {
				$user = get_user_by( 'email', $user_email );
			  $user_id = $user->ID;
			  $user_name = $user->user_login;
			 }
			
         
		   
		    if ( !$user_id && $email_exists == false ) {
				
			  $user_name = strtolower($result['first_name'].'.'.$result['last_name']);
               				
				while(username_exists($user_name)){		        
				$i++;
				$user_name = strtolower($result['first_name'].'.'.$result['last_name']).'.'.$i;			     
	
					}
				
			 $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
      		   $userdata = array(
                           'user_login'    => $user_name,
						   'user_email'    => $user_email,
                           'user_pass'  => $random_password,
						   'display_name'  => $result["name"],
						   'first_name'  => $result['first_name'],
						   'last_name'  => $result['last_name']
                                     );

                   $user_id = wp_insert_user( $userdata ) ;				   
				 if($user_id) {
					 $user_account = 'user registered.';
					 update_user_meta(  $user_id, 'reference', 'Facebook'); 
					 }
				 
            } else {
				
				 if($user_id) $user_account = 'user logged in.';
				}
			
			 $expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user_id, true);
    	     $cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');
        
		$response['msg'] = $user_account;
		$response['wp_user_id'] = $user_id;
		$response['cookie'] = $cookie;
		$response['user_login'] = $user_name;	
		$response['registered'] = $user->user_registered;
		}
		else {
			$json_api->error( "Your 'access_token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Facebook app.");

			}
	
	}	

return $response;
	  
	  }
	  
public function google_connect(){
	  
	    global $json_api;
		
		if ($json_api->query->ssl) {
			 $enable_ssl = $json_api->query->ssl;
		}else $enable_ssl = true;
	
	if (!$json_api->query->id_token) {
			$json_api->error("You must include 'id_token' value. Get the valid 'id_token' for your app from Google API. (Try this https://developers.google.com/oauthplayground/) to generate a 'id_token'. To use this 'google_connect' endpoint, you must be familiar with the Google apps and generating access_token and id_token for your app. See this for more details https://developers.google.com/identity/protocols/OAuth2UserAgent");
		}else{
			
$url='https://www.googleapis.com/oauth2/v3/tokeninfo?id_token='.$json_api->query->id_token;
	
	//  Initiate curl
$ch = curl_init();
// Enable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $enable_ssl);
// Will return the response, if false it print the response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Set the url
curl_setopt($ch, CURLOPT_URL,$url);
// Execute
$result=curl_exec($ch);
// Closing
curl_close($ch);

	$result = json_decode($result, true);
	
   if(isset($result["email"])){
          
            $user_email = $result["email"];
           	$email_exists = email_exists($user_email);
			
			if($email_exists) {
				$user = get_user_by( 'email', $user_email );
			  $user_id = $user->ID;
			  $user_name = $user->user_login;
			 }
			
         
		   
		    if ( !$user_id && $email_exists == false ) {
				
			  $user_name = strtolower($result['given_name'].'.'.$result['family_name']);
               				
				while(username_exists($user_name)){		        
				$i++;
				$user_name = strtolower($result['given_name'].'.'.$result['family_name']).'.'.$i;			     
	
					}
				
			 $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
      		   $userdata = array(
                           'user_login'    => $user_name,
						   'user_email'    => $user_email,
                           'user_pass'  => $random_password,
						   'display_name'  => $result["name"],
						   'first_name'  => $result['given_name'],
						   'last_name'  => $result['family_name']
                                     );

                   $user_id = wp_insert_user( $userdata ) ;				   
				 if($user_id) {
					 $user_account = 'user registered.';
					 update_user_meta(  $user_id, 'reference', 'Google'); 
					 }
				 
            } else {
				
				 if($user_id) $user_account = 'user logged in.';
				}
			
			 $expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user_id, true);
    	     $cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');
        
		$response['msg'] = $user_account;
		$response['wp_user_id'] = $user_id;
		$response['cookie'] = $cookie;
		$response['user_login'] = $user_name;
	   $response['registered'] = $user->user_registered;
		$response['picture'] =  $result['picture'];	
		
		}
		else {
			$json_api->error("Your 'id_token' did not return email of the user. Without 'email', user cookie can't be generated or user be registered. Get user email included in the id_token from Google Auth API.");

			}
	
	}	

return $response;
	  
	  }	  
 
public function post_comment(){
   global $json_api;

  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

 if ( !$json_api->query->post_id ) {
  $json_api->error("No post specified. Include 'post_id' var in your request.");
  } elseif (!$json_api->query->content ) {
  $json_api->error("Please include 'content' var in your request.");
  }
  
 if (!isset($json_api->query->comment_status) ) {
  $json_api->error("Please include 'comment_status' var in your request. Possible values are '1' (approved) or '0' (not-approved)");
  }else $comment_approved = $json_api->query->comment_status;

$user_info = get_userdata(  $user_id );

 $time = current_time('mysql');
 $agent = $_SERVER['HTTP_USER_AGENT'];
 $ip=$_SERVER['REMOTE_ADDR'];

    $data = array(
  'comment_post_ID' => $json_api->query->post_id,
  'comment_author' => $user_info->user_login,
  'comment_author_email' => $user_info->user_email,
  'comment_author_url' => $user_info->user_url,
  'comment_content' => $json_api->query->content,
  'comment_type' => '',
  'comment_parent' => 0,
  'user_id' => $user_info->ID,
  'comment_author_IP' =>  $ip,
  'comment_agent' => $agent,
  'comment_date' => $time,
  'comment_approved' => $comment_approved,
   );

//print_r($data);

 $comment_id = wp_insert_comment($data);
 
 return array(
             "comment_id" => $comment_id
             );    
   }

public function delete_comment(){
   global $json_api;

  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

 if ( !$json_api->query->comment_id ) {
  $json_api->error("No comment_id specified. Include 'comment_id' var in your request.");
  } else $comment_id = $json_api->query->comment_id;
  
  if ($json_api->query->force_delete ) {
 $force_delete = true;
  } else  $force_delete = false;


 $result =  wp_delete_comment( $comment_id, $force_delete );
 
 return array(
             "response" => $result
             );    
   }
   
public function comment_status(){
   global $json_api;

  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

 if ( !$json_api->query->comment_id ) {
  $json_api->error("No comment_id specified. Include 'comment_id' var in your request.");
  } else $comment_id = $json_api->query->comment_id;
  
  $result =  wp_get_comment_status( $comment_id);
 
 return array(
             "response" => $result
             );    
   }  
     
public function comments(){
	  global $json_api;	    
	
	 $oReturn = new stdClass();
	
	 if (!$json_api->query->post_id) {
			$json_api->error("You must include a 'post_id' var in your request.");
		}else $post_id = (int) $json_api->query->post_id;
	
	 if (!$json_api->query->per_page) {
			$limit = 10;
		}else $limit = (int) $json_api->query->per_page;
		
		 if (!$json_api->query->page) $page = 1;
     else $page = (int) $json_api->query->page;	
	 
	  if (!$json_api->query->sort) $sort = 'DESC';
     else $sort = $json_api->query->sort;

$offset = ($page * $limit) - $limit;

$args = array(
    'status'=>'approve',
    'offset'=>$offset,
    'number'=>$limit,
	'post_id' => $post_id,
	'order'  => $sort

);

$total_comments = get_comments(array('status'=>'approve', 'post_id' => $post_id));

$oReturn->total_pages = ceil(count($total_comments)/$limit);
$oReturn->current_page = $page;

$comments = get_comments($args);
   

  foreach($comments as $comment) { 
		 $tmp = new stdClass();
	$tmp->comment_id = $comment->comment_ID;   
	$tmp->user_id = $comment->user_id; 
	$tmp->avatar = bp_core_fetch_avatar(array('item_id'=>$tmp->user_id,'html'=>false));;
	$tmp->comment_date = $comment->comment_date;
	$tmp->time_since =  bp_core_time_since($comment->comment_date);
		
	$tmp->author = $comment->comment_author; 
	$tmp->content = $comment->comment_content;
	
	$oReturn->comments[] = $tmp;
     }

   return $oReturn;
	   
	   }   
	  
public function profile() { 

   global $json_api;
        $oReturn = new stdClass();
	  
if (function_exists('bp_is_active')) {	

	global $bp;
	
	  if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		}
		else $user_id = $json_api->query->user_id;



        if (!bp_has_profile(array('user_id' => $user_id))) {

            return $this->error('xprofile', 0);
			$json_api->error("$user_id does not have BuddPress profile.");

        }



        while (bp_profile_groups(array('user_id' => $user_id))) {

            bp_the_profile_group();

            if (bp_profile_group_has_fields()) {

                $sGroupName = bp_get_the_profile_group_name();

                while (bp_profile_fields()) {

                    bp_the_profile_field();

                    $sFieldName = bp_get_the_profile_field_name();

                    if (bp_field_has_data()) {

                        $sFieldValue = bp_get_the_profile_field_edit_value();

                    }

                    $oReturn->profileGroups->$sGroupName->$sFieldName = $sFieldValue;

                }

            }

        }

        return $oReturn;

    }else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
   }
  
public function threads() { 
     global $json_api;
        $oReturn = new stdClass();
	
	if (function_exists('bp_is_active')) {	

	
	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
	//d(get_userdata( $user_id ));
	
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
			
	$box = 'inbox';
	$per_page = 10;
	$page = 1;
	$type = 'all';

	
	 if ($json_api->query->box) $box = $json_api->query->box;	 
	 if ($json_api->query->per_page) $per_page = $json_api->query->per_page;	 
	 //if ($json_api->query->limit) $limit = $json_api->query->limit;
	  if ($json_api->query->search) $search_terms = $json_api->query->search; else $search_terms = '';
	   if ($json_api->query->page) $page = $json_api->query->page;
	    if ($json_api->query->tsort) $tsort = $json_api->query->tsort; else $tsort = 'desc';
		 if ($json_api->query->msort) $msort = $json_api->query->msort; else $msort = 'desc';
	   
	    if ($json_api->query->sender_id) $sender_id = $json_api->query->sender_id;
	
	$limit = $per_page;
	
	      global $wpdb, $bp;
   
           $user_id_sql = $pag_sql = $type_sql = $search_sql = '';
   
           if ( $limit && $page ) {
               $pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
           }
   
           if ( $type == 'unread' ) {
               $type_sql = " AND r.unread_count != 0 ";
           } elseif ( $type == 'read' ) {
               $type_sql = " AND r.unread_count = 0 ";
           }
  
           if ( ! empty( $search_terms ) ) {
               $search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
               $search_sql        = $wpdb->prepare( "AND ( subject LIKE %s OR message LIKE %s )", $search_terms_like, $search_terms_like );
           }
   
           if ( 'sentbox' == $box ) {
               $user_id_sql = $wpdb->prepare( 'm.sender_id = %d', $user_id );
               $thread_ids  = $wpdb->get_results( "SELECT m.thread_id, MAX(m.date_sent) AS date_sent FROM {$bp->messages->table_name_recipients} r, {$bp->messages->table_name_messages} m WHERE m.thread_id = r.thread_id AND m.sender_id = r.user_id AND {$user_id_sql} AND r.is_deleted = 0 {$search_sql} GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}" );
               $total_threads = $wpdb->get_var( "SELECT COUNT( DISTINCT m.thread_id ) FROM {$bp->messages->table_name_recipients} r, {$bp->messages->table_name_messages} m WHERE m.thread_id = r.thread_id AND m.sender_id = r.user_id AND {$user_id_sql} AND r.is_deleted = 0 {$search_sql} " );
           } else {
               $user_id_sql = $wpdb->prepare( 'r.user_id = %d', $user_id );
			   
			   if($sender_id) $sender_sql = " AND m.sender_id=$sender_id";
			   
			   $my_query = "SELECT m.thread_id, MAX(m.date_sent) AS date_sent FROM {$bp->messages->table_name_recipients} r, {$bp->messages->table_name_messages} m WHERE m.thread_id = r.thread_id $sender_sql AND r.is_deleted = 0 AND {$user_id_sql} AND r.sender_only = 0 {$type_sql} {$search_sql} GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}";
			   

			   
               $thread_ids = $wpdb->get_results( $my_query );
               $total_threads = $wpdb->get_var( "SELECT COUNT( DISTINCT m.thread_id ) FROM {$bp->messages->table_name_recipients} r, {$bp->messages->table_name_messages} m WHERE m.thread_id = r.thread_id $sender_sql AND r.is_deleted = 0 AND {$user_id_sql} AND r.sender_only = 0 {$type_sql} {$search_sql}" );
           }
   
           if ( empty( $thread_ids ) ) {
               return false;
           }
   
           // Sort threads by date_sent
		   
		   
		   
           foreach( (array) $thread_ids as $thread ) {
               $sorted_threads[$thread->thread_id] = strtotime( $thread->date_sent );
           }
   
        //   
		   
    if($tsort=='desc') asort( $sorted_threads );
    else arsort( $sorted_threads );
   
   
           $threads = false;
           foreach ( (array) $sorted_threads as $thread_id => $date_sent ) {
             $threads[] = new BP_Messages_Thread( $thread_id );			
			 
         }
          
		//d($threads);
		  
		  foreach($threads as $t){
			  $tmp = new stdClass();
			  

		//d($t->messages);
		
			// if($recipient_id && !in_array($recipient_id, $t->sender_ids)) continue;
			 
				$tmp->thread_id = $t->thread_id;
				//$tmp->unread_count = $t->unread_count;
				$messages =  $t->messages;								
				foreach($messages as $m){
				$thread = $m;
				$thread->time_since = bp_core_time_since ($m->date_sent);
				
				}
				$tmp->message =  $thread;
				
				$recipients_ids = $t->recipients;
			
				foreach($recipients_ids as $r){
					 $recipients['id'] = (int)$r->id;
					 $recipients['user_id'] = (int)$r->user_id;
					  $recipients['thread_id'] = (int)$r->thread_id;
					   $recipients['sender_only'] = (int)$r->sender_only;
					   $recipients['is_deleted'] = (int)$r->is_deleted;
					 $user = get_userdata($r->user_id);
					  $recipients['avatar'] = bp_core_fetch_avatar(array('item_id'=>$r->user_id,'html'=>false));
					  $recipients['display_name'] = $user->display_name;
				}				
				
				$tmp->recipients = $recipients;
				
				$sender_ids = array_values($t->sender_ids);
				
				foreach($sender_ids as $s){
					 $senders['id'] = (int)$s;
					 $user = get_userdata($s);
					  $senders['avatar'] = bp_core_fetch_avatar(array('item_id'=> $s,'html'=>false));
					  $senders['display_name'] = $user->display_name;
				}
				$tmp->sender_ids = $senders; 
								 
				$reindexed_threads[] = $tmp;
				//d($t->recipients);
			  }
		  
		  
        $oReturn = array( 'threads' => $reindexed_threads, 
		                  'total_pages' => ceil((int)$total_threads/$per_page), 
						  'current_page' => (int)$page,
						  'total_threads' => (int) $total_threads );
   
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
        return $oReturn;
    }

public function thread() { 
     global $json_api;
        $oReturn = new stdClass();
	
	if (function_exists('bp_is_active')) {	

	
	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
		
	 if (!$json_api->query->thread_id) {
			$json_api->error("You must include a 'thread_id' var in your request. Use the messages end point to get thread_id.");
		}else $thread_id = $json_api->query->thread_id;	

  if (!messages_check_thread_access( $thread_id, $user_id) ) {
			$json_api->error("Invalid Access. User does not have access to this thread.");
		}	
  

 $aParams ['thread_id'] = $thread_id;	
 $aParams ['order'] = 'ASC';		

	// if ($json_api->query->order) $aParams ['order'] = $json_api->query->order;
	 
		//print_r($aParams);

        if (bp_thread_has_messages($aParams)) {
			
			$oReturn->thread_subject = bp_get_the_thread_subject(); 

			messages_mark_thread_read( $thread_id );
			
            while (bp_thread_messages()) {
                bp_thread_the_message();
				
                $aTemp = new stdClass();	
				 
				$aTemp->message_id = bp_get_the_thread_message_id();
				$aTemp->sender_id = (int) messages_get_message_sender( $aTemp->message_id );
				$aTemp->avatar = bp_core_fetch_avatar(array('item_id'=>$aTemp->sender_id,'html'=>false));       
				$aTemp->time_since =  bp_get_the_thread_message_time_since();
				$aTemp->date_sent =  pim_bp_get_message_date($aTemp->message_id);
                $aTemp->message = strip_tags(bp_get_the_thread_message_content());
                            		
				$users[] = $aTemp->sender_id;				
				
			   $oReturn->thread_messages[] = $aTemp;
			
            }
        } else {
            $json_api->error("No message available.");
        }
		
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
        
		
		$users = array_unique($users);
		
		foreach($users as $k){
			 $aUser = new stdClass();
			 $aUser->user_id = $k;
			 $aUser->avatar = bp_core_fetch_avatar(array('item_id'=>$k,'html'=>false));;
			 
			 $oReturn->thread_users[]= $aUser;
			}
		
		
		return $oReturn;
    }
  
public function new_message() { 
    global $json_api;    	
	
  if (function_exists('bp_is_active')) {	
	
	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
	if (empty($json_api->query->recipient_ids)) {
      $json_api->error("No recipient specified. Include `recipient_ids` var in your request.");
    } elseif (empty($json_api->query->subject)) {
      $json_api->error("No subject specified. Include `subject` var in your request.");
    }elseif (empty($json_api->query->content)) {
      $json_api->error("No content specified. Include `content` var in your request.");
    }

	$sender_id = $user_id;	
	$recipient_ids =  array_map('trim',explode(",", $_REQUEST['recipient_ids']));
	$subject =  sanitize_text_field( $_REQUEST['subject'] );
	$content =  sanitize_text_field( $_REQUEST['content'] );
	$thread_id =  sanitize_text_field( $_REQUEST['thread_id'] );
	
	if($thread_id){ 
	if (!messages_check_thread_access( $thread_id, $user_id) ) {
			$json_api->error("Invalid Access. User does not have access to this thread.");
		}
	}
	
	$args = array(
	          'sender_id' =>$sender_id,
			  'recipients'=>$recipient_ids,
	          'subject'=>$subject,
			  'content'=>$content,
			  'thread_id'=>$thread_id			  
	        );
	
//d($args);

	
    $message_thread_id = messages_new_message( $args );
	

    
	return array(
	       "sender_id" => $args['sender_id'],
		   "recipient_id" => $args['recipients'],
	       "message_thread_id" => $message_thread_id
		  ); 
	 }else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }  
   }
   
public function mark_thread_read(){ 
	    global $json_api;  
		  	
	if (function_exists('bp_is_active')) {	
	
	global $bp, $wpdb;	
		 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
		
		
	 if (!$json_api->query->thread_id) {
			$json_api->error("You must include a 'thread_id' var in your request.");
		}else $thread_id = $json_api->query->thread_id;	

  if (!messages_check_thread_access( $thread_id, $user_id) ) {
			$json_api->error("Invalid Access. User does not have access to this thread.");
		}	
	  
	  // $action = BP_Messages_Thread::mark_as_read( $thread_id ); 
	   
	   $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE user_id = %d AND thread_id = %d", $user_id, $thread_id );
	   
	$action = $wpdb->query($sql);

		wp_cache_delete( $user_id, 'bp_messages_unread_count' );
	   
	  
	   return array('mark_read'=>$action);
	   
	   }else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	   
	 }

public function mark_thread_unread(){ 
	    global $json_api;  
		  	
	if (function_exists('bp_is_active')) {	
	global $bp, $wpdb;	
	
		 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
		
		
	 if (!$json_api->query->thread_id) {
			$json_api->error("You must include a 'thread_id' var in your request.");
		}else $thread_id = $json_api->query->thread_id;	

  if (!messages_check_thread_access( $thread_id, $user_id) ) {
			$json_api->error("Invalid Access. User does not have access to this thread.");
		}	
	  
	   $sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 1 WHERE user_id = %d AND thread_id = %d", $user_id, $thread_id );

   wp_cache_delete( $user_id, 'bp_messages_unread_count' );
   
	$action = $wpdb->query($sql);	  
	  	  
	   return array('mark_unread'=>$action);
	   
	   }else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	   
	 }
	 
public function messages_count(){ 
	    global $json_api;  
		  	
	if (function_exists('bp_is_active')) {	
	
	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
		
	  $count =  BP_Messages_Thread::get_inbox_count($user_id);
	   
	   return array('unread'=>$count);
	   
	   }else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	   
	 }
	 
public function avatar_upload(){ 
    global $json_api;

  if (function_exists('wp_get_image_editor')) {	
  	
	 if (!$_REQUEST['cookie']) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}
//d($_POST);
//d($_FILES);

//exit();
		$user_id = wp_validate_auth_cookie(urldecode($_REQUEST['cookie']), 'logged_in');
	
//	echo '$user_id: '.$user_id;
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
		
	 if (!$_FILES['avatar']) {
			$json_api->error("You must include 'avatar' file var in your POST form data.");
		}
		
  if ($_REQUEST['full']) $full = $_REQUEST['full'];
  elseif(defined( 'BP_AVATAR_FULL_WIDTH' )) $full = BP_AVATAR_FULL_WIDTH;
   else $full = 300;
  
  if ($_REQUEST['thumb']) $full = $_REQUEST['thumb'];
  elseif(defined( 'BP_AVATAR_THUMB_WIDTH' )) $thumb = BP_AVATAR_THUMB_WIDTH;
  else $thumb = 100;

 
  $uploadedfile = $_FILES['avatar'];
 
 
     if ( ! function_exists( 'wp_handle_upload' ) ) 
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
add_filter('upload_dir', 'pim_avatar_upload_dir');
$upload_overrides = array( 'test_form' => false );

   $upload_dir = wp_upload_dir();
  
   pim_empty_avatar_dir($upload_dir['path']);//remove current avatars

$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );


$avatar_file = wp_get_image_editor( $movefile['file'] );
$thumb_file = wp_get_image_editor( $movefile['file'] );
if ( ! is_wp_error( $avatar_file ) ) { 
	
	 $resized = $avatar_file->resize( $full, $full, true );
     $filename1 = $avatar_file->generate_filename( 'bpfull' );
     $img1 = $avatar_file->save($filename1);
	 
	 $resized = $thumb_file->resize( $thumb, $thumb, true );
	 $filename2 = $thumb_file->generate_filename( 'bpthumb' );
      $img2 = $thumb_file->save($filename2); 
}
unlink($movefile['file']);//delete original image

remove_filter('upload_dir', 'pim_avatar_upload_dir');


    return array( 'full'=>$upload_dir['url'].'/'.basename($filename1),
	              'thumb'=>$upload_dir['url'].'/'.basename($filename2)
				  	);
	
	}else {	  
	  $json_api->error("You must install WordPress v 3.5.0 or later to use 'avatar_upload' end point.");
	   }
	
   }
   
public function delete_account(){ 
	    global $json_api;  
		  	
	if (!function_exists('bp_is_active')) {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }

	
 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}

  if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'delete_account');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }
	  
	if ( is_super_admin( $user_id ) ) {
     
	if($json_api->query->delete_user_id) {
		$delete_user_id = (int) $json_api->query->delete_user_id;
		$action = wp_delete_user( $delete_user_id);
	 do_action( 'bp_core_deleted_account', $delete_user_id );  
	}else $json_api->error("Website super admin cannot delete himself. Provide some other user to be deleted by super admin. i.e. delete_user_id=  (IMPORTANT: This action is undoable and will delete BP user and its all meta.) ");
     
	 }else{  
	  
  $action = bp_core_delete_account( $user_id );
   do_action( 'bp_core_deleted_account', $user_id );  
	 }
	   return array('deleted'=>$action);
	   
	  	   
	 }  
	 
public function delete_user(){ 
	    global $json_api;  
	
 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
		
	if ($json_api->query->reassign) $reassign = (int) $json_api->query->reassign; 	

  if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'delete_user');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }
	  
	if ( is_super_admin( $user_id ) ) {
     
	if($json_api->query->delete_user_id) {
		$delete_user_id = (int) $json_api->query->delete_user_id;
		
		if($reassign) $action = wp_delete_user( $delete_user_id, $reassign );
		else $action = wp_delete_user( $delete_user_id);
	 
	}else $json_api->error("Website super admin cannot delete himself. Provide some other user to be deleted by super admin. i.e. delete_user_id=  (IMPORTANT: This action is undoable and will delete user and its all meta.) ");
     
	 }else{  
	  
 		if($reassign) $action = wp_delete_user( $user_id, $reassign );
		else $action = wp_delete_user( $user_id);
  
	 }
	   return array('deleted'=>$action);
	   
	  	   
	 } 	 
	 
public function search_user() {
   global $json_api, $wpdb;
   
   if (!$_REQUEST['search']) {
	$json_api->error("You must include a 'search' var in your request.");
		}
    
	 $query = "SELECT ID, user_login FROM " . $wpdb->prefix . "users WHERE user_login LIKE '%" . $_REQUEST['search'] . "%'";
	 
	 $result = $wpdb->get_results( $query );  
	 $oReturn = new stdClass();
	 foreach ($result as $k) {
			 $aTemp = new stdClass();
   
            $aTemp->user_id = $k->ID;
			$aTemp->username = $k->user_login;   
            $aTemp->avatar = bp_core_fetch_avatar(array('item_id'=>$k->ID,'html'=>false));
			
			$oReturn->users[] = $aTemp;
        }
     $oReturn->count = count($result);
	
    return $oReturn;
  }	
  
public function get_user_by() {
   global $json_api;
   
    if (!$_REQUEST['secret']) {

	$json_api->error("You must include 'secret' var in your request.");

		}elseif($_REQUEST['secret'] != $this->secret) $json_api->error("Error invalid 'secret'. You must include valid 'secret' var value in your request.");
		
   if (!$_REQUEST['field']) {
	$json_api->error("You must provide a 'field' var in your request. e.g. id | ID | slug | email | login");
		} $field = $_REQUEST['field'];
		
	 if (!$_REQUEST['value']) {
			$json_api->error("You must include a 'value' var in your request to be searched in any given 'field'.");
		}else $value = 	$_REQUEST['value'];
    
		 $result = get_user_by( $field, $value );  
	 $oReturn = new stdClass();
     $oReturn->user = $result;
	
    return $oReturn;
  }	
  
public function activities() { 

  global $json_api;

if (function_exists('bp_is_active')) {	

     $oReturn = new stdClass();
	 
	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
		
 if ($json_api->query->sort) $sort = $json_api->query->sort;	
 else $sort = 'DESC';//ASC, DESC
 
  if (!$json_api->query->comments) $comments = 'stream';
else $comments = $json_api->query->comments;//Accepted arguments: false
 
  if ($json_api->query->scope) {
	   if (!$json_api->query->user_id) $json_api->error("Error. 'scope' can only be used with 'user_id'.");
	  $scope = $json_api->query->scope;
  }
 else $scope = false;

 if ($json_api->query->limit) $limit = $json_api->query->limit;
else $limit = 10;

 if ($json_api->query->page) $page = $json_api->query->page;
else $page = 1;


$per_page = $limit;
 
        if (!bp_has_activities())  $json_api->error("No Activities found.");		 
		

        if ($page) {

            $aParams ['max'] = true;
            $aParams ['per_page'] = $limit;
			 $aParams ['page'] = $page;
            $iPages = $page;

        }


        $aParams ['display_comments'] = $comments;
        $aParams ['sort'] =  $sort;
		$aParams ['scope'] = $scope;
		
if ($json_api->query->include)	$aParams ['include'] = $json_api->query->include;
if ($json_api->query->search_terms)	$aParams ['search_terms'] = $json_api->query->search_terms;

    if ($json_api->query->user_id)   $aParams ['filter'] ['user_id'] = (int) $json_api->query->user_id;
   if ($json_api->query->component)  $aParams ['filter'] ['object'] = $json_api->query->component;
	
   if ($json_api->query->type)     $aParams ['filter'] ['action'] = $json_api->query->type;
     if ($json_api->query->primary_id)    $aParams ['filter'] ['primary_id'] = (int) $json_api->query->primary_id;
	  if ($json_api->query->secondary_id)     $aParams ['filter'] ['secondary_id'] = (int) $json_api->query->secondary_id;
		
        $iLimit = $limit;
	
//d($aParams);

            $aTempActivities = bp_activity_get($aParams);
		
            if (!empty($aTempActivities['activities'])) {
				
            $user_favs = array_values (bp_activity_get_user_favorites( $user_id ) );
			//print_r($user_favs);
			//exit();
                foreach ($aTempActivities['activities'] as $oActivity) {
					
					$aTemp = new stdClass();
					
                   $aTemp->activity_id = $oActivity->id;					
				   
				   $aTemp->component = $oActivity->component;
					
					$aTemp->action = strip_tags($oActivity->action);
					
					$aTemp->content = $oActivity->content;
					
				
                     
                    $aTemp->user[0]->user_id = (int) $oActivity->user_id; 
					  $aTemp->user[0]->username = $oActivity->user_login; 
					 $aTemp->user[0]->avatar = bp_core_fetch_avatar(array('item_id'=>$oActivity->user_id,'html'=>false));              
                    $aTemp->user[0]->display_name = $oActivity->display_name;

                   $aTemp->type = $oActivity->type;

                   $aTemp->time = $oActivity->date_recorded;
				   
				    $aTemp->time_since =  bp_core_time_since ( $oActivity->date_recorded ) ;

                    $aTemp->is_hidden = $oActivity->hide_sitewide === "0" ? false : true;

                   $aTemp->is_spam = $oActivity->is_spam === "0" ? false : true;
                
				   $aTemp->is_fav = array_search( (int)$aTemp->activity_id, $user_favs); 
				   
				   $aTemp->can_delete = pim_bp_activity_can_delete($user_id, (int)$aTemp->activity_id);
				   
					foreach($oActivity->children as $comment) {
						 $aComment = new stdClass();
						$aComment->id = (int) $comment->id;
						$aComment->component = $comment->component;
						$aComment->user_id = (int) $comment->user_id;
						$aComment->avatar = bp_core_fetch_avatar(array('item_id'=> $comment->user_id,'html'=>false));
						$aComment->content = strip_tags($comment->content);
						$aComment->action = strip_tags($comment->action);
						
						$aComment->item_id = (int) $comment->item_id;
						$aComment->secondary_item_id = (int) $comment->secondary_item_id;
						$aComment->is_fav = array_search( $aComment->id, $user_favs); 
						$aComment->can_delete = pim_bp_activity_can_delete($user_id, $aComment->id);
						$aComment->date_recorded = $comment->date_recorded;
						$aComment->time_since =  bp_core_time_since ($comment->date_recorded);
						$aComment->hide_sitewide = $comment->hide_sitewide;
						$aComment->display_name = $comment->display_name;
						$aComment->depth = $comment->depth;
						$aComment->is_spam = $comment->is_spam;
						$aComment->children = $comment->children;
						$aComment->mptt_left = $comment->mptt_left;
						$aComment->mptt_right = $comment->mptt_right;
		
						$aTemp->comments[] =  $aComment;
					}
					  
					 
					//  $aTemp->old_comments =  $oActivity->children;
	   $oReturn->activities[] = $aTemp;
                }

                $oReturn->has_more_items = $aTempActivities['has_more_items'];
				$oReturn->page = (int) $page;
				$oReturn->per_page = (int) $limit;

            } else {

                return $json_api->error("No Activities found.");	

            }
			

           return $oReturn;
 
}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this endpoint.");
	   }	

    }

public function activities_post_update(){
   global $json_api;
 $oReturn = new stdClass();
  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

   if (!$json_api->query->content ) {
  $json_api->error("Please include 'content' var in your request.");
  }else $content = $json_api->query->content;

  $args = array('user_id'=>$user_id,
                'content'=>$content);
				
 $oReturn->activity_id = bp_activity_post_update( $args );
 
 return $oReturn;    
   }
   
public function activities_delete_activity(){
   global $json_api;
 $oReturn = new stdClass();
 
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

 
  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
		if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

   if (!$json_api->query->activity_id ) {
  $json_api->error("Please include 'activity_id' var in your request.");
  }else $activity_id = $json_api->query->activity_id;

	
$can_delete = pim_bp_activity_can_delete($user_id, $activity_id);
 
if(!$can_delete) $json_api->error("User does not have capability to delete this activity. Only author or admin can delete."); 
			
 $oReturn->deleted =  bp_activity_delete_by_activity_id( $activity_id );
 
 return $oReturn;    
   }
   
public function activities_new_comment(){
   global $json_api;
 $oReturn = new stdClass();
 
  if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}
		
if (!$json_api->query->activity_id ) {
  $json_api->error("Please include 'activity_id' var in your request.");
  }else $activity_id = (int) $json_api->query->activity_id;

   if (!$json_api->query->content ) {
  $json_api->error("Please include 'content' var in your request.");
  }else $content = $json_api->query->content;

  $args = array('user_id'=>$user_id,
                'activity_id'=>$activity_id,
                'content'=>$content);
	
  if ($json_api->query->comment_id ) $args['id'] = (int)$json_api->query->comment_id;
  if ($json_api->query->parent_id ) $args['parent_id'] = (int)$json_api->query->parent_id;
				
 $oReturn->comment_id = bp_activity_new_comment( $args );
 
 return $oReturn;    
   }

public function activities_delete_comment(){
   global $json_api;
 $oReturn = new stdClass();
 
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

   if (!$json_api->query->activity_id ) {
  $json_api->error("Please include 'activity_id' var in your request.");
  }else $activity_id = (int)$json_api->query->activity_id;

   if (!$json_api->query->comment_id ) {
  $json_api->error("Please include 'comment_id' var in your request.");
  }else $comment_id = (int)$json_api->query->comment_id;
  
  $can_delete = pim_bp_activity_can_delete($user_id, $comment_id);
 
if(!$can_delete) $json_api->error("User does not have capability to delete this comment. Only author or admin can delete."); 
		   				
 $oReturn->deleted = bp_activity_delete_comment( $activity_id, $comment_id );
 
 return $oReturn;    
   }      

public function activities_get_user_favorites(){
   global $json_api;
 $oReturn = new stdClass();
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request. Use the `generate_auth_cookie` method.");
		}else 

  $user_id = (int)$json_api->query->user_id;
	
  
 $oReturn->favorites = bp_activity_get_user_favorites( $user_id );
 
 return $oReturn;    
   }
   
public function activities_total_favorites_for_user(){
   global $json_api;
 $oReturn = new stdClass();
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request. Use the `generate_auth_cookie` method.");
		}else 

  $user_id = (int)$json_api->query->user_id;
	
  
 $oReturn->favorites = bp_activity_total_favorites_for_user( $user_id );
 
 return $oReturn;    
   }
   
public function activities_add_user_favorite(){
   global $json_api;
 $oReturn = new stdClass();
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}
		
if (!$json_api->query->activity_id ) {
  $json_api->error("Please include 'activity_id' var in your request.");
  }else $activity_id = (int) $json_api->query->activity_id;

 $oReturn->favorite = bp_activity_add_user_favorite( $activity_id, $user_id );
 
 return $oReturn;    
   }
   
public function activities_remove_user_favorite(){
   global $json_api;
 $oReturn = new stdClass();
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}
		
if (!$json_api->query->activity_id ) {
  $json_api->error("Please include 'activity_id' var in your request.");
  }else $activity_id = (int) $json_api->query->activity_id;

 $oReturn->favorite = bp_activity_remove_user_favorite( $activity_id, $user_id );
 
 return $oReturn;    
   }    

public function activities_find_mentions(){
   global $json_api;
 $oReturn = new stdClass();
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->content) {
			$json_api->error("You must include a 'content' var in your request. ");
		}else $content = $json_api->query->content;
	
  
 $oReturn->users = bp_activity_find_mentions( $content );
 
 return $oReturn;    
   } 
 
public function activities_get_user_mentionname(){
   global $json_api;
 $oReturn = new stdClass();
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request. ");
		}else $user_id = (int)$json_api->query->user_id;
	
  
 $oReturn->mentionname = bp_activity_get_user_mentionname( $user_id );
 
 return $oReturn;    
   }
   
public function activities_get_userid_from_mentionname(){
   global $json_api;
 $oReturn = new stdClass();
 if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->mentionname) {
			$json_api->error("You must include a 'mentionname' var in your request.");
		}else $mentionname = $json_api->query->mentionname;
	
  
 $oReturn->user_id = bp_activity_get_userid_from_mentionname( $mentionname );
 
 return $oReturn;    
   }

public function activities_clear_new_mentions(){
   global $json_api;
   
 $oReturn = new stdClass();
  
  if (!function_exists('bp_is_active')) { $json_api->error("You must install and activate BuddyPress plugin to use this method.");	}

  if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request. ");
		}else $user_id = (int)$json_api->query->user_id;	
  
 $oReturn->clear = bp_activity_clear_new_mentions( $user_id );
 
 return $oReturn;    
   }
	  
public function friends() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

    if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		}else $user_id = (int) $json_api->query->user_id;

        $sFriends = bp_get_friend_ids($user_id);
        $aFriends = explode(",", $sFriends);
       
		if ($aFriends[0] == "") $json_api->error("No friend found.");
		
		if ($json_api->query->xprofile){
			$xprofile_fields = explode(",", $json_api->query->xprofile);
		}
 
		//d($xprofile_fields);
        foreach ($aFriends as $sFriendID) {
			 $aTemp = new stdClass();
            $oUser = get_user_by('id', $sFriendID);
            $aTemp->user_id = $oUser->data->ID;
			$aTemp->username = $oUser->data->user_login;
            $aTemp->display_name = $oUser->data->display_name;
            $aTemp->avatar = bp_core_fetch_avatar(array('item_id'=>$oUser->data->ID,'html'=>false));
			
			
	 if(is_array($xprofile_fields)){
	  
	  foreach($xprofile_fields as $k){
		  
		  $fields_data[$k] = xprofile_get_field_data( $k, $aTemp->user_id );
		  //echo '$k'.$k.'='.$fields_data[$k];
		  }
		  $aTemp->xprofile = $fields_data;
         }
			 
			$oReturn->friends[] = $aTemp;
        }
        $oReturn->count = count($aFriends);
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	    return $oReturn;
    }	
	
public function friends_add_friend() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

    if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
	  if (!$json_api->query->friend_id) {
			$json_api->error("You must include a 'friend_id' var in your request.");
		} else $friend_id = (int) $json_api->query->friend_id;	
	
      if ($json_api->query->force_accept) $force_accept= $json_api->query->force_accept;
		 else $force_accept= false;	
		
    $oReturn->response = friends_add_friend($user_id, $friend_id, $force_accept);        
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }

public function friends_remove_friend() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

    if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
	  if (!$json_api->query->friend_id) {
			$json_api->error("You must include a 'friend_id' var in your request.");
		} else $friend_id = (int) $json_api->query->friend_id;	
	
		
    $oReturn->response = friends_remove_friend($user_id, $friend_id);        
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }
	
public function friends_accept_friendship() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

    if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
	  if (!$json_api->query->friendship_id) {
			$json_api->error("You must include a 'friendship_id' var in your request.");
		} else $friendship_id = (int) $json_api->query->friendship_id;	
	
	global $wpdb, $bp;
	
	$oReturn->response = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->friends->table_name} SET is_confirmed = 1, date_created = %s WHERE id = %d AND friend_user_id = %d", bp_core_current_time(), $friendship_id, $user_id ) );
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }		
	
public function friends_reject_friendship() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

    if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
	  if (!$json_api->query->friendship_id) {
			$json_api->error("You must include a 'friendship_id' var in your request.");
		} else $friendship_id = (int) $json_api->query->friendship_id;	
	
	global $wpdb, $bp;
		
    $oReturn->response = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d AND friend_user_id = %d", $friendship_id, $user_id ) );
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	
	
public function friends_withdraw_friendship() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

    if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
	  if (!$json_api->query->friend_id) {
			$json_api->error("You must include a 'friend_id' var in your request.");
		} else $friend_id = (int) $json_api->query->friend_id;		
		
		global $bp, $wpdb;
		
	//	 $bp->loggedin_user->id = $user_id;
	$friendship_id = friends_get_friendship_id( $user_id, $friend_id );
	
	 $friendship    = new BP_Friends_Friendship( $friendship_id, true, false );
	
	 $sql = $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d AND initiator_user_id = %d", $friendship_id, $user_id );

	 if ( empty( $friendship->is_confirmed ) ) {
	 $oReturn->response = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d AND initiator_user_id = %d", $friendship_id, $user_id ) ); 
	 
	 }else $oReturn->response = 'confirmed already.';
      
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	
	
public function friends_check_friendship() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

    if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = (int) $json_api->query->user_id; 

		
	  if (!$json_api->query->friend_id) {
			$json_api->error("You must include a 'friend_id' var in your request.");
		} else $friend_id = (int) $json_api->query->friend_id;		
		
    $oReturn->response = friends_check_friendship($user_id, $friend_id );        
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	

public function friends_friendship_status() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

        if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = (int) $json_api->query->user_id; 
	
	  if (!$json_api->query->friend_id) {
			$json_api->error("You must include a 'friend_id' var in your request.");
		} else $friend_id = (int) $json_api->query->friend_id;		
		
    $oReturn->response = friends_check_friendship_status($user_id, $friend_id );        
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	

public function friends_friend_count() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

        if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = (int) $json_api->query->user_id; 
			
    $oReturn->response = friends_get_total_friend_count($user_id);        
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	
	
public function friends_has_friends() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

        if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = (int) $json_api->query->user_id; 
			
    $oReturn->response = friends_check_user_has_friends($user_id);        
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }		

public function friends_friendship_id() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

        if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request. user_id is initiator of friendship request.");
		} else $user_id = (int) $json_api->query->user_id; 
	
	  if (!$json_api->query->friend_id) {
			$json_api->error("You must include a 'friend_id' var in your request.");
		} else $friend_id = (int) $json_api->query->friend_id;		
		
    $oReturn->response = friends_get_friendship_id($user_id, $friend_id );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }

public function friends_friend_ids() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

        if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = (int) $json_api->query->user_id; 
	
	  if ($json_api->query->friend_requests_only) $friend_requests_only = $json_api->query->friend_requests_only;
		 else $friend_requests_only = false;
    
	 $assoc_arr = false;		
		
    $oReturn->friends = friends_get_friend_user_ids( $user_id, $friend_requests_only, $assoc_arr );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }
	
public function friends_search_friends() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

        if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = (int) $json_api->query->user_id; 
		
		if (!$json_api->query->search) {
			$json_api->error("You must include a 'search' var in your request.");
		} else $search = $json_api->query->search; 
	
	  if ($json_api->query->per_page) $per_page =(int) $json_api->query->per_page;
		 else $per_page = 10;
		 
	  if ($json_api->query->page) $page =(int) $json_api->query->page;
		 else $page = null;
    
		
    $oReturn = friends_search_friends( $search, $user_id, $per_page, $page );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }

public function friends_friendship_request_user_ids() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

        if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = (int) $json_api->query->user_id; 
		
    $oReturn->users = friends_get_friendship_request_user_ids( $user_id );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }
	
public function friends_listing(){ 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

        if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} else $user_id = (int) $json_api->query->user_id; 
	
	  if ($json_api->query->type) $type = $json_api->query->type;
		 else $type = 'active';
    
		if ($json_api->query->search) $filter = $json_api->query->search; 
		else $filter = ''; 
	
	  if ($json_api->query->per_page) $per_page =(int) $json_api->query->per_page;
		 else $per_page = 0;
		 
	  if ($json_api->query->page) $page =(int) $json_api->query->page;
		 else $page = 0;
		 
   $oReturn = BP_Core_User::get_users( $type, $per_page, $page, $user_id, $filter );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }
 
public function friends_get_bulk_last_active() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

       if (!$json_api->query->friend_ids) {
			$json_api->error("You must include a 'friend_ids' var in your request. ids must be comma separated.");
		} 
	
	$friend_ids = explode(',',$json_api->query->friend_ids);
		
    $oReturn->users = friends_get_bulk_last_active( $friend_ids );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }
		 				
public function friends_group_invite_list() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

       if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} 
	
	  if (!$json_api->query->group_id) {
			$json_api->error("You must include a 'group_id' var in your request.");
		} 
		
	$oReturn->friends = friends_get_friends_invite_list( $user_id, $group_id );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }

public function friends_count_invitable_friends() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

       if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} 
	
	  if (!$json_api->query->group_id) {
			$json_api->error("You must include a 'group_id' var in your request.");
		} 
		
	$oReturn->friends = friends_count_invitable_friends( $user_id, $group_id );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	

public function friends_friend_count_for_user() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

       if (!$json_api->query->user_id) {
			$json_api->error("You must include a 'user_id' var in your request.");
		} 
	
	$oReturn->friends = friends_get_friend_count_for_user( $user_id ) ;     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	

public function friends_is_friendship_confirmed() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

       if (!$json_api->query->friendship_id) {
			$json_api->error("You must include a 'friendship_id' var in your request.");
		} 
	
	$oReturn->friendship = friends_is_friendship_confirmed( $friendship_id ) ;     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	
	
public function members() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

   
   if ($json_api->query->limit) $limit = (int) $json_api->query->limit;
else $limit = 10;

 if ($json_api->query->page) $page = (int) $json_api->query->page;
else $page = 1;

 if ($json_api->query->type) $type = $json_api->query->type;
else $type = 'active';  

 
	 // $aParams ['max'] = true;
            $aParams ['per_page'] = $limit;
			 $aParams ['page'] = $page;
			 $aParams ['type'] = $type;
  
			  
		 if ($json_api->query->user_id)	 $aParams ['user_id'] = (int)$json_api->query->user_id;	
		  if ($json_api->query->search_terms)	  $aParams ['search_terms'] = $json_api->query->search_terms;		  
		  		 
		  if ($json_api->query->meta_key)	  $aParams ['meta_key'] = $json_api->query->meta_key;		  
		   if ($json_api->query->meta_value)	{
			   if(!$json_api->query->meta_key) $json_api->error("You must include a 'meta_key' var in your request. or remove 'meta_value' var.");
			    $aParams ['meta_value'] = $json_api->query->meta_value;
				}
		   
		     if ($json_api->query->include)	  $aParams ['include'] = $json_api->query->include;		  
		   if ($json_api->query->exclude)	 $aParams ['exclude'] = $json_api->query->exclude;	
          
	  
	    $sMembers = bp_core_get_users( $aParams );
		
		//d( $sMembers);		
		       
		if ( $sMembers['total'] == 0) $json_api->error("No member found.");
		
		if ($json_api->query->xprofile){
			$xprofile_fields = explode(",", $json_api->query->xprofile);
		}
 
		//d($xprofile_fields);
        foreach ( $sMembers['users'] as $sMemberID) {
			 $aTemp = new stdClass();
          
            $aTemp->user_id = (int) $sMemberID->ID;
			$aTemp->username = $sMemberID->user_login;
			$aTemp->user_nicename = $sMemberID->user_nicename;
            $aTemp->display_name = $sMemberID->display_name;
			$aTemp->fullname = $sMemberID->fullname;
			  $aTemp->is_friend = $sMemberID->is_friend;
			  $aTemp->last_activity = $sMemberID->last_activity;
			    $aTemp->total_friend_count = (int) $sMemberID->total_friend_count;
	
            $aTemp->avatar = bp_core_fetch_avatar(array('item_id'=>$sMemberID->ID,'html'=>false));
			
			
	 if(is_array($xprofile_fields)){
	  
	  foreach($xprofile_fields as $k){
		  
		  $fields_data[$k] = xprofile_get_field_data( $k, $aTemp->user_id );
		  //echo '$k'.$k.'='.$fields_data[$k];
		  }
		  $aTemp->xprofile = $fields_data;
         }
			 
			$oReturn->members[] = $aTemp;
        }
        $oReturn->total = (int) $sMembers['total'];
		$oReturn->page = (int) $page;
		$oReturn->per_page = (int) $limit;
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	    return $oReturn;
    }	
	
public function notifications() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

      if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
		
		$args = array('user_id'=>$user_id);
	
	 if ($json_api->query->is_new) $args['is_new'] = $json_api->query->is_new;
	 
	  if ($json_api->query->item_id) $args['item_id'] = $json_api->query->item_id;
	  
	   if ($json_api->query->component) $args['component_name'] = $json_api->query->component;
	 
	    if ($json_api->query->action) $args['component_action'] = $json_api->query->action;
		
		if ($json_api->query->search_terms) $args['search_terms'] = $json_api->query->search_terms;
		
		if ($json_api->query->order_by) $args['order_by'] = $json_api->query->order_by;
		else $args['order_by'] = 'date_notified';	
		if ($json_api->query->sort_order) $args['sort_order'] = $json_api->query->sort_order;
		else $args['sort_order'] = 'DESC';
			
		if ($json_api->query->page) $args['page'] = $json_api->query->page;
		
		if ($json_api->query->per_page) $args['per_page'] = $json_api->query->per_page;
	 
	 global $bp;

	$notifications = BP_Notifications_Notification::get($args);
	//$notifications = bp_notifications_get_notifications_for_user( $user_id, 'object');// $user_id, $format = 'object' ); 
	//return $oReturn->notifications = $notifications;
	    if(is_array($notifications)) {
			
			 foreach ( $notifications as $notification) {
			 $aTemp = new stdClass();
        
            $aTemp->id = (int) $notification->id;
			
		
			$aTemp->user_id = (int) $notification->user_id;
			$aTemp->item_id = (int) $notification->item_id;
			$aTemp->secondary_item_id = (int) $notification->secondary_item_id;
			 $aTemp->component_name = $notification->component_name;
			$aTemp->component_action = $notification->component_action;
			
			if($aTemp->component_name== 'groups'){
				$group = groups_get_group( array( 'group_id' => $aTemp->item_id ) );
			 $aTemp->sender_name = $group->name;
			  $aTemp->sender_avatar = bp_core_fetch_avatar(array('item_id'=>$aTemp->item_id,'object' => 'group','html'=>false));
			}elseif($aTemp->component_name== 'friends') {
				
			 $user = get_userdata( $aTemp->item_id );
			 
			 $aTemp->status = friends_check_friendship_status($aTemp->item_id, $user_id );
			 $aTemp->sender_name = $user->display_name;			 
			  $aTemp->sender_avatar = bp_core_fetch_avatar(array('item_id'=>$aTemp->item_id,'html'=>false));
				}else {
				 $user = get_userdata( $aTemp->secondary_item_id );			
			 $aTemp->sender_name = $user->display_name;
			  $aTemp->sender_avatar = bp_core_fetch_avatar(array('item_id'=>$aTemp->secondary_item_id,'html'=>false));
			}
			 
	           
			  $aTemp->date_notified = $notification->date_notified;
			    $aTemp->time_since = bp_core_time_since($notification->date_notified);
			  $aTemp->is_new = (int)$notification->is_new;
			  
			  $component_name = $aTemp->component_name;
			  $component_action_name = $aTemp->component_action;
			  
			  
			   $content = call_user_func(
                           $bp->{$component_name}->notification_callback,
                           $component_action_name,
                           $aTemp->item_id,
                         $aTemp->secondary_item_id,
                           $action_item_count,
                          'array'
                      );
			  
			   $aTemp->content = $content['text'];
			    $aTemp->href = $content['link'];
			 			 
			$oReturn->notifications[] = $aTemp;
        }
			
			
			}
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }

public function notifications_unread_count() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

      if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
	$oReturn->count = bp_notifications_get_unread_notification_count( $user_id );     
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	

public function notifications_delete() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

      if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
	  if (!$json_api->query->notification_id) {
			$json_api->error("You must include a 'notification_id' var in your request.");
		}else $notification_id = (int) $json_api->query->notification_id;

	 if ( ! bp_notifications_check_notification_access( $user_id, $notification_id ) ) {
          $json_api->error("Invalid access. user does not have access to this notification.");
     }
 
    $oReturn->response = BP_Notifications_Notification::delete( array( 'id' => $notification_id ) );	
	    
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	
		
public function notifications_mark_all() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

      if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
   $is_new = 0;
	
 
    $oReturn->response = BP_Notifications_Notification::mark_all_for_user( $user_id, $is_new )  ;	
	    
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }	
	
public function notifications_mark_all_by_type() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

      if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}

	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
		
	if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
		}
	
    if (!$json_api->query->component_name) {
			$json_api->error("You must include a 'component_name' var in your request. ");
		}else 	 $component_name= $json_api->query->component_name;
		
	 if (!$json_api->query->component_action) {
			$json_api->error("You must include a 'component_action' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}
		else $component_action=$json_api->query->component_action;
	
	$is_new = 0;
		
 
   // $oReturn->response = bp_notifications_mark_notifications_from_user( $user_id, $component_name, $component_action, $is_new );	
	 $oReturn->response =    BP_Notifications_Notification::update(
           array(
              'is_new' => $is_new
          ),
           array(
              'user_id'          => $user_id,
              'component_name'   => $component_name,
              'component_action' => $component_action
           )
           );
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	 return $oReturn;
    }		
	
public function settings_get_notifications() {
	 
	  global $json_api;
	  
	   if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');

	if (!$user_id) 	$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		

  $settings_keys = array(
        'notification_activity_new_mention',
        'notification_activity_new_reply' ,
        'notification_friends_friendship_request',
        'notification_friends_friendship_accepted',
        'notification_groups_invite',
        'notification_groups_group_updated',
        'notification_groups_admin_promotion',
        'notification_groups_membership_request',
        'notification_messages_new_message',
    );
 
    foreach( $settings_keys as $s ) {
 
      $meta = get_user_meta( $user_id,  $s );
	        $data['settings'][$s] =  $meta[0];
    }
	
	
 	   return  $data;	    
	  
	  }	

public function settings_update_notifications() {
	 
	  global $json_api;
	  
	   if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

		$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');

	if (!$user_id) 	$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		

  $settings_keys = array(
        'notification_activity_new_mention',
        'notification_activity_new_reply' ,
        'notification_friends_friendship_request',
        'notification_friends_friendship_accepted',
        'notification_groups_invite',
        'notification_groups_group_updated',
        'notification_groups_admin_promotion',
        'notification_groups_membership_request',
        'notification_messages_new_message',
    );
 
  if ($json_api->query->notification_activity_new_mention) 
       $settings['notification_activity_new_mention'] = $json_api->query->notification_activity_new_mention;
  
  if ($json_api->query->notification_activity_new_reply) 
       $settings['notification_activity_new_reply'] = $json_api->query->notification_activity_new_reply;
	   
  if ($json_api->query->notification_friends_friendship_request) 
       $settings['notification_friends_friendship_request'] = $json_api->query->notification_friends_friendship_request;
  
  if ($json_api->query->notification_friends_friendship_accepted) 
       $settings['notification_friends_friendship_accepted'] = $json_api->query->notification_friends_friendship_accepted;
	   
  if ($json_api->query->notification_groups_invite) 
       $settings['notification_groups_invite'] = $json_api->query->notification_groups_invite;
  if ($json_api->query->notification_groups_group_updated) 
       $settings['notification_groups_group_updated'] = $json_api->query->notification_groups_group_updated;
  if ($json_api->query->notification_groups_admin_promotion) 
       $settings['notification_groups_admin_promotion'] = $json_api->query->notification_groups_admin_promotion;
  if ($json_api->query->notification_groups_membership_request) 
       $settings['notification_groups_membership_request'] = $json_api->query->notification_groups_membership_request;
	   
  if ($json_api->query->notification_messages_new_message) 
       $settings['notification_messages_new_message'] = $json_api->query->notification_messages_new_message;	   	   	    
 
    foreach( $settings as $key=>$val ) {
 
       $data['settings'][$key] = update_user_meta( $user_id, $key, $val );
	      
    }
	
	
 	   return  $data;	    
	  
	  }	
	
public function users(){
	  
	  global $json_api;
	  
	  if (!$json_api->query->secret) {

			$json_api->error("You must include 'secret' var in your request.");

		}elseif($json_api->query->secret != $this->secret) $json_api->error("Error invalid 'secret'. You must include valid 'secret' var in your request.");
		
	   $oReturn = new stdClass();

if (!$json_api->query->blog_id) $blog_id = $GLOBALS['blog_id'];
	  else $blog_id = $json_api->query->blog_id;
	  
	if (!$json_api->query->blog_id) $blog_id = $GLOBALS['blog_id'];
	  else $blog_id = $json_api->query->blog_id;
	  
	  if (!$json_api->query->role) $role = 'author';
	  else $role = $json_api->query->role;
	  
	  if (!$json_api->query->per_page) {
			$per_page = 10;
		}else $per_page = (int) $json_api->query->per_page;
		
		 if (!$json_api->query->page) $page = 1;
     else $page = (int) $json_api->query->page;		
	 
	  if (!$json_api->query->order) $order = 'ASC';
     else $order = $json_api->query->order;
	 
	   if (!$json_api->query->orderby) $orderby = 'name';
     else $orderby = $json_api->query->orderby;	 
	 
		 
	   if ($json_api->query->meta_key) $meta_key = $json_api->query->meta_key;
	 else $meta_key = '';
	 
	   if ($json_api->query->meta_value) $meta_value = $json_api->query->meta_value;
	 else $meta_value = '';
	 
	   if ($json_api->query->meta_compare) $meta_compare = $json_api->query->meta_compare;
	 else $meta_compare = '';
	
	 if ($json_api->query->search) $search = $json_api->query->search;
	 else $search = '';
	 
	  if ($json_api->query->number) $number = (int) $json_api->query->number;
	 else $number = $per_page; 
	 
	 if(isset($json_api->query->include)){
	 if (is_array($json_api->query->include)) $include = $json_api->query->include;
	 else $include = explode(',',$json_api->query->include);
	 }
	 
	  if(isset($json_api->query->exclude)){
	  if (is_array($json_api->query->exclude)) $exclude = $json_api->query->exclude;
	 else $exclude = explode(',',$json_api->query->exclude);
	  }
	  
	$offset = ($page * $per_page) - $per_page;
	 
	$args = array(
	'blog_id'      => $blog_id,
	'role'         => $role,
	'meta_key'     => $meta_key,
	'meta_value'   => $meta_value,
	'meta_compare' => $meta_compare,
	    
	'include'      => $include,
	'exclude'      => $exclude,
	'orderby'      => $orderby,
	'order'        => $order,
	'offset'       => $offset,
	'search'       => $search,
	'number'       => $number,
	'count_total'  => true,
	'fields'       => 'all'	
	);
	

 $wp_user_query = new WP_User_Query( $args );
 $total_users = $wp_user_query->get_total();  
 
	 $users = $wp_user_query->results;

//print_r($users);


 foreach($users as $u){
		 $userid = $u->data->ID ;
  $post_id = get_user_meta($userid, 'wp_metronet_post_id', true);
  
 $avatar = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail' , false, '' );
   
 
	$description = nl2br(get_the_author_meta('description',  $userid));
	$aTemp = $u->data;
	$aTemp->user_pass = '';
	$aTemp->avatar = $avatar[0];
	$aTemp->description = $description;
	$aTemp->meta->twitter = get_the_author_meta( 'twitter', $userid );
	$aTemp->meta->facebook = get_the_author_meta( 'facebook', $userid );
	$aTemp->meta->linkedin = get_the_author_meta( 'linkedin', $userid );
	$aTemp->meta->googleplus = get_the_author_meta( 'googleplus', $userid );
	$aTemp->meta->xing = get_the_author_meta( 'xing', $userid );
	$aTemp->meta->youtube = get_the_author_meta( 'youtube', $userid );
	$aTemp->meta->vimeo = get_the_author_meta( 'vimeo', $userid );
	$aTemp->meta->wikipedia = get_the_author_meta( 'wikipedia', $userid );
	$oReturn->users[] = $aTemp;
		  }

	 //$oReturn->users = $users;
	 $oReturn->page = $page;
	 $oReturn->per_page = $per_page;
	  $oReturn->count_total = $total_users;
	 $oReturn->pages =   ceil($total_users/$per_page); 
	 
	
	  return  $oReturn;
	  }	  

public function em_event() {
    global $json_api, $wpdb;

if (!class_exists('EM_Event')) {
		$json_api->error("You must install and activate 'Events Manager' plugin first to use this endpoint.");
	}


		
	 if (!$json_api->query->id) {
			$json_api->error("You must include a 'id' var in your request.");
		}else $post_id = (int) $json_api->query->id;
	
	 
    $post = get_post($post_id);
	//print_r($post );
	
		 $aTemp = new stdClass();
		 $aTemp->event = $post;
		 
		
		 
		 $custom_fields = get_post_custom($post->ID);
		  // print_r($custom_fields );
		   foreach( $custom_fields as $key=>$val){			   
			   $cf[$key] = $val[0];			   
			   }
		 //print_r($cf );
		 $aTemp->custom_fields = $cf;
		 
		 $aTemp->thumb_url = wp_get_attachment_thumb_url($cf['_thumbnail_id'] );
		 
		
		 
		 $event_id = (int)$cf['_event_id'];
		
				$sql = "SELECT * FROM ". EM_BOOKINGS_TABLE ." WHERE event_id ='$event_id' ORDER BY booking_date";
			
				$bookings = $wpdb->get_results($sql, ARRAY_A);
		/*
			foreach ($bookings as $booking){
				$aTemp->attendees[] = $booking;
			}
			*/
	 
		 $aTemp->bookings = $bookings;
		   
		 $aTemp->location = get_event_location($cf['_location_id']);
		
		 $response = array();
	
		 $response['data'] =  $aTemp;
		

	
    if ($post) {
          return $response;
    } else {
      $json_api->error("Not found.");
    }
  }

public function em_event_tickets() {
    global $json_api, $wpdb;

if (!class_exists('EM_Event')) {
		$json_api->error("You must install and activate 'Events Manager' plugin first to use this endpoint.");
	}


		
	 if (!$json_api->query->event_id) {
			$json_api->error("You must include a 'event_id' var in your request.");
		}else $event_id = (int) $json_api->query->event_id;
	
	
		 $aTemp = new stdClass();
				 
				$sql = "SELECT * FROM ". EM_TICKETS_TABLE ." WHERE event_id ='$event_id' ORDER BY ticket_id";
			
				$event_tickets = $wpdb->get_results($sql, ARRAY_A);
		/*
			foreach ($bookings as $booking){
				$aTemp->attendees[] = $booking;
			}
			*/
	 
		 $aTemp->tickets = $event_tickets;
		
	

	
    if ($event_tickets) {
          return $aTemp;
    } else {
      $json_api->error("Not found.");
    }
  }
  
public function em_has_booking() {
    global $json_api, $wpdb, $blog_id, $EM_Event;

if (class_exists('EM_Event')) {
		global $EM_Event;
	}
	else $json_api->error("You must install and activate 'Events Manager' plugin first to use this endpoint.");
	

	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
   
    if (!$json_api->query->event_id) {
			$json_api->error("You must include a 'event_id' var in your request.");
		}else $event_id = (int) $json_api->query->event_id; 
		
	
	$EM_Event = new EM_Event($event_id);
	
	$result = $EM_Event->get_bookings()->has_booking($user_id) || get_option('dbem_bookings_double');
	
		
    return array(
      'booking' => $result
	  
    );
  }    

public function em_get_bookings() {
    global $json_api, $wpdb;

if (class_exists('EM_Event')) {
		global $EM_Event;
	}
	else $json_api->error("You must install and activate 'Events Manager' plugin first to use this endpoint.");
	

    if (!$json_api->query->event_id) {
			$json_api->error("You must include a 'event_id' var in your request.");
		}else $event_id = (int) $json_api->query->event_id; 
		

			$bookings = array();

				$sql = "SELECT * FROM ". EM_BOOKINGS_TABLE ." WHERE event_id ='".$event_id."' ORDER BY booking_date";

				$bookings = $wpdb->get_results($sql, ARRAY_A);

		
			
	return array(
      'bookings' => $bookings
	  
    );	
}

public function em_cancel_booking() {
    global $json_api, $EM_Event, $EM_Booking, $EM_Person;

if (class_exists('EM_Event')) {
		global $EM_Event;
	}
	else $json_api->error("You must install and activate 'Events Manager' plugin first to use this endpoint.");
	
 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
		
    if (!$json_api->query->booking_id) {
			$json_api->error("You must include a 'booking_id' var in your request.");
		}else $booking_id = (int) $json_api->query->booking_id; 
		

	$EM_Booking = em_get_booking($booking_id);
   $EM_Event = $EM_Booking->get_event();
		
	if( $EM_Booking->can_manage() || ($EM_Booking->person->ID == $user_id && get_option('dbem_bookings_user_cancellation')) ){

				if( $EM_Booking->cancel() ) $result = true;
          	else $result = false;

					
	}else	$json_api->error('You do not have permission to cancel this booking.');

			
	return array(
      'result' => $result
	  
    );	
}
    	  
public function add_em_event(){
	
	global $json_api, $wpdb, $blog_id;

if (class_exists('EM_Event')) {
		global $EM_Event;
	}
	else $json_api->error("You must install and activate 'Events Manager' plugin first to use this endpoint.");
	

	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
	
	
	if(!$this->disable_author_check){
		
	if (!user_can($user_id,'edit_posts')) {
    $json_api->error("You need to login with a user capable of creating posts.");
      }
	  
	}
		
	
    if(!$this->disable_nonce){
 if (!$json_api->query->nonce) {
			$json_api->error("You must include 'nonce' var in your request. Use the 'get_nonce' Core API method. ");
		}
 else $nonce =  sanitize_text_field( $json_api->query->nonce ) ;
 
 $nonce_id = $json_api->get_nonce_id('userplus', 'add_em_event');

 if( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {

    $json_api->error("Invalid access, unverifiable 'nonce' value. Use the 'get_nonce' Core API method. ");
        }
 }
 
 
 	if (!$json_api->query->blog_id) $blog_id = $blog_id;
	  else $blog_id = $json_api->query->blog_id;
	
	
	if (!$json_api->query->type) {
      $json_api->error("You must include 'type' value to create event. e.g. type=event or type=event-recurring");
    }else $post_type = $json_api->query->type;
	  
	if (!$json_api->query->title) {
      $json_api->error("You must include 'title' value to create post.");
    }else $post_title = $json_api->query->title;
		
	if (!$json_api->query->content) {
      $json_api->error("You must include 'content' value to create post.");
    }else $post_content = $json_api->query->content;
	
	if ($json_api->query->excerpt) $post_excerpt = $json_api->query->excerpt;
	
	
	if (!$json_api->query->status) {
      $json_api->error("You must include 'status' value to create post. 'draft' or 'publish'");
    }else $post_status = $json_api->query->status;
	
	if ($json_api->query->event_start_date) $event_start_date = $json_api->query->event_start_date;
	  else 	$json_api->error("You must include 'event_start_date' var in your request. e.g. event_start_date=2016-04-05");
	  
	  if ($json_api->query->event_end_date) $event_end_date = $json_api->query->event_end_date;
	  else 	$json_api->error("You must include 'event_end_date' var in your request. e.g. event_end_date=2016-04-10");
	
	 if ($json_api->query->event_start_time) $event_start_time = $json_api->query->event_start_time;
	  else 	$json_api->error("You must include 'event_start_time' var in your request. e.g. event_start_time=00:30:00");
	  
	  if ($json_api->query->event_end_time) $event_end_time = $json_api->query->event_end_time;
	  else 	$json_api->error("You must include 'event_end_time' var in your request. e.g. event_end_time=04:45:00");
	  
	  if ($json_api->query->location_id) $location_id = $json_api->query->location_id;
	  else 	$location_id =0;
	  
	  if ($json_api->query->group_id) $group_id = $json_api->query->group_id;
	  else 	$group_id =0;
	  
	  if ($json_api->query->event_rsvp) $event_rsvp = $json_api->query->event_rsvp;
	  else 	$event_rsvp =0;
	  
	  if ($json_api->query->event_rsvp_date) $event_rsvp_date = $json_api->query->event_rsvp_date;
	  else 	$event_rsvp_date = $event_start_date;
	  
	  
	  if ($json_api->query->event_rsvp_time) $event_rsvp_time = $json_api->query->event_rsvp_time;
	  else 	$event_rsvp_time ='00:00:00';
	  
	  if ($json_api->query->event_rsvp_spaces) $event_rsvp_spaces = $json_api->query->event_rsvp_spaces;
	  else 	$event_rsvp_spaces =0;
	  
	  if ($json_api->query->event_spaces) $event_spaces = $json_api->query->event_spaces;
	  else 	$event_spaces =0;
	  
	  if ($json_api->query->event_all_day) $event_all_day = 1;
	  else 	$event_all_day =0;
	  
	  if ($json_api->query->custom_booking_form) $custom_booking_form = $json_api->query->custom_booking_form;
	  else 	$custom_booking_form =0;
	  
	  
	$EM_Event = new EM_Event();


$EM_Event->event_start_date = $event_start_date;
$EM_Event->event_end_date =  $event_end_date;

$EM_Event->event_start_time = $event_start_time;
$EM_Event->event_end_time = $event_end_time;

$EM_Event->start = strtotime($EM_Event->event_start_date." ".$EM_Event->event_start_time);
$EM_Event->end = strtotime($EM_Event->event_end_date." ".$EM_Event->event_end_time);

$EM_Event->event_private = 0;
$EM_Event->event_status = 1;
$EM_Event->event_rsvp = $event_rsvp;
$EM_Event->event_rsvp_date = $event_rsvp_date;
$EM_Event->rsvp_end = $event_end_date;
$EM_Event->event_rsvp_time = $event_rsvp_time;
$EM_Event->event_rsvp_spaces = $event_rsvp_spaces;
$EM_Event->event_spaces = $event_spaces;
$EM_Event->event_all_day = $event_all_day;

$EM_Event->event_owner = $user_id;

// attach the event to a buddypress group

$EM_Event->group_id = $group_id;   
$EM_Event->location_id = $location_id;   


$EM_Event->post_content = $post_content;
$EM_Event->post_excerpt = $post_excerpt;
//$EM_Event->event_slug = $post_slug;
$EM_Event->event_name = $post_title;

// create the event post to link with the bp group

$EM_Event->save();
$EM_Event->save_meta();



// we need to catch back the event id
$result_event = $EM_Event;
$current_event = em_get_event($EM_Event->ID,'post_id');
$current_event_id = $current_event->event_id;

$EM_Ticket = new EM_Ticket();
$EM_Ticket->event_id = $current_event_id;
$EM_Ticket->ticket_name = $EM_Event->event_name;
$EM_Ticket->ticket_end = $EM_Event->rsvp_date;

 $EM_Ticket->save();

	 return array(
      'event' => $result_event,
	  'enabled_booking'=>$EM_Ticket
    );
	}

public function em_delete_event() {
    global $json_api, $EM_Event, $EM_Booking, $EM_Person;

if (class_exists('EM_Event')) {
		global $EM_Event;
	}
	else $json_api->error("You must install and activate 'Events Manager' plugin first to use this endpoint.");
	
 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
		
    if (!$json_api->query->event_id) {
			$json_api->error("You must include 'event_id' var in your request.");
		}else $event_id = (int) $json_api->query->event_id; 

   $EM_Event = new EM_Event($event_id);
   
   
   if(!$EM_Event->event_id) $json_api->error('Event does not exist. Please provide correct Event ID.');
   
    if($EM_Event->event_status<0) $json_api->error('Event has already been deleted. ');
		
	if( $EM_Event->can_manage() || ( $EM_Event->event_owner == $user_id ) ){

				if( $EM_Event->delete() ) $result = true;
          	else $result = false;

					
	}else	$json_api->error('You do not have permission to delete this event. Only Admin or Event owner can delete event.');

			
	return array(
      'result' => $result
	  
    );	
}		
  
public function book_em_event() {
    global $json_api, $wpdb, $blog_id, $EM_Event;

if (class_exists('EM_Event')) {
		global $EM_Event;
	}
	else $json_api->error("You must install and activate 'Events Manager' plugin first to use this endpoint.");
	

	 if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
		}
	
	$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	//echo '$user_id: '.$user_id;	
	
		if (!$user_id) {
			$json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` method.");
		}
   
    if (!$json_api->query->event_id) {
			$json_api->error("You must include a 'event_id' var in your request.");
		}else $event_id = (int) $json_api->query->event_id; 
		
		 if (!$json_api->query->ticket_id) {
			$json_api->error("You must include a 'ticket_id' var in your request.");
		}else $ticket_id = (int) $json_api->query->ticket_id;
		
		if (!$json_api->query->ticket_spaces) {
			$json_api->error("You must include a 'ticket_spaces' var in your request.");
		}else $ticket_spaces = (int) $json_api->query->ticket_spaces; 
	

		$EM_Booking = em_get_booking(array('person_id'=>$user_id, 'event_id'=>$event_id, 'booking_spaces'=>$ticket_spaces)); //new booking

		$EM_Ticket = $EM_Event->get_bookings()->get_tickets()->get_first();	

				//get first ticket in this event and book one place there. similar to getting the form values in EM_Booking::get_post_values()

				$EM_Ticket_Booking = new EM_Ticket_Booking(array('ticket_id'=>$ticket_id, 'ticket_booking_spaces'=>$ticket_spaces));

				$EM_Booking->tickets_bookings = new EM_Tickets_Bookings();

				$EM_Booking->tickets_bookings->booking = $EM_Ticket_Booking->booking = $EM_Booking;

				$EM_Booking->tickets_bookings->add( $EM_Ticket_Booking );

				//Now save booking

				if( $EM_Event->get_bookings()->add($EM_Booking) ){
					$result = true;

				}else{
         	$result = false;
				}
	
		
    return array(
      'booking' => $result
	  
    );
  }  

public function taxonomies(){
  global $json_api;	  
  
  if ($json_api->query->operator=='or') $operator = 'or';
  else $operator = 'and';
 
  if ($json_api->query->output=='objects') $output = 'objects';
  else $output = 'names';

  $args = array(
   'public'   => true,
  '_builtin' => false
  );
  
   $taxonomies = get_taxonomies( $args, $output, $operator );

  return array('taxonomies'=>$taxonomies);
  
  }	  

public function terms(){
  global $json_api;	  
  
  if ($json_api->query->taxonomy) $taxonomy = $json_api->query->taxonomy;
  else $taxonomy = null;
 
  if ($json_api->query->hide_empty) $hide_empty = true;
  else $hide_empty = false;
  
  if ($json_api->query->hierarchical) $hierarchical = true;
  else $hierarchical = false;
  
  if ($json_api->query->pad_counts) $pad_counts = true;
  else $pad_counts = false;
  
 if (!$json_api->query->per_page) {
			$per_page = 20;
		}else $per_page = (int) $json_api->query->per_page;

 if (!$json_api->query->page) $page = 1;
     else $page = (int) $json_api->query->page;		
	 
	  if (!$json_api->query->order) $order = 'ASC';
     else $order = $json_api->query->order;
	 
	   if (!$json_api->query->orderby) $orderby = 'ID';
     else $orderby = $json_api->query->orderby;	 
	 
		 
	   if ($json_api->query->meta_key) $meta_key = $json_api->query->meta_key;
	 else $meta_key = '';
	 
	   if ($json_api->query->meta_value) $meta_value = $json_api->query->meta_value;
	 else $meta_value = '';
	 
	   if (is_array($json_api->query->meta_query)) $meta_query = $json_api->query->meta_query;
	 else $meta_query = '';
	
	
	if ($json_api->query->name) $name = $json_api->query->name;
	 else $name = ''; 
	 
	 if ($json_api->query->slug) $slug = $json_api->query->slug;
	 else $slug = ''; 
	 
	 if ($json_api->query->search) $search = $json_api->query->search;
	 else $search = '';
	 
	  if ($json_api->query->number) $number = (int) $json_api->query->number;
	 else $number = ''; 
	 
	 if ($json_api->query->name_like) $name_like = $json_api->query->name_like;
	 else $name_like = '';
	 
	 if ($json_api->query->description_like) $description_like = $json_api->query->description_like;
	 else $description_like = '';
	 
	 if(isset($json_api->query->include)){
	 if (is_array($json_api->query->include)) $include = $json_api->query->include;
	 else $include = explode(',',$json_api->query->include);
	 }
	 
	  if(isset($json_api->query->exclude)){
	  if (is_array($json_api->query->exclude)) $exclude = $json_api->query->exclude;
	 else $exclude = explode(',',$json_api->query->exclude);
	  }
	  
	   if(isset($json_api->query->exclude_tree)){
	  if (is_array($json_api->query->exclude_tree)) $exclude_tree = $json_api->query->exclude_tree;
	 else $exclude_tree = explode(',',$json_api->query->exclude_tree);
	  }
	  
	  if (!$json_api->query->child_of) $child_of = 0;
     else $child_of = (int) $json_api->query->child_of;		

   if (!$json_api->query->parent) $parent = '';
     else $parent = (int) $json_api->query->parent;		

/* if ($json_api->query->offset) $offset = (int) $json_api->query->offset;
	 else $offset = 0; 
	*/ 
	$offset = ($page * $per_page) - $per_page;
	
	 
	 
	$args = array(
	'taxonomy' => $taxonomy,
    'hide_empty' => $hide_empty,
            
	'meta_key'     => $meta_key,
	'meta_value'   => $meta_value,
	'meta_query' => $meta_query,
	    
	'include'      => $include,
	'exclude'      => $exclude,
	'orderby'      => $orderby,
	'order'        => $order,
	'offset'       => $offset,
	'search'       => $search,
       'exclude_tree'           => $exclude_tree,
        'number'                 => $per_page,
        
        'name'                   => $name,
        'slug'                   => $slug,
        'hierarchical'           => $hierarchical,
        'name__like'             => $name_like,
        'description__like'      => $description_like,
        'pad_counts'             => $pad_counts,
        'child_of'               => $child_of,
        'parent'                 => $parent,
        'childless'              => $childless,
        'update_term_meta_cache' => true,
       	'fields'       => 'all'	
	);


   $terms = get_terms( $args );

  $count = wp_count_terms( $taxonomy, array('hide_empty'=>$hide_empty) );
  
  return array('count'=>$count,
               'per_page'=> $per_page,
			   'page'=> $page,
               'terms'=>$terms);
  
  }	

public function groups() { 
		global $json_api;
        
        $oReturn = new stdClass();

  if (function_exists('bp_is_active')) {	

   
   if ($json_api->query->per_page) $per_page = (int) $json_api->query->per_page;
else $limit = 20;

 if ($json_api->query->page) $page = (int) $json_api->query->page;
else $page = 1;

 if ($json_api->query->type)  $aParams ['type'] = $json_api->query->type;
  
 if ($json_api->query->order)  $aParams ['order'] = $json_api->query->order;
 if ($json_api->query->orderby)  $aParams ['orderby'] = $json_api->query->orderby;

            $aParams ['per_page'] = $per_page;
			 $aParams ['page'] = $page;
			  
		 if ($json_api->query->user_id)	 $aParams ['user_id'] = (int)$json_api->query->user_id;	
		  if ($json_api->query->search_terms)	  $aParams ['search_terms'] = $json_api->query->search_terms;		  
		  		 
		  if ($json_api->query->meta_query)	  $aParams ['meta_query'] = $json_api->query->meta_query;
		    if ($json_api->query->show_hidden)	  $aParams ['show_hidden'] = $json_api->query->show_hidden;		  
		  		   
		     if ($json_api->query->include)	  $aParams ['include'] = $json_api->query->include;		  
		   if ($json_api->query->exclude)	 $aParams ['exclude'] = $json_api->query->exclude;	
          
	  
	    $groups = groups_get_groups( $aParams );
		
       $oReturn = $groups;
     
		//$oReturn->groups->page = (int) $page;
		//$oReturn->groups->per_page = (int) $per_page;
       
		}else {	  
	  $json_api->error("You must install and activate BuddyPress plugin to use this method.");
	   }
	    return $oReturn;
    }	
	  
public function groups_create_group() {
    global $json_api;
        
        $oReturn = new stdClass();

if (!function_exists('bp_is_active') && !bp_is_active( 'groups' )) { $json_api->error("You must install and activate BuddyPress plugin to use this method and activate Groups component.");	}
    
  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}
		
if (!$json_api->query->name ) {
  $json_api->error("Please include 'name' var in your request.");
  }else $name = $json_api->query->name;
  
 if (!$json_api->query->status ) {
  $json_api->error("Please include 'status' var in your request. Accepts 'public', 'private' or 'hidden'. ");
  }else $status = $json_api->query->status;

  
  
  $args = array(
                'group_id'     => 0,
		'creator_id'   => $user_id,
		'name'         => $name,
		'description'  => '',
		'slug'         => $slug,
		'status'       => $status,
		'parent_id'    => 0,
		'enable_forum' => 0,
		'date_created' => bp_core_current_time()
		);

if ($json_api->query->description ) $args['description'] = $json_api->query->description;	
if ($json_api->query->slug ) $args['slug'] = $json_api->query->slug;	
	
if ($json_api->query->enable_forum ) $args['enable_forum'] = (int)$json_api->query->enable_forum;
  if ($json_api->query->parent_id ) $args['parent_id'] = (int)$json_api->query->parent_id;
  if ($json_api->query->date_created ) $args['date_created'] = (int)$json_api->query->date_created;
			
 $oReturn->group_id =  groups_create_group( $args );
 
 return $oReturn;    
    }
	
public function groups_update_group() {
    global $json_api;
        
        $oReturn = new stdClass();

if (!function_exists('bp_is_active') && !bp_is_active( 'groups' )) { $json_api->error("You must install and activate BuddyPress plugin to use this method and activate Groups component.");	}
    
  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

if (!$json_api->query->group_id ) {
  $json_api->error("Please include 'group_id' var in your request.");
  }else $group_id = (int) $json_api->query->group_id;
  
  		
if (!$json_api->query->name ) {
  $json_api->error("Please include 'name' var in your request.");
  }else $group_name = $json_api->query->name;
  


   if (!$json_api->query->status ) {
  $json_api->error("Please include 'status' var in your request. Accepts 'public', 'private' or 'hidden'. ");
  }else $status = $json_api->query->status;


  
  $args = array(
                'group_id'     => $group_id,
		'creator_id'   => $user_id,
		'name'         => $group_name,
		'description'  => '',
		'slug'         => $slug,
		'status'       => $status,
		'parent_id'    => 0,
		'enable_forum' => 0,
		'date_created' => bp_core_current_time()
		);

if ($json_api->query->description ) $args['description'] = $json_api->query->description;	
if ($json_api->query->slug ) $args['slug'] = $json_api->query->slug;	
	
if ($json_api->query->enable_forum ) $args['enable_forum'] = (int)$json_api->query->enable_forum;
  if ($json_api->query->parent_id ) $args['parent_id'] = (int)$json_api->query->parent_id;
 if ($json_api->query->date_created ) $args['date_created'] = (int)$json_api->query->date_created;
			
			
 $group_updated =  groups_create_group( $args );
 
 
 if ($json_api->query->notify_members ) {
	 
          groups_edit_base_group_details( $group_id, $group_name, $json_api->query->description, $json_api->query->notify_members );
       }
 $oReturn->group_id = $group_updated;
 return $oReturn;    
    }	
	
public function groups_edit_group_settings() {
    global $json_api;
        
        $oReturn = new stdClass();

if (!function_exists('bp_is_active') && !bp_is_active( 'groups' )) { $json_api->error("You must install and activate BuddyPress plugin to use this method and activate Groups component.");	}
    
  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

if (!$json_api->query->group_id ) {
  $json_api->error("Please include 'group_id' var in your request.");
  }else $group_id = (int) $json_api->query->group_id;
  
  		
if (!$json_api->query->enable_forum ) {
  $json_api->error("Please include 'enable_forum' var in your request. possible values true or false.");
  }else $enable_forum = $json_api->enable_forum;
  
 if (!$json_api->query->status ) {
  $json_api->error("Please include 'status' var in your request. Accepts 'public', 'private' or 'hidden'. ");
  }else $status = $json_api->query->status;

 if (!$json_api->query->invite_status ) {
  $json_api->error("Please include 'invite_status' var in your request. Who is allowed to send invitations to the group. 'members', 'mods', or 'admins'. ");
  }else $invite_status = $json_api->query->invite_status;

  $oReturn->group_settings_updated = groups_edit_group_settings( $group_id, $enable_forum, $status, $invite_status );
    
 return $oReturn;    
    }	
	
public function groups_delete_group() {
    global $json_api;
        
        $oReturn = new stdClass();

if (!function_exists('bp_is_active') && !bp_is_active( 'groups' )) { $json_api->error("You must install and activate BuddyPress plugin to use this method and activate Groups component.");	}
    
  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

if (!$json_api->query->group_id ) {
  $json_api->error("Please include 'group_id' var in your request.");
  }else $group_id = (int) $json_api->query->group_id;
  

$group_deleted = false;

if( groups_is_user_admin( $user_id, $group_id ) ) { 
  $group_deleted = groups_delete_group( $group_id );
  }
else $json_api->error('user_id: '.$user_id.' is not group Admin of group_id: '.$group_id);

  $oReturn->group_deleted = $group_deleted;
    
 return $oReturn;    
    }	
	
public function wlm_update_user_meta(){
	 global $json_api, $WishListMemberInstance;
	 

 if(class_exists(WishListMemberDBMethods)){
	 
	 
	  if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.");
		}

  $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
	
   if (!$user_id) {
			$json_api->error("Invalid cookie. Use the `generate_auth_cookie` method.");
		}

	 if(is_array($_REQUEST['wpm_useraddress'])){
			foreach ((array) $_REQUEST['wpm_useraddress'] as $k => $v) {
				$_REQUEST['wpm_useraddress'][$k] = stripslashes($v);
			}
			
		$data['wpm_useraddress'] = $WishListMemberInstance->Update_UserMeta( $user_id, 'wpm_useraddress', $_REQUEST['wpm_useraddress']);
	 }
			// custom fields
	if(is_array($_REQUEST['wlm_custom_fields_profile'])){
		
	foreach ($_REQUEST['wlm_custom_fields_profile'] as $field=>$val) {
					$data['custom_'.$field] = $WishListMemberInstance->Update_UserMeta( $user_id, 'custom_' . $field, $val );
				
				}
			}
						
 } else $json_api->error("You must install and activate 'wishlist-member' plugin before using this endpoint.");	

return $data; 

	
	}	
	
public function wlm_get_user_meta(){	
 global $json_api, $WishListMemberInstance;

 
 if(class_exists(WishListMemberDBMethods)){
	 
	 if (!$json_api->query->user_id) {
			$json_api->error("You must include 'user_id' var in your request. ");
		}else $user_id = (int) $json_api->query->user_id;
  
  if (!$json_api->query->user_meta) {
			$json_api->error("You must include 'user_meta' var in your request.");
		}else $user_meta = $json_api->query->user_meta;
	 
$data[$user_meta] = $WishListMemberInstance->Get_UserMeta( $user_id, $user_meta);
 }
 else $json_api->error("You must install and activate 'wishlist-member' plugin before using this endpoint.");	

return $data; 
  }
  
public function wlm_get_levels(){	
 global $json_api;

 if(!class_exists(WLMAPIMethods))  $json_api->error("You must install and activate 'wishlist-member' plugin before using this endpoint.");	
		 
$data = wlmapi_get_levels();
 

return $data; 
  }  

public function wlm_add_user_to_level(){	
 global $json_api;

 if(!class_exists(WLMAPIMethods))  $json_api->error("You must install and activate 'wishlist-member' plugin before using this endpoint.");	

 if (!$json_api->query->user_id) {
			$json_api->error("You must include 'user_id' var in your request. ");
		}else $user_id = (int) $json_api->query->user_id;
 
 if (!$json_api->query->level_id) {
			$json_api->error("You must include 'level_id' var in your request. Use 'wlm_get_levels' endpoint to get level_id.");
		}else $level_id = (int) $json_api->query->level_id;
		
if ($json_api->query->level_pending)  $level_pending =  true;
else 		$level_pending =  false;

if ($json_api->query->level_confirm_email)  $level_confirm_email =  true;
else 		$level_confirm_email =  false;
		
$args = array(
          'Users' => array($user_id),
          'Pending' => $level_pending,
		  'UnConfirmed' => $level_confirm_email
     );
$data = wlmapi_add_member_to_level($level_id, $args);
 

return $data; 
  }  
  
  
 }//end class