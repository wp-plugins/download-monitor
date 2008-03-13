<?php
	require_once('../../../wp-config.php');
	global $table_prefix,$wpdb,$user_ID;	
	// set table name	
	$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
	$id=$_GET['id'];
	if (isset($id)) {
		// set table name	
		$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
		
		//type of link
		$downloadtype = get_option('wp_dlm_type');	
		switch ($downloadtype) {
					case ("Title") :
							// select a download
							$query_select_1 = sprintf("SELECT * FROM %s WHERE title='%s';",
								mysql_real_escape_string( $wp_dlm_db ),
								mysql_real_escape_string( $id ));
					break;
					case ("Filename") :
							// select a download
							$query_select_1 = sprintf("SELECT * FROM %s WHERE filename LIKE '%s' LIMIT 1;",
								mysql_real_escape_string( $wp_dlm_db ),
								mysql_real_escape_string( "%".$id ));
					break;
					default :
							// select a download
							$query_select_1 = sprintf("SELECT * FROM %s WHERE id=%s;",
								mysql_real_escape_string( $wp_dlm_db ),
								mysql_real_escape_string( $id ));
					break;
				}	

		$d = $wpdb->get_row($query_select_1);
		if (!empty($d)) {
		
				// FIXED:1.6 - Admin downloads don't count
				if (isset($user_ID)) {
					$user_info = get_userdata($user_ID);
					$level = $user_info->user_level;
				}
				if ($level!=10) {
					$hits = $d->hits;
					$hits++;
					// update download hits
					$query_update = sprintf("UPDATE %s SET hits=%s WHERE id=%s;",
						mysql_real_escape_string( $wp_dlm_db ),
						mysql_real_escape_string( $hits ),
						mysql_real_escape_string( $d->id ));
				   $wpdb->query($query_update);
			   }
        	   $location= 'Location: '.$d->filename;
        	   header($location);
        	   exit();
		} else echo 'Download does not exist!';
   }
   else echo 'Download does not exist!';
?>