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

if (!function_exists('readfile_chunked')) {
	// from php.net comments -chrisputnam at gmail dot com
	function readfile_chunked($filename,$retbytes=true) {
	   $chunksize = 1*(1024*1024); // how many bytes per chunk
	   $buffer = '';
	   $cnt =0;
	   $handle = fopen($filename, 'rb');
	   if ($handle === false) {
	       return false;
	   }
	   while (!feof($handle)) {
	       $buffer = fread($handle, $chunksize);
	       echo $buffer;
	       ob_flush();
	       flush();
	       if ($retbytes) {
	           $cnt += strlen($buffer);
	       }
	   }
	       $status = fclose($handle);
	   if ($retbytes && $status) {
	       return $cnt; // return num. bytes delivered like readfile() does.
	   }
	   return $status;
	
	} 
}

global $wp_db_version;
if ($wp_db_version < 8201) {
	// Pre 2.6 compatibility (BY Stephen Rider)
	if ( ! defined( 'WP_CONTENT_URL' ) ) {
		if ( defined( 'WP_SITEURL' ) ) define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
		else define( 'WP_CONTENT_URL', get_option( 'url' ) . '/wp-content' );
	}
	if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
	if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

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
		   		if (get_option('wp_dlm_log_downloads')=='yes') {
					$timestamp = current_time('timestamp');					
					$ipAddress = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];
					$user = $user_ID;
					if (empty($user)) $user = '0';				
					$wpdb->query( $wpdb->prepare( "INSERT INTO $wp_dlm_db_log (download_id, user_id, date, ip_address) VALUES (%s, %s, %s, %s);", $d->id, $user, date("Y-m-d H:i:s" ,$timestamp), $ipAddress ) );
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
				
				/*  Ok, new logic. So we want to only force downloads if its member only and force is not set to 0. Ok so far.
					But as we know, remotly hosted files can be a bitch to force download without corruption SO heres what I want to do:
					
						Forced = 0 - DONT FORCE
						Member Only and Forced Not Set or Set to 1 - FORCE
						Normal Download and Forced Not Set or set to 0 =  DONT Force
				*/
				
				$force = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wp_dlm_db_meta WHERE download_id = %s AND meta_name='force' LIMIT 1" , $d->id ) );
				
				if (isset($force)) {} else {
					if ($d->members) $force=1; else $force=0;
				}

				if ($force==1 && ini_get('allow_url_fopen') ) {				

					$filename = basename($thefile);
					$file_extension = strtolower(substr(strrchr($filename,"."),1));					

					// This will set the Content-Type to the appropriate setting for the file
					// Extended list provided by Jim Isaacs (jidd.jimisaacs.com)
					switch( $file_extension ) {
						case "m4r": 	$ctype="audio/Ringtone"; 				break;
						case "zip": 	$ctype="application/zip"; 				break;
						case "gz": 		$ctype="application/x-gzip"; 			break;
						case "rar": 	$ctype="application/zip"; 				break;	
						case "xls": 	$ctype="application/vnd.ms-excel";		break;
						case "djvu":	$ctype="image/x.djvu";					break;		
						case "ez":		$ctype="application/andrew-inset";		break; 
						case "hqx":		$ctype="application/mac-binhex40";		break; 
						case "cpt":		$ctype="application/mac-compactpro";	break; 
						case "doc":		$ctype="application/msword";			break; 
						case "oda":		$ctype="application/oda";				break; 
						case "pdf":		$ctype="application/pdf";				break; 
						case "ai":		$ctype="application/postscript";		break; 
						case "eps":		$ctype="application/postscript";		break; 
						case "ps":		$ctype="application/postscript";		break; 
						case "smi":		$ctype="application/smil";				break; 
						case "smil":	$ctype="application/smil";				break; 
						case "wbxml":	$ctype="application/vnd.wap.wbxml";		break; 
						case "wmlc":	$ctype="application/vnd.wap.wmlc";		break; 
						case "wmlsc":	$ctype="application/vnd.wap.wmlscriptc";break; 
						case "bcpio":	$ctype="application/x-bcpio";			break; 
						case "vcd":		$ctype="application/x-cdlink";			break; 
						case "pgn":		$ctype="application/x-chess-pgn";		break; 
						case "cpio":	$ctype="application/x-cpio";			break; 
						case "csh":		$ctype="application/x-csh";				break; 
						case "dcr":		$ctype="application/x-director";		break; 
						case "dir":		$ctype="application/x-director";		break; 
						case "dxr":		$ctype="application/x-director";		break; 
						case "dvi":		$ctype="application/x-dvi";				break; 
						case "spl":		$ctype="application/x-futuresplash";	break; 
						case "gtar":	$ctype="application/x-gtar";			break; 
						case "hdf":		$ctype="application/x-hdf";				break; 
						case "js":		$ctype="application/x-javascript";		break; 
						case "skp":		$ctype="application/x-koan";			break; 
						case "skd":		$ctype="application/x-koan";			break; 
						case "skt":		$ctype="application/x-koan";			break; 
						case "skm":		$ctype="application/x-koan";			break; 
						case "latex":	$ctype="application/x-latex";			break; 
						case "nc":		$ctype="application/x-netcdf";			break; 
						case "cdf":		$ctype="application/x-netcdf";			break; 
						case "sh":		$ctype="application/x-sh";				break; 
						case "shar":	$ctype="application/x-shar";			break; 
						case "swf":		$ctype="application/x-shockwave-flash";	break; 
						case "sit":		$ctype="application/x-stuffit";			break; 
						case "sv4cpio":	$ctype="application/x-sv4cpio";			break; 
						case "sv4crc":	$ctype="application/x-sv4crc";			break; 
						case "tar":		$ctype="application/x-tar";				break; 
						case "tcl":		$ctype="application/x-tcl";				break; 
						case "tex":		$ctype="application/x-tex";				break; 
						case "texinfo":	$ctype="application/x-texinfo";			break; 
						case "texi":	$ctype="application/x-texinfo";			break; 
						case "t":		$ctype="application/x-troff";			break; 
						case "tr":		$ctype="application/x-troff";			break; 
						case "roff":	$ctype="application/x-troff";			break; 
						case "man":		$ctype="application/x-troff-man";		break; 
						case "me":		$ctype="application/x-troff-me";		break; 
						case "ms":		$ctype="application/x-troff-ms";		break; 
						case "ustar":	$ctype="application/x-ustar";			break; 
						case "src":		$ctype="application/x-wais-source";		break;  
						case "au":		$ctype="audio/basic";					break; 
						case "snd":		$ctype="audio/basic";					break; 
						case "mid":		$ctype="audio/midi";					break; 
						case "midi":	$ctype="audio/midi";					break; 
						case "kar":		$ctype="audio/midi";					break; 
						case "mpga":	$ctype="audio/mpeg";					break; 
						case "mp2":		$ctype="audio/mpeg";					break; 
						case "mp3":		$ctype="audio/mpeg";					break; 
						case "aif":		$ctype="audio/x-aiff";					break; 
						case "aiff":	$ctype="audio/x-aiff";					break; 
						case "aifc":	$ctype="audio/x-aiff";					break; 
						case "m3u":		$ctype="audio/x-mpegurl";				break; 
						case "ram":		$ctype="audio/x-pn-realaudio";			break; 
						case "rm":		$ctype="audio/x-pn-realaudio";			break; 
						case "rpm":		$ctype="audio/x-pn-realaudio-plugin";	break; 
						case "ra":		$ctype="audio/x-realaudio";				break; 
						case "wav":		$ctype="audio/x-wav";					break; 
						case "pdb":		$ctype="chemical/x-pdb";				break; 
						case "xyz":		$ctype="chemical/x-xyz";				break; 
						case "bmp":		$ctype="image/bmp";						break; 
						case "gif":		$ctype="image/gif";						break; 
						case "ief":		$ctype="image/ief";						break; 
						case "jpeg":	$ctype="image/jpeg";					break; 
						case "jpg":		$ctype="image/jpeg";					break; 
						case "jpe":		$ctype="image/jpeg";					break; 
						case "png":		$ctype="image/png";						break; 
						case "tiff":	$ctype="image/tiff";					break; 
						case "tif":		$ctype="image/tif";						break;  
						case "djv":		$ctype="image/vnd.djvu";				break; 
						case "wbmp":	$ctype="image/vnd.wap.wbmp";			break; 
						case "ras":		$ctype="image/x-cmu-raster";			break; 
						case "pnm":		$ctype="image/x-portable-anymap";		break; 
						case "pbm":		$ctype="image/x-portable-bitmap";		break; 
						case "pgm":		$ctype="image/x-portable-graymap";		break; 
						case "ppm":		$ctype="image/x-portable-pixmap";		break; 
						case "rgb":		$ctype="image/x-rgb";					break; 
						case "xbm":		$ctype="image/x-xbitmap";				break; 
						case "xpm":		$ctype="image/x-xpixmap";				break; 
						case "xwd":		$ctype="image/x-windowdump";			break; 
						case "igs":		$ctype="model/iges";					break; 
						case "iges":	$ctype="model/iges";					break; 
						case "msh":		$ctype="model/mesh";					break; 
						case "mesh":	$ctype="model/mesh";					break; 
						case "silo":	$ctype="model/mesh";					break; 
						case "wrl":		$ctype="model/vrml";					break; 
						case "vrml":	$ctype="model/vrml";					break;
						case "as":		$ctype="text/x-actionscript";			break; 
						case "css":		$ctype="text/css";						break; 
						case "asc":		$ctype="text/plain";					break; 
						case "txt":		$ctype="text/plain";					break; 
						case "rtx":		$ctype="text/richtext";					break; 
						case "rtf":		$ctype="text/rtf";						break; 
						case "sgml":	$ctype="text/sgml";						break; 
						case "sgm":		$ctype="text/sgml";						break; 
						case "tsv":		$ctype="text/tab-seperated-values";		break; 
						case "wml":		$ctype="text/vnd.wap.wml";				break; 
						case "wmls":	$ctype="text/vnd.wap.wmlscript";		break; 
						case "etx":		$ctype="text/x-setext";					break; 
						case "xml":		$ctype="text/xml";						break; 
						case "xsl":		$ctype="text/xml";						break; 
						case "mpeg":	$ctype="video/mpeg";					break; 
						case "mpg":		$ctype="video/mpeg";					break; 
						case "mpe":		$ctype="video/mpeg";					break; 
						case "qt":		$ctype="video/quicktime";				break; 
						case "mov":		$ctype="video/quicktime";				break; 
						case "mxu":		$ctype="video/vnd.mpegurl";				break; 
						case "avi":		$ctype="video/x-msvideo";				break; 
						case "movie":	$ctype="video/x-sgi-movie";				break; 
						case "ice":		$ctype="x-conference-xcooltalk" ;		break;
						//The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files) - if you want to serve these types of files just zip then or give them another extension! This is mainly to protect users who don't know what they are doing :)
						case "php":
						case "htm":
						case "htaccess":
						case "sql":
						case "html":
							$location= 'Location: '.$thefile;
							header($location);
							exit;
						break;						
						default: 		$ctype="application/octet-stream";
					}						

					if(	ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');
					if(	!ini_get('safe_mode')) set_time_limit(0);
					@ob_end_clean();
					
					// START jidd.jimisaacs.com
					$urlparsed = parse_url($thefile);
					$isURI = array_key_exists('scheme', $urlparsed);
					$localURI = (bool) strstr($thefile, get_bloginfo('url'));
					
					// Deal with remote file or local file					
					//if (strstr($thefile, get_bloginfo('url'))) {
					if( $isURI && $localURI || !$isURI && !$localURI ) {
						// Local File URI
						if( $localURI ) {
							// the URI is local, replace the WordPress url OR blog url with WordPress's absolute path.
							$patterns = array( '|^'. get_bloginfo('wpurl') . '/' . '|', '|^'. get_bloginfo('url') . '/' . '|');
							$path = preg_replace( $patterns, '', $thefile );
							// this is joining the ABSPATH constant, changing any slashes to local filesystem slashes, and then finally getting the real path.
							$thefile = str_replace( '/', DIRECTORY_SEPARATOR, path_join( ABSPATH, $path ) );
						// Local File System path
						} else if( !path_is_absolute( $thefile ) ) { 
							// the path is relative, append it to the WordPress absolute path.
							$thefile = path_join( ABSPATH, $thefile );
						}
						// If the path wasn't a URI and not absolute, then it made it all the way to here without manipulation, so now we do this...
						// By the way, realpath() returns NOTHING if is does not exist.
						$thefile = realpath( $thefile );
						// now do a long condition check, it should not be emtpy, a directory, and should be readable.
						$willDownload = empty($thefile) ? false : !is_file($thefile) ? false : is_readable($thefile);
						//if ( file_exists($thefile) && is_readable($thefile) ) {
						if ( $willDownload ) {
						// END jidd.jimisaacs.com
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
							readfile_chunked($thefile);
							exit;
						}			
					// START jidd.jimisaacs.com
					// this is only for remote URI's
					} else if ( $isURI ) {
					// END jidd.jimisaacs.com
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
						readfile_chunked($thefile);
						exit;
					}	
					
					// If we have not exited by now, the only thing left to do is die.
					// We cannot download something that is a local file system path on another system, and that's the only thing left it could be!
					wp_die(__('Download path is invalid!',"wp-download_monitor"), __('Download path is invalid!',"wp-download_monitor"));
							
				}
				
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