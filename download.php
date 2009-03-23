<?php
/*	if(file_exists('../../../wp-config.php')) {
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
	}*/
	
if(file_exists('../../../wp-load.php')) {
	require_once("../../../wp-load.php");
} else if(file_exists('../../wp-load.php')) {
	require_once("../../wp-load.php");
} else if(file_exists('../wp-load.php')) {
	require_once("../wp-load.php");
} else if(file_exists('wp-load.php')) {
	require_once("wp-load.php");
} else if(file_exists('../../../../wp-load.php')) {
	require_once("../../../../wp-load.php");
} else if(file_exists('../../../../wp-load.php')) {
	require_once("../../../../wp-load.php");
} else {

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
		exit;
	}

}

load_plugin_textdomain('wp-download_monitor', 'wp-content/plugins/download-monitor/', 'download-monitor/');

	include_once('classes/linkValidator.class.php');
		
	global $table_prefix,$wpdb,$user_ID;	
	// set table name	
	$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
	$wp_dlm_db_stats = $table_prefix."DLM_STATS";
	$wp_dlm_db_log = $table_prefix."DLM_LOG";
	
	$id=$_GET['id'];
	
	if ($id) {
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
	}
	if (isset($id) && $go==true) {
		// set table name	
		$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
		
		switch ($downloadtype) {
					case ("Title") :
							// select a download
							$query_select_1 = $wpdb->prepare( "SELECT * FROM $wp_dlm_db WHERE title='%s';", $id );
					break;
					case ("Filename") :
							// select a download
							$query_select_1 = $wpdb->prepare( "SELECT * FROM $wp_dlm_db WHERE filename LIKE '%s' ORDER BY LENGTH(filename) ASC LIMIT 1;", "%".$id );
					break;
					default :
							// select a download
							$query_select_1 = $wpdb->prepare( "SELECT * FROM $wp_dlm_db WHERE id=%s;" , $id );
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
   					} else _e('You must be logged in to download this file.',"wp-download_monitor");
					exit();
				}
								
				// Min-level add-on
				if ($d->members && isset($user_ID)) {
					$minLevel = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wp_dlm_db_meta WHERE download_id = %s AND meta_name='min-level' LIMIT 1" , $id ) );
					if ($minLevel) {
						if ($level < $minLevel) {
							$url = get_option('wp_dlm_member_only');
							if (!empty($url)) {
								$url = 'Location: '.$url;
								header( $url );
								exit();
		   					} else _e('You do not have permission to download this file.',"wp-download_monitor");
							exit();
						}
					}
				}
				
				if ($level!=10) {
					$hits = $d->hits;
					$hits++;
					// update download hits					
					$wpdb->query( $wpdb->prepare( "UPDATE $wp_dlm_db SET hits=%s WHERE id=%s;", $hits, $d->id ) );
					
					// Record date/hits for stats purposes
					$today = date("Y-m-d");
					
					// Check database for date
					$hits = $wpdb->get_var( $wpdb->prepare("SELECT hits FROM $wp_dlm_db_stats WHERE date='%s' AND download_id=%s;", $today, $d->id ) );
					
					if($hits<1) {
						// Insert hits
						$wpdb->query( $wpdb->prepare( "INSERT INTO $wp_dlm_db_stats (download_id,date,hits) VALUES (%s,'%s',%s);", $d->id, $today, 1 ) );
					} else {
						// Update hits
						$wpdb->query( $wpdb->prepare( "UPDATE $wp_dlm_db_stats SET hits=%s WHERE date='%s' AND download_id=%s;", $hits+1, $today, $d->id ) );
					}					
			   }
			   
		   		// Log download details
				$timestamp = current_time('timestamp');					
				$ipAddress = $_SERVER['REMOTE_ADDR'];
				$user = $user_ID;					
				$wpdb->query( $wpdb->prepare( "INSERT INTO $wp_dlm_db_log (download_id, user_id, date, ip_address) VALUES (%s, %s, %s, %s);", $d->id, $user, date("Y-m-d H:i:s" ,$timestamp), $ipAddress ) );
			   
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
			   		$linkValidator = new linkValidator();
			   		while ($checking) { 						
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
        	   exit;
		}
   }
   $url = get_option('wp_dlm_does_not_exist');
   if (!empty($url)) {
   		$url = 'Location: '.$url;
		header( $url );
		exit();
   } else _e('Download does not exist!',"wp-download_monitor");
   exit();
?>