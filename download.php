<?php
	require_once('../../../wp-config.php');
	global $table_prefix,$wpdb,$user_ID;	
	// set table name	
	$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
	$id=$_GET['id'];
	//type of link
	$downloadtype = get_option('wp_dlm_type');	
	// Check passed data is safe
	$go=false;
	switch ($downloadtype) {
		case ("Title") :
			$id=urldecode($id);
			$go=true;
		break;
		case ("Filename") :
			//nothing
			$go=true;
		break;
		default :
			if (is_numeric($id) && $id>0) $go=true;
		break;
	}	
	if (isset($id) && $go==true) {
		// set table name	
		$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
		
		
		switch ($downloadtype) {
					case ("Title") :
							// select a download
							$query_select_1 = sprintf("SELECT * FROM %s WHERE title='%s';",
								mysql_real_escape_string( $wp_dlm_db ),
								mysql_real_escape_string( $id ));
					break;
					case ("Filename") :
							// select a download
							//$query_select_1 = sprintf("SELECT * FROM %s WHERE filename LIKE '%s' LIMIT 1;",
							$query_select_1 = sprintf("SELECT * FROM %s WHERE filename LIKE '%s' ORDER BY LENGTH(filename) ASC LIMIT 1;",
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
				
				// Check permissions
				if ($d->members && !isset($user_ID)) {
					echo "You must be logged in to download this file";
					exit();
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
			   /* Redirect to the link URL THIS CODE IS BROKEN - 0kb downlaods
				$location= $d->filename;
				$mm_type="application/octet-stream";
				header("Cache-Control: public, must-revalidate");
				header("Pragma: hack");
				header("Content-Type: " . $mm_type);
				header('Content-Disposition: attachment; filename="'.basename($location).'"');
				header("Content-Transfer-Encoding: binary\n");
				readfile($location);
				exit();*/
		} else echo 'Download does not exist!';
   }
   else echo 'Download does not exist!';
?>