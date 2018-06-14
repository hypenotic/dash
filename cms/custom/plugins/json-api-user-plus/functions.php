<?php



function pim_avatar_upload_dir($upload_dir) {

	$user_id = wp_validate_auth_cookie(urldecode($_REQUEST['cookie']), 'logged_in');

	$upload_dir['subdir']	= '/avatars/'.$user_id;

	$upload_dir['path']		= $upload_dir['basedir'] . $upload_dir['subdir'];

	$upload_dir['url']		= $upload_dir['baseurl'] . $upload_dir['subdir'];

	return $upload_dir;

}





function pim_empty_avatar_dir($dirname) {

   $files = glob($dirname.'/*'); // get all file names

foreach($files as $file){ // iterate files

  if(is_file($file))

    unlink($file); // delete file

   }

 }

 

 

function pim_bp_get_message_date($message_id) {

   global $wpdb;   

   $query =  "SELECT date_sent FROM {$wpdb->prefix}bp_messages_messages WHERE id = '$message_id'";

$result =  $wpdb->get_row($query, ARRAY_A);

 return get_date_from_gmt( $result['date_sent']); 



}



function pim_sort_by_id($a, $b) {

  if ( $a->id < $b->id ) return 1;

    if ( $a->id > $b->id ) return -1;

    return 0; // equality

}



function pim_bp_activity_can_delete($user_id, $item_id){

	

global $wpdb, $bp;



$can_delete = 0; 



$activity = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE id = %d", $item_id));



 if($activity->user_id == $user_id) $can_delete = 1;

 else $can_delete = user_can( $user_id, 'delete_posts' );

	

	return $can_delete;

	

 }

 

 function pim_notify_admin_on_post($user_id){

   $user = get_userdata( $user_id );

   $admin_email = get_option( 'admin_email' );

   if (!user_can( $user_id, 'administrator' )){// avoid sending emails when admin is updating user profiles

        $to = $admin_email;

        $subject = 'A new Post was submitted by '.$user->display_name;

        $message = "The user : " .$user->display_name . " has submitted a new post. Please review and publish it.";

        foreach($_POST as $key => $value){

			

			if($key == 'cookie' || $key == 'key') continue;

            $message .= "\n"."<b>".$key . ":</b> ". $value ;

        }

        wp_mail( $to, $subject, $message);

    }

}

function get_event_location($location_id){
	global $wpdb;
	$sql = "SELECT * FROM ". EM_LOCATIONS_TABLE ." WHERE location_id ='$location_id'";
	//	echo 	$sql;
	$location = $wpdb->get_row($sql, ARRAY_A);
	return $location;
	}