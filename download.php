<?php

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

// Pre 2.6 compatibility (BY Stephen Rider)
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	if ( defined( 'WP_SITEURL' ) ) define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
	else define( 'WP_CONTENT_URL', get_option( 'url' ) . '/wp-content' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

load_plugin_textdomain('wp-download_monitor', WP_PLUGIN_URL.'/download-monitor/languages/', 'download-monitor/languages/');

	include_once('classes/linkValidator.class.php');
		
	global $table_prefix,$wpdb,$user_ID;	
	// set table name	
	$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
	$wp_dlm_db_stats = $table_prefix."DLM_STATS";
	$wp_dlm_db_log = $table_prefix."DLM_LOG";
	
	$id = stripslashes($_GET['id']);
	
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
				$id=urldecode($id);
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
					$url = str_replace('{referrer}',urlencode($_SERVER['REQUEST_URI']),$url);
					if (!empty($url)) {
						$url = 'Location: '.$url;
						header( $url );
						exit();
   					} else {
   						@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
   						wp_die(__('You must be logged in to download this file.',"wp-download_monitor"), __('You must be logged in to download this file.',"wp-download_monitor"));
   					}
					exit();
				}
								
				// Min-level add-on
				if ($d->members && isset($user_ID)) {
					$minLevel = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wp_dlm_db_meta WHERE download_id = %s AND meta_name='min-level' LIMIT 1" , $d->id ) );
					if ($minLevel) {
						if ($level < $minLevel) {
							$url = get_option('wp_dlm_member_only');
							if (!empty($url)) {
								$url = 'Location: '.$url;
								header( $url );
								exit();
		   					} else {
		   						@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
		   						wp_die(__('You do not have permission to download this file.',"wp-download_monitor"),__('You do not have permission to download this file.',"wp-download_monitor"));
		   					}
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
				if (empty($user)) $user = '0';				
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
			   

				// NEW - Member only downloads should be forced to download so real URL is not revealed - NOW OPTIONAL DUE TO SOME SERVER CONFIGS
				
				/*  CHANGED 23 June 09 - Ok, new logic. So we want to only force downloads if its member only and force is not set to 0. Ok so far.
					But as we know, remotly hosted files can be a bitch to force download without corruption SO heres what I want to do:
					
						Member Only and Forced = 0 - DONT FORCE
						Member Only and Forced Not Set or Set to 1 - FORCE
						Normal Download and Forced Not Set or set to 0 =  DONT Force
				*/
				
				$force = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wp_dlm_db_meta WHERE download_id = %s AND meta_name='force' LIMIT 1" , $d->id ) );
				
				if ($d->members) {
					if ($force && $force==0){} else {$force=1;}
				} else {
					if (!$force) $force = 0;
				}
			
				if ($force && ini_get('allow_url_fopen') ) {				

					$filename = basename($thefile);
					$file_extension = strtolower(substr(strrchr($filename,"."),1));					

					//This will set the Content-Type to the appropriate setting for the file
					switch( $file_extension ) {
						case "m4r": 	$ctype="audio/Ringtone"; 		break;
						case "jpg": 	$ctype="image/jpeg"; 			break;
						case "jpeg": 	$ctype="image/jpeg"; 			break;
						case "gif": 	$ctype="image/gif";				break;
						case "png": 	$ctype="image/png"; 			break;
						case "pdf": 	$ctype="application/pdf"; 		break;
						case "zip": 	$ctype="application/zip"; 		break;
						case "gz": 		$ctype="application/x-gzip"; 	break;
						case "tar": 	$ctype="application/x-tar"; 	break;
						case "rar": 	$ctype="application/zip"; 		break;
						case "doc": 	$ctype="application/msword";	break;	
						case "xls": 	$ctype="application/vnd.ms-excel";	break;
						case "mov": 	$ctype="video/quicktime";	break;
						case "mp3": 	$ctype="audio/mpeg";	break;
						case "mpeg": 	$ctype="video/mpeg";	break;
						case "mpg": 	$ctype="video/mpeg";	break;
						case "djvu":	$ctype="image/x.djvu";	break;
						//The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
						case "php":
						case "htm":
						case "htaccess":
						case "sql":
						case "html":
						case "txt": 
							$location= 'Location: '.$thefile;
							header($location);
							exit;
						break;						
						default: 		$ctype="application/octet-stream";
					}						

					if(	ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');
					if(	!ini_get('safe_mode')) set_time_limit(0);
					@ob_end_clean();
					
					// Deal with remote file or local file					
					if (strstr($thefile, get_bloginfo('url'))) {
						// Local File
						$thefile = str_replace(get_bloginfo('url')."/", ABSPATH, $thefile); // Added by David MacMathan				
						if ( file_exists($thefile) && is_readable($thefile) ) {
							header("Pragma: public");
							header("Expires: 0");
							header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
							header("Content-Type: application/force-download");
							header("Content-Type: ".$ctype."");	;
							header("Content-Type: application/download");
							header("Content-Description: File Transfer");						
							header("Content-Disposition: attachment; filename=\"".$filename."\";");
							header("Content-Transfer-Encoding: binary");
							$size = @filesize($thefile);
							if ($size) {						
								header("Content-Length: ".$size);
							}
							@readfile($thefile);
							exit;
						}			
					} else {
						// Remote File						
						header('Cache-Control: private');
						header('Pragma: private');
						header("Expires: 0");
						header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
						header("Content-Type: application/force-download");
						header("Content-Type: ".$ctype."");	;
						header("Content-Type: application/download");
						header("Content-Description: File Transfer");						
						header("Content-Disposition: attachment; filename=\"".$filename."\";");
						header("Content-Transfer-Encoding: binary");
						
						// Get filesize
						$filesize = 0;
						if (function_exists('get_headers')) {
							// php5 method
							$ary_header = get_headers($thefile, 1);    
							$filesize = $ary_header['Content-Length'];
						} else if (function_exists('curl_init')) {
							// Curl Method
							ob_start();
							$ch = curl_init($thefile);
							curl_setopt($ch, CURLOPT_HEADER, 1);
							curl_setopt($ch, CURLOPT_NOBODY, 1);
							if(!empty($user) && !empty($pw)) {
							$headers = array('Authorization: Basic ' . base64_encode("$user:$pw"));
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							}
							$ok = curl_exec($ch);
							curl_close($ch);
							$head = ob_get_contents();
							ob_end_clean();
							$regex = '/Content-Length:\s([0-9].+?)\s/';
							$count = preg_match($regex, $head, $matches);
							if (isset($matches[1])) $filesize = $matches[1];
						}
						if ($file_size > 0) {						
							header("Content-Length: ".$file_size);
						}
						@readfile($thefile);
						exit;
					}				
				}		
				
				// If we have not exited by now then we have not redirected or outputted the download yet	
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
   } else {
   	@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
   	wp_die(__('Download does not exist!',"wp-download_monitor"), __('Download does not exist!',"wp-download_monitor"));
   }
   exit();
?>