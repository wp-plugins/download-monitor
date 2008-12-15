<?php
	if(file_exists('../../../wp-config.php')) {
		require_once("../../../wp-config.php");
	} else if(file_exists('../../wp-config.php')) {
		require_once("../../wp-config.php");
	} else if(file_exists('../wp-config.php')) {
		require_once("../wp-config.php");
	} else if(file_exists('wp-config.php')) {
		require_once("wp-config.php");
	} else if(file_exists('../../../../wp-config.php')) {
		require_once("../../../../wp-config.php");
	} else if(file_exists('../../../../wp-config.php')) {
		require_once("../../../../wp-config.php");
	} else {
		echo '<p>Cannnot find wp-config.php. Maybe a config error with "custom download url" setting.</p>';
		exit;
	}
	include_once('classes/linkValidator.class.php');
		
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
		if (!empty($d) && is_numeric($d->id) ) {
					
				// FIXED:1.6 - Admin downloads don't count
				if (isset($user_ID)) {
					$user_info = get_userdata($user_ID);
					$level = $user_info->user_level;
				}
				
				// Check permissions
				if ($d->members && !isset($user_ID)) {
					$url = get_option('wp_dlm_member_only');
					if (!empty($url)) {
						$url = 'Location: '.$url;
						header( $url );
						exit();
   					} else echo 'You must be logged in to download this file.';
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
			   
			   // Select a mirror
			   $mirrors = trim($d->mirrors);
			   if (!empty($mirrors)) {
			   
			   
			   		$mirrors = explode("\n",$mirrors);
			   		array_push($mirrors,$d->filename);
			   		$mirrorcount = sizeof($mirrors)-1;
			   		$thefile = $mirrors[rand(0,$mirrorcount)];

			   		// Check random mirror is OK or choose another
			   		$checking=true;
			   		$loop = 0;
			   		while ($checking) {
 						$linkValidator = new linkValidator();
						$linkValidator->linkValidator($thefile);
						if (!$linkValidator->status()) {
						
							// Failed - use another mirror
							if ($mirrorcount<$loop) {
								$thefile = $mirrors[$loop];
								$loop++;
							} else {
								// All broken
								$thefile = $d->filename;
								$checking = false;
							}
						
						} else {
							$checking = false;
						}

					}
					// Do we have a link?		
					if (strlen($thefile)<4) $thefile = $d->filename;			   		
			   			   		
			   } else {
			   		$thefile = $d->filename;
			   };
			   
			   // Link to download
        	   $location= 'Location: '.$thefile;
        	   header($location);
        	   exit();
		}
   }
   $url = get_option('wp_dlm_does_not_exist');
   if (!empty($url)) {
   		$url = 'Location: '.$url;
		header( $url );
		exit();
   } else echo 'Download does not exist!';
   exit();
?>