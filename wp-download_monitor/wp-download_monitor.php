<?php
/* 
Plugin Name: Wordpress Download Monitor
Plugin URI: http://blue-anvil.com
Version: v2.0.3 B20080315
Author: <a href="http://www.blue-anvil.com/">Mike Jolley</a>
Description: Manage downloads on your site, view and show hits, and output in posts. Downloads page found at "Manage>Downloads".
*/

/*  Copyright 2006  Michael Jolley  (email : jolley.small.at.googlemail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


$wp_dlm_root = get_bloginfo('wpurl')."/wp-content/plugins/wp-download_monitor/"; 	//FIXED: 2 - get_settings depreciated
$allowed_extentions = array(".zip",".pdf",".mp3",".rar"); 							//FIXED: 1.6 - Added to store extentions
$max_upload_size = 10485760; //10mb													

$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";											//FIXED: 2 - Defining db table

load_plugin_textdomain('wp-download_monitor', 'wp-content/plugins/wp-download_monitor/');
																					
################################################################################
// Set up menus within the wordpress admin sections
################################################################################
function wp_dlm_menu() { 	
// Add submenus to the manage menu:
	 add_management_page(__('Downloads','wp-download_monitor'), __('Downloads','wp-download_monitor'), 6,'Downloads', 'wp_dlm_admin');
}
add_action('admin_menu', 'wp_dlm_menu');


################################################################################
// ADMIN HEADER
################################################################################
function wp_dlm_head() {
	global $wp_db_version;
	// Provide css based on wordpress version. Version 2.3.3 and below:
	if ($wp_db_version <= 6124) {
		echo '<link rel="stylesheet" type="text/css" href="../wp-content/plugins/wp-download_monitor/css/wp-download_monitor.css" />';
		// Include JQUERY where needed
		if( strpos($_SERVER['REQUEST_URI'], 'post.php')
		|| strstr($_SERVER['PHP_SELF'], 'page-new.php')
		|| $_GET['page']=="Downloads"
		|| strstr($_SERVER['PHP_SELF'], 'post-new.php') )
		{
			echo '<script type="text/javascript" src="../wp-includes/js/jquery/jquery.js"></script>';
		}
	} else {
		// 2.5 + with new interface
		echo '<link rel="stylesheet" type="text/css" href="../wp-content/plugins/wp-download_monitor/css/wp-download_monitor25.css" />';
	}
}
add_action('admin_head', 'wp_dlm_head');


################################################################################
// Set up database
################################################################################
function wp_dlm_init() {

	add_option('wp_dlm_url', '', 'URL for download', 'no');	
	add_option('wp_dlm_url', 'ID', 'wp_dlm_type', 'no');
	
 	global $wp_dlm_db,$wpdb;
	
    $wp_dlm_db_exists = false; 
	// Check table exists
	$res = mysql_query("show table status like '$wp_dlm_db'");
	if ($res) $wp_dlm_db_exists = mysql_num_rows($res) == 1;
	
    if ( !$wp_dlm_db_exists )
    { 
		$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db." (				
				`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT, 
				`title`   	VARCHAR (200) NOT NULL ,
				`filename`  LONGTEXT  NOT NULL ,
				`dlversion` VARCHAR (200) NOT NULL ,
				`postDate`  DATETIME  NOT NULL ,
				`hits`   	INT UNSIGNED NOT NULL ,
				`user`   	VARCHAR (200) NOT NULL ,
				PRIMARY KEY ( `id` )
				)";
        $result = $wpdb->query($sql);
		if (!empty($result)) { 
				echo '<div id="message" class="updated fade"><p><strong>';
				_e('Tables created successfully','wp-download_monitor');
				echo '</strong></p></div>'."\n";
		}
      }   
	$q=$wpdb->get_results("select * from $wp_dlm_db;");
	if ( empty( $q ) ) {
		$wpdb->query("TRUNCATE table $wp_dlm_db");
	}
  return;
}

################################################################################
// MAGIC QUOTES - checks if magic quotes enabled, disables the add_slashes on
// inputs, so ensure add_slashes before interacting with the database
################################################################################
function wp_dlm_magic() { 
	if (get_magic_quotes_gpc()){ 
	 $_GET = array_map('stripslashes', $_GET); 
	 $_POST = array_map('stripslashes', $_POST); 
	}
	return;
}

################################################################################
// INSERT BUTTON ON POST SCREEN
################################################################################
function wp_dlm_ins_button() {
	//set globals
	global $table_prefix,$wpdb,$wp_dlm_db;
  	
  	if( strpos($_SERVER['REQUEST_URI'], 'post.php')
	|| strstr($_SERVER['PHP_SELF'], 'page-new.php')
	|| strstr($_SERVER['PHP_SELF'], 'post-new.php') )
	{
		$wp_dlm_db_exists = false; 
		// Check table exists
		$res = mysql_query("show table status like '$wp_dlm_db'");
		if ($res) $wp_dlm_db_exists = mysql_num_rows($res) == 1;
	
		if ($wp_dlm_db_exists==true) {
      	
			// select all downloads
			$query_select = sprintf("SELECT * FROM %s ORDER BY id;",
			$wpdb->escape($wp_dlm_db));
      	
      		$downloads = $wpdb->get_results($query_select);
      	
      		if (!empty($downloads)) {
      				
				foreach( $downloads as $d )
				{
					$js .= '<option value=\"'.$d->id.'\">'.$d->id.' - '.$d->title.'</option>';
				}
          	
				?>
					<script type="text/javascript">
                        <!--
						jQuery(function() {
							jQuery("#ed_toolbar,td.mceToolbar.first").append('<select style=\"width:120px;margin:3px 2px 2px;\" class=\"ed_button\" id=\"downloadMon\" size=\"1\" onChange=\"return wpdlmins(this);\"><option selected="selected" value=\"\"><?php _e('Downloads','wp-download_monitor'); ?></option><?php echo $js;?></select>');
						});
						function wpdlmins(ele) {
							try{
							if( ele != undefined && ele.value != '')
								edInsertContent(edCanvas, '[download#'+ ele.value +']');
							}catch (excpt) { alert(excpt); }
							ele.selectedIndex = 0; // reset menu
							return false;
						}
                        //-->
                    </script>
                <?php
      		}
      	}
	}
}	
add_filter('admin_head', 'wp_dlm_ins_button');

################################################################################
// INSERT LINK INTO POSTS
################################################################################
function wp_dlm_ins($data) {
	//echo "-Test 1-";
	if (substr_count($data,"[download#")) {
		//echo "-Test 2-";
      	global $table_prefix,$wpdb,$wp_dlm_root,$allowed_extentions,$max_upload_size,$wp_dlm_db;
		
		$wp_dlm_db_exists = false;
          	
		// Check table exists
		$res = mysql_query("show table status like '$wp_dlm_db'");
		if ($res) $wp_dlm_db_exists = mysql_num_rows($res) == 1;

		if ($wp_dlm_db_exists==true) {
			//echo "-Table exists-";
			$url = get_option('wp_dlm_url');
			$downloadurl = get_bloginfo('wpurl').'/'.$url;	
			if (empty($url)) $downloadurl = $wp_dlm_root.'download.php?id=';
			$downloadtype = get_option('wp_dlm_type');

			// select all downloads
            $query_select = sprintf("SELECT * FROM %s ORDER BY id;",
            $wpdb->escape($wp_dlm_db));
                	
            $downloads = $wpdb->get_results($query_select);	
                
				if (!empty($downloads)) {
                	//echo "-Downloads found-";
					$patts = array();
					$subs = array();	
					
					foreach($downloads as $d) {
					
						switch ($downloadtype) {
								case ("Title") :
										$downloadlink = $d->title;
								break;
								case ("Filename") :
										$downloadlink = $d->filename;
										$links = explode("/",$downloadlink);
										$downloadlink = end($links);
								break;
								default :
										$downloadlink = $d->id;
								break;
						}
						
						for ( $i = 1; $i <= 7; $i += 1) {
						
							switch ($i) {
								case (1) :
									// Regular download link
									//echo "-Link output-";
									if (!empty($d->dlversion)) 				
										$link = '<a href="'.$downloadurl.$downloadlink.'" title="'.__("Version","wp-download_monitor").' '.$d->dlversion.' '.__("downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a>';
									else $link = '<a href="'.$downloadurl.$downloadlink.'" title="'.__("Downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a>';									
									$patts[] = "[download#" . $d->id . "]";
									$subs[] = $link;
								break;
								case (2) :
									// No hit counter
									if (!empty($d->dlversion)) 
									$link = '<a href="'.$downloadurl.$downloadlink.'" title="'.__("Version","wp-download_monitor").' '.$d->dlversion.' '.__("downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.'</a>';
									else $link = '<a href="'.$downloadurl.$downloadlink.'" title="'.__("Downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.'</a>';
									$patts[] = "[download#" . $d->id . "#nohits]";
									$subs[] = $link;
								break;
								case (3) :
									// URL only
									$link = $downloadurl.$downloadlink;	
									$patts[] = "[download#" . $d->id . "#url]";
									$subs[] = $link;		
								break;
								case (4) :
									// Hits only
									$link = $d->hits;
									$patts[] = "[download#" . $d->id . "#hits]";
									$subs[] = $link;		
								break;
								case (5) :	
									// Image link
									if (!empty($d->dlversion)) 				
										$link = '<a class="dlimg" href="'.$downloadurl.$downloadlink.'" title="'.__("Download","wp-download_monitor").' '.$d->title.' '.__("Version","wp-download_monitor").' '.$d->dlversion.'"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/wp-download_monitor/img/download.gif" alt="'.__("Download","wp-download_monitor").' '.$d->title.' '.__("Version","wp-download_monitor").' '.$d->dlversion.'" /></a>
									<p class="dlstat">'.__("Downloaded a total of","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'</p>';
									else $link = '<a class="dlimg" href="'.$downloadurl.$downloadlink.'" title="'.__("Download","wp-download_monitor").' '.$d->title.'"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/wp-download_monitor/img/download.gif" alt="'.__("Download","wp-download_monitor").' '.$d->title.'" /></a>
									<p class="dlstat">'.__("Downloaded a total of","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'</p>';
									$patts[] = "[download#" . $d->id . "#image]";
									$subs[] = $link;
								break;
								case (6) :
									// Regular download link WITH filesize
									//echo "-Link output-";
									if (!empty($d->dlversion)) 				
										$link = '<a href="'.$downloadurl.$downloadlink.'" title="'.__("Version","wp-download_monitor").' '.$d->dlversion.' '.__("downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.$d->hits.') - '.wp_dlm_get_size($d->filename).'</a>';
									else $link = '<a href="'.$downloadurl.$downloadlink.'" title="'.__("Downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.$d->hits.') - '.wp_dlm_get_size($d->filename).'</a>';			
									$patts[] = "[download#" . $d->id . "#size]";
									$subs[] = $link;
								break;
								case (7) :
									// No hit counter + filesize
									if (!empty($d->dlversion)) 
									$link = '<a href="'.$downloadurl.$downloadlink.'" title="'.__("Version","wp-download_monitor").' '.$d->dlversion.' '.__("downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.wp_dlm_get_size($d->filename).')</a>';
									else $link = '<a href="'.$downloadurl.$downloadlink.'" title="'.__("Downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.wp_dlm_get_size($d->filename).')</a>';
									$patts[] = "[download#" . $d->id . "#size#nohits]";
									$subs[] = $link;
								break;
							}
						}
					} return str_replace($patts, $subs, $data);
				}else return $data;
		} else return $data;
	} else return $data;
} 
add_filter('the_content', 'wp_dlm_ins',1,1); 
add_filter('the_excerpt', 'wp_dlm_ins',1,1);

// Formats file size
function wp_dlm_get_size($path) {
	$path = str_replace(get_bloginfo('wpurl'),"./",$path);
	if (file_exists($path)) {
		$size = filesize($path);
		if ($size) {
		$bytes = array('bytes','KB','MB','GB','TB');
		  foreach($bytes as $val) {
		   if($size > 1024){
			$size = $size / 1024;
		   }else{
			break;
		   }
		  }
		  return round($size, 2)." ".$val;
		}
	}
}
	

################################################################################
// ADMIN PAGE
################################################################################
function wp_dlm_admin()
{
	//set globals
	global $table_prefix,$wpdb,$wp_dlm_root,$allowed_extentions,$max_upload_size,$wp_dlm_db;
	// create database		 
	wp_dlm_init();

	// turn off magic quotes
	wp_dlm_magic();
	
	// DEFINE QUERIES
	
	// select all downloads
	if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;
		
	// select a downloads
	$query_select_1 = sprintf("SELECT * FROM %s WHERE id=%s;",
		$wpdb->escape( $wp_dlm_db ),
		$wpdb->escape( $_GET['id'] ));	
	
	$action = $_GET['action'];
	if (!empty($action)) {
		switch ($action) {
				case "add" :							
							$method = $_REQUEST['method'];
							if (!empty($method))
							{
								//SAVE
								if ( $_POST['sub'] ) {
									
									//get postdata
									$title = htmlspecialchars(trim($_POST['title']));
									$filename = htmlspecialchars(trim($_POST['filename']));									
									$dlversion = htmlspecialchars(trim($_POST['dlversion']));
									$dlhits = htmlspecialchars(trim($_POST['dlhits']));
									$postDate = $_POST['postDate'];
									$user = $_POST['user'];
									
									//validate fields
									if (empty( $_POST['title'] )) $errors.=__('<div class="error">Required field: <strong>Title</strong> omitted</div>',"wp-download_monitor");
									if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
									if (!is_numeric($_POST['dlhits'] )) $errors.=__('<div class="error">Invalid <strong>hits</strong> entered</div>',"wp-download_monitor");
									
									if ($method=="upload") {
										//attempt to upload file
										if ( empty($errors ) ) {

													global $max_upload_size;
													
													$max_size = $max_upload_size; // the max. size for uploading
														
													$my_upload = new wp_dlm_file_upload;

													$my_upload->upload_dir = "../wp-content/plugins/wp-download_monitor/user_uploads/"; // "files" is the folder for the uploaded files (you have to create this folder)
													
													$my_upload->extensions = $allowed_extentions; // specify the allowed extensions here
													$my_upload->max_length_filename = 100; // change this value to fit your field length in your database (standard 100)
													$my_upload->rename_file = false;

													//upload it
													$my_upload->the_temp_file = $_FILES['upload']['tmp_name'];
													$my_upload->the_file = $_FILES['upload']['name'];
													$my_upload->http_error = $_FILES['upload']['error'];
													$my_upload->replace = (isset($_POST['replace'])) ? $_POST['replace'] : "n";
													$my_upload->do_filename_check = "n";
													
													if ($my_upload->upload()) {
														$full_path = $my_upload->upload_dir.$my_upload->file_copy;
														$info = $my_upload->show_error_string();
													} 
													else $errors = '<div class="error">'.$my_upload->show_error_string().'</div>';
													
													$filename = $wp_dlm_root."user_uploads/".$my_upload->file_copy;
													
										}										
									} 
									elseif ($method=="url") {
										if ( empty( $_POST['filename']) ) $errors.=__('<div class="error">No file selected</div>',"wp-download_monitor");
									} else $errors.=__('<div class="error">Error</div>',"wp-download_monitor");
									
									//save to db
									if ( empty($errors ) ) {	

										if ($my_upload->replace=="y") {
												$query_del = sprintf("DELETE FROM %s WHERE filename='%s';",
												$wpdb->escape( $wp_dlm_db ),
												$wpdb->escape( $filename ));
												
												$wpdb->query($query_del);
										} 
										
										$query_add = sprintf("INSERT INTO %s (title, filename, dlversion, postDate, hits, user) VALUES ('%s','%s','%s','%s','%s','%s')",
										$wpdb->escape( $wp_dlm_db ),
										$wpdb->escape( $_POST['title'] ),
										$wpdb->escape( $filename ),
										mysql_real_escape_string( $_POST['dlversion'] ),
										$wpdb->escape( $_POST['postDate'] ),
										mysql_real_escape_string( $_POST['dlhits'] ),
										$wpdb->escape( $_POST['user'] ));										
											
										$result = $wpdb->query($query_add);
										if ($result) {
											if (empty($info)) echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully","wp-download_monitor").'</strong></p></div>';
											else echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully","wp-download_monitor").' - '.$info.'</strong></p></div>';											
											$_POST['add_n']="";
											$_POST['add_e']="";
											$show=true;
										}
										else _e('<div class="error">Error saving to database</div>',"wp-download_monitor");										
										break;
									}
									else echo $errors;									
								} 	
							} 
							
							if (!empty( $_POST['add_n'] ))
							{
									//ADD DOWNLOAD FORM	
									global $max_upload_size;
									$max_size = $max_upload_size; // the max. size for uploading

								?>
								<div class="wrap">
								<h2><?php _e('Add Download','wp-download_monitor'); ?></h2>
								<form enctype="multipart/form-data" action="?page=Downloads&amp;action=add&amp;method=upload" method="post" id="wp_dlm_add" name="add_download"> 
                                    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_size; ?>" />
                                    <table class="optiontable niceblue"> 
                                        <tr valign="top">
                                            <th scope="row"><strong><?php _e('Title (required)',"wp-download_monitor"); ?>: </strong></th> 
                                            <td>
                                                <input type="text" style="width:320px;" class="cleardefault" value="<?php echo $title; ?>" name="title" id="dltitle" maxlength="200" />												
                                            </td> 
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><strong><?php _e('Version',"wp-download_monitor"); ?>: </strong></th> 
                                            <td>
                                                <input type="text" style="width:320px;" class="cleardefault" value="<?php echo $dlversion; ?>" name="dlversion" id="dlversion" />
                                            </td> 
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><strong><?php _e('Starting hits',"wp-download_monitor");?>: </strong></th> 
                                            <td>
                                                <input type="text" style="width:100px;" class="cleardefault" value="<?php if ($dlhits>0) echo $dlhits; else echo 0; ?>" name="dlhits" id="dlhits" maxlength="50" />
                                            </td> 
                                        </tr>
										<tr valign="top">
												<th scope="row"><strong><?php _e('Select a file...',"wp-download_monitor"); ?></strong></th> 
												<td>
													<input type="file" name="upload" style="width:320px;" /><br />
													<p><?php _e('Max. filesize = ',"wp-download_monitor"); ?><?php echo $max_size; ?> <?php _e('bytes',"wp-download_monitor"); ?>.</p>
												</td>												
                                        </tr>
                                        <tr valign="top">												
                                            <th scope="row"><strong><?php _e('Replace File?',"wp-download_monitor"); ?></strong></th> 
                                            <td><input type="checkbox" name="replace" value="y" /><br />
                                            <?php _e('<p>Replacing the file will <strong>delete all current stats</strong> 
                                            for the currently uploaded file. If you wish to keep existing stats, go to the
                                            files Edit page instead and re-upload there.</p>',"wp-download_monitor"); ?>
                                            </td>
                                        </tr>  
                                    </table>

                                    <p class="submit"><input type="submit" class="btn" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Upload &amp; save',"wp-download_monitor"); ?>" /></p>
									<input type="hidden" name="postDate" value="<?php echo date("Y-m-d H:i:s") ;?>" />
									<?php 
										global $userdata;
										get_currentuserinfo();										
										echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
									?>	
									<input type="hidden" name="add_n" value="add_n" />
									<input type="hidden" name="sub" value="sub" />									
								</form>
                                </div>
								<?php	
							}
							elseif (!empty( $_POST['add_e'] )) {
								//ADD DOWNLOAD FORM	
								?>
								<div class="wrap">
								<h2><?php _e('Add Download',"wp-download_monitor"); ?></h2>
								<form action="?page=Downloads&amp;action=add&amp;method=url" method="post" id="wp_dlm_add" name="add_download"> 

                                    <table class="optiontable niceblue">
                                        <tr valign="top">
                                            <th scope="row"><strong><?php _e('Title (required)',"wp-download_monitor"); ?>: </strong></th> 
                                            <td>
                                                <input type="text" style="width:320px;" class="cleardefault" value="<?php echo $title; ?>" name="title" id="dlmtitle" maxlength="200" />												
                                            </td> 
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><strong><?php _e('Version',"wp-download_monitor"); ?>: </strong></th> 
                                            <td>
                                                <input type="text" style="width:320px;" class="cleardefault" value="<?php echo $dlversion; ?>" name="dlversion" id="dlversion" maxlength="200" />
                                            </td> 
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><strong><?php _e('Starting hits',"wp-download_monitor"); ?>: </strong></th> 
                                            <td>
                                                <input type="text" style="width:100px;" class="cleardefault" value="<?php if ($dlhits>0) echo $dlhits; else echo 0; ?>" name="dlhits" id="dlhits" maxlength="50" />
                                            </td> 
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><strong><?php _e('Url',"wp-download_monitor"); ?>:</strong></th> 
                                            <td>
                                                <input type="text" style="width:320px;" class="cleardefault" value="<?php echo $filename; ?>" name="filename" id="filename" />
                                            </td> 
                                        </tr>
                                    </table>
									<p class="submit"><input type="submit" class="btn" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Save',"wp-download_monitor"); ?>" /></p>
									<input type="hidden" name="postDate" value="<?php echo date("Y-m-d H:i:s") ;?>" />
									<?php 
										global $userdata;
										get_currentuserinfo();										
										echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
									?>									
									<input type="hidden" name="add_e" value="add_e" />	
									<input type="hidden" name="sub" value="sub" />										
								</form>
                                </div>
								<?php	
							}
							else _e('<p>Invalid Add method, <a href="?page=Downloads">go back</a>.</p>',"wp-download_monitor");
				break;
				case "delete" :
					$d = $wpdb->get_row($query_select_1);
					?>
						<div class="wrap">
							<h2><?php _e('Sure?',"wp-download_monitor"); ?></h2>
							<p><?php _e('Are you sure you want to delete',"wp-download_monitor"); ?> "<?php echo $d->title; ?>"<?php _e('? (If originally uploaded by this plugin, this will also remove the file from the server)',"wp-download_monitor"); ?> <a href="<?php echo get_bloginfo('wpurl'); ?>/wp-admin/edit.php?page=Downloads&amp;action=confirmed&amp;id=<?php echo $_GET['id']; ?>&amp;sort=<?php echo $_GET['sort']; ?>&amp;p=<?php echo $_GET['p']; ?>"><?php _e('[yes]',"wp-download_monitor"); ?></a> <a href="<?php echo get_bloginfo('wpurl'); ?>/wp-admin/edit.php?page=Downloads&amp;action=cancelled&amp;sort=<?php echo $_GET['sort']; ?>&amp;p=<?php echo $_GET['p']; ?>"><?php _e('[no]',"wp-download_monitor"); ?></a>
						</div>
					<?php
				break;
				case "edit" :	
					if ( $_POST['subedit'] )
					{
						//save and validate
						if (empty( $_POST['title'] )) $errors.='<div class="error">'.__('Required field: <strong>Title</strong> omitted',"wp-download_monitor").'</div>';
						if (empty( $_POST['dlfilename'] )) $errors.='<div class="error">'.__('Required field: <strong>File URL</strong> omitted',"wp-download_monitor").'</div>';						
						if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
						if (!is_numeric($_POST['dlhits'] )) $errors.='<div class="error">'.__('Invalid <strong>hits</strong> entered',"wp-download_monitor").'</div>';
							
						if (empty($errors)) {
								if (!empty($_FILES['upload']['tmp_name'])) {
										//user is replacing the file	
										global $max_upload_size;
										
										$max_size = $max_upload_size; // the max. size for uploading
											
										$my_upload = new wp_dlm_file_upload;

										$my_upload->upload_dir = "../wp-content/plugins/wp-download_monitor/user_uploads/"; // "files" is the folder for the uploaded files (you have to create this folder)
									
										$my_upload->extensions = $allowed_extentions; // specify the allowed extensions here
										$my_upload->max_length_filename = 100; // change this value to fit your field length in your database (standard 100)
										$my_upload->rename_file = false;

										//upload it
										$my_upload->the_temp_file = $_FILES['upload']['tmp_name'];
										$my_upload->the_file = $_FILES['upload']['name'];
										$my_upload->http_error = $_FILES['upload']['error'];
										$my_upload->replace = "y";
										$my_upload->do_filename_check = "n";
										if ($my_upload->upload()) {
											$full_path = $my_upload->upload_dir.$my_upload->file_copy;
											$info = $my_upload->show_error_string();
										} 
										else $errors.= '<div class="error">'.$my_upload->show_error_string().'</div>';
										
										$filename = $wp_dlm_root."user_uploads/".$my_upload->file_copy;
										

										// update download & file
										$query_update_file = sprintf("UPDATE %s SET title='%s', dlversion='%s', hits='%s', filename='%s', postDate='%s', user='%s' WHERE id=%s;",
											$wpdb->escape( $wp_dlm_db ),
											$wpdb->escape( $_POST['title'] ),
											mysql_real_escape_string( $_POST['dlversion'] ),
											mysql_real_escape_string( $_POST['dlhits'] ),
											$wpdb->escape( $filename ),
											$wpdb->escape( $_POST['postDate'] ),
											$wpdb->escape( $_POST['user'] ),
											$wpdb->escape( $_GET['id'] ));
		
										//replacing file
										$d = $wpdb->get_row($query_update_file);
										$show=true;
										echo '<div id="message" class="updated fade"><p><strong>'.__('Download edited Successfully',"wp-download_monitor").' - '.$info.'</strong></p></div>';
								} else {
										//not replacing file
										$query_update = sprintf("UPDATE %s SET title='%s', dlversion='%s', hits='%s', filename='%s' WHERE id=%s;",
											$wpdb->escape( $wp_dlm_db ),
											$wpdb->escape( $_POST['title'] ),
											mysql_real_escape_string( $_POST['dlversion'] ),
											mysql_real_escape_string( $_POST['dlhits'] ),
											$wpdb->escape( $_POST['dlfilename'] ),
											$wpdb->escape( $_GET['id'] ));
										$d = $wpdb->get_row($query_update);
										$show=true;
										echo '<div id="message" class="updated fade"><p><strong>'.__('Download edited Successfully',"wp-download_monitor").'</strong></p></div>';
								}
						} 
						if (!empty($errors)) {
							echo $errors;
							$title = $_POST['title'];
							$dlversion = $_POST['dlversion'];
							$dlhits = $_POST['dlhits'];
							$dlfilename =$_POST['dlfilename'];
						}
					}
					else 
					{
						//load values
						$d = $wpdb->get_row($query_select_1);
						$title = $d->title;
						$dlversion = $d->dlversion;
						$dlhits = $d->hits;
						$dlfilename = $d->filename;
						if (empty( $dlhits )) $dlhits = 0;
					}	

					if ($show==false) {
					?>
								<div class="wrap">
								<h2><?php _e('Edit Download Information',"wp-download_monitor"); ?></h2>
								<form enctype="multipart/form-data" action="?page=Downloads&amp;action=edit&amp;id=<?php echo $_GET['id']; ?>" method="post" id="wp_dlm_add" name="edit_download"> 

										<table class="optiontable niceblue">                     
											<tr valign="top">
												<th scope="row"><strong><?php _e('Title (required)',"wp-download_monitor"); ?>: </strong></th> 
												<td>
													<input type="text" style="width:320px;" class="cleardefault" value="<?php echo $title; ?>" name="title" id="dlmtitle" maxlength="200" />												
												</td> 
											</tr>
											<tr valign="top">
												<th scope="row"><strong><?php _e('Version',"wp-download_monitor"); ?>: </strong></th> 
												<td>
													<input type="text" style="width:100px;" class="cleardefault" value="<?php echo $dlversion; ?>" name="dlversion" id="dlversion" maxlength="200" />
												</td> 
											</tr>
											<tr valign="top">
												<th scope="row"><strong><?php _e('Change hit count',"wp-download_monitor"); ?>: </strong></th> 
												<td>
													<input type="text" style="width:100px;" class="cleardefault" value="<?php echo $dlhits; ?>" name="dlhits" id="dlhits" maxlength="50" />
												</td> 
											</tr>
											<tr valign="top">
												<th scope="row"><strong><?php _e('File URL (required)',"wp-download_monitor"); ?>: </strong></th> 
												<td>
													<input type="text" style="width:320px;" class="cleardefault" value="<?php echo $dlfilename; ?>" name="dlfilename" id="dlfilename" />
													<p><?php _e('Note: changes to the file url will only work if not uploading a new file below.',"wp-download_monitor"); ?></p>
												</td> 
											</tr>
										</table>
																
										<h3><?php _e('Upload a new file',"wp-download_monitor"); ?></h3>
										<input type="hidden" name="MAX_FILE_SIZE" value="<?php global $max_upload_size; echo $max_upload_size; ?>" />										
										
										<?php _e('<p>Here you can upload/re-upload the file from your computer. This will Overwrite any existing files 
										with the same name, but will keep stats in-tact.</p>',"wp-download_monitor"); ?>
										
										<table class="optiontable niceblue">                     
											<tr valign="top">
												<th scope="row"><strong><?php _e('Select a file...',"wp-download_monitor"); ?></strong></th> 
												<td>
													<input type="file" name="upload" style="width:320px;" /><br />
                                                    <p><?php _e('Max. filesize = ',"wp-download_monitor"); ?><?php echo $max_upload_size; ?> <?php _e('bytes',"wp-download_monitor"); ?>.</p>
												</td>												
											</tr>
										</table>
									<input type="hidden" name="sort" value="<?php echo $_GET['sort']; ?>" />
									<input type="hidden" name="p" value="<?php echo $_GET['p']; ?>" />
									<input type="hidden" name="subedit" value="subedit" />
									<input type="hidden" name="postDate" value="<?php echo date("Y-m-d H:i:s") ;?>" />
									<?php 
										global $userdata;
										get_currentuserinfo();										
										echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
									?>	
									<p class="submit">
											<input type="submit" class="btn" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" />
									</p>
								</form>
								</div>
					<?php	
					}
				
				break;
				case "confirmed" :
					//load values
					$d = $wpdb->get_row($query_select_1);
					$file = $d->filename;
					if ( strstr ( $d->filename, "/wp-content/plugins/wp-download_monitor/user_uploads/" ) ) {
						
						$path = $wp_dlm_root."user_uploads/";
						$file = str_replace( $path , "" , $d->filename);
						if(is_file('../wp-content/plugins/wp-download_monitor/user_uploads/'.$file)){
								chmod('../wp-content/plugins/wp-download_monitor/user_uploads/'.$file, 0777);  
								unlink('../wp-content/plugins/wp-download_monitor/user_uploads/'.$file);
						 }					    
					}
					$query_delete = sprintf("DELETE FROM %s WHERE id=%s;",
						$wpdb->escape( $wp_dlm_db ),
						$wpdb->escape( $_GET['id'] ));
					$wpdb->query($query_delete);
					echo '<div id="message" class="updated fade"><p><strong>'.__('Download deleted Successfully',"wp-download_monitor").'</strong></p></div>';
					wp_dlm_init();
					$show=true;
				break;
				case "cancelled" :
					$show=true;
				break;
				case "saveurl" :
				  $url = $_POST['url'];						 
					update_option('wp_dlm_url', trim($url));
					update_option('wp_dlm_type', $_POST['type']);
					if (!empty($url)) {
						echo '<div id="message"class="updated fade">';	
						_e('<p>Download URL updated - You need to <strong>re-save your permalinks settings</strong> (Options -> Permalinks) for 
						the changes to occur in your blog.</p>
						<p>If your .htaccess file cannot be written to by WordPress, add the following to your 
					.htaccess file above the "# BEGIN WordPress" line:</p>
						<p>Options +FollowSymLinks<br/>
						RewriteEngine on<br/>
						RewriteRule ^download/(.*) wp-content/plugins/wp-download_monitor/download.php?id=$1 [L]</p>
						<p>replacing "download/" with your custom url.</p>',"wp-download_monitor");			
						echo '</div>';
					} else {
					echo '<div id="message"class="updated fade">';				
						_e('<p>Download URL updated - You need to <strong>re-save your permalinks settings</strong> (Options -> Permalinks) for 
						the changes to occur in your blog.</p>
						<p>If your .htaccess file cannot be written to by WordPress, remove the following from your 
					.htaccess file if it exists above the "# BEGIN WordPress" line:</p>
						<p>Options +FollowSymLinks<br/>
						RewriteEngine on<br/>
						RewriteRule ^download/(.*) wp-content/plugins/wp-download_monitor/download.php?id=$1 [L]</p>
						<p>replacing "download/" with your previous custom url.</p>',"wp-download_monitor");
					echo '</div>';
					}
					$show=true;
				break;
		}
	}
	//show downloads page
	if ( ($show==true) || ( empty($action) ) )
	{
	
	$downloadurl = get_option('wp_dlm_url');
	$downloadtype = get_option('wp_dlm_type');
	
	?>
	
    <div class="wrap alternate">
        <h2><?php _e('Downloads',"wp-download_monitor"); ?></h2>
        <br style="clear: both;"/>
        <form action="?page=Downloads&amp;action=add" method="post" id="wp_dlm_add" name="add_download"> 		
            <div class="tablenav">
                <div style="float: left;">
                    <input type="submit" class="button" name="add_n" value="<?php _e('Add New Download',"wp-download_monitor"); ?>" />
                    <input type="submit" class="button" name="add_e" value="<?php _e('Add Existing Download',"wp-download_monitor"); ?>" />
                </div> 
                <br style="clear: both;"/>
            </div>
		</form>
        <br style="clear: both;"/>
        <table class="widefat"> 
			<thead>
				<tr>
				<th scope="col" style="text-align:center"><a href="?page=Downloads&amp;sort=id"><?php _e('ID',"wp-download_monitor"); ?></a></th>
				<th scope="col"><a href="?page=Downloads&amp;sort=title"><?php _e('Title',"wp-download_monitor"); ?></a></th>
				<th scope="col"><a href="?page=Downloads&amp;sort=filename"><?php _e('File',"wp-download_monitor"); ?></a></th>
				<th scope="col" style="text-align:center"><?php _e('Version',"wp-download_monitor"); ?></th>
				<th scope="col"><a href="?page=Downloads&amp;sort=postDate"><?php _e('Posted on',"wp-download_monitor"); ?></a></th>
				<th scope="col"><?php _e('Posted by',"wp-download_monitor"); ?></th>
				<th scope="col" style="text-align:center"><?php _e('Hits',"wp-download_monitor"); ?></th>
				<th scope="col"><?php _e('Action',"wp-download_monitor"); ?></th>
				</tr>
			</thead>						
		<?php	
				// If current page number, use it 
				if(!isset($_REQUEST['p'])){ 
					$page = 1; 
				} else { 
					$page = $_REQUEST['p']; 
				}
				
				// Sort column
				$sort = "title";
				if ($_REQUEST['sort'] && ($_REQUEST['sort']=="id" || $_REQUEST['sort']=="filename" || $_REQUEST['sort']=="postDate")) $sort = $_REQUEST['sort'];
				
				$total_results = sprintf("SELECT COUNT(id) FROM %s;",
					$wpdb->escape($wp_dlm_db));
					
				// Figure out the limit for the query based on the current page number. 
				$from = (($page * 10) - 10); 
			
				$paged_select = sprintf("SELECT * FROM %s ORDER BY %s LIMIT %s,10;",
					$wpdb->escape( $wp_dlm_db ),
					$wpdb->escape( $sort ),
					$wpdb->escape( $from ));
					
				$download = $wpdb->get_results($paged_select);
				$total = $wpdb->get_var($total_results);
			
				// Figure out the total number of pages. Always round up using ceil() 
				$total_pages = ceil($total / 10);
			
				if (!empty($download)) {
					echo '<tbody id="the-list">';
					foreach ( $download as $d ) {
						$date = date("jS M Y", strtotime($d->postDate));
						
						$path = $wp_dlm_root."user_uploads/";
						$file = str_replace($path, "", $d->filename);
						$links = explode("/",$file);
						$file = end($links);
						echo ('<tr class="alternate">');
						echo '<td style="text-align:center">'.$d->id.'</td>
						<td>'.$d->title.'</td>
						<td>'.$file.'</td>
						<td style="text-align:center">'.$d->dlversion.'</td>
						<td>'.$date.'</td>
						<td>'.$d->user.'</td>
						<td style="text-align:center">'.$d->hits.'</td>
						<td><a href="?page=Downloads&amp;action=edit&amp;id='.$d->id.'&amp;sort='.$sort.'&amp;p='.$page.'"><img src="../wp-content/plugins/wp-download_monitor/img/edit.png" alt="Edit" title="Edit" /></a> <a href="?page=Downloads&amp;action=delete&amp;id='.$d->id.'&amp;sort='.$sort.'&amp;p='.$page.'"><img src="../wp-content/plugins/wp-download_monitor/img/cross.png" alt="Delete" title="Delete" /></a></td>';
						
					}
					echo '</tbody>';
				} else echo '<tr><th colspan="8">'.__('No downloads added yet.',"wp-download_monitor").'</th></tr>'; // FIXED: 1.6 - Colspan changed
		?>			
		</table>
        <br style="clear: both;"/>
        <div class="tablenav">
        	<div style="float:left">
				<?php
					// FIXED: 2 - Moved around to make more sense
					if ($total_pages>1)  { // FIXED: 1.6 - Stops it displaying when un-needed
					
						// Build Page Number Hyperlinks 
						if($page > 1){ 
							$prev = ($page - 1); 
							echo "<a href=\"?page=Downloads&amp;p=$prev&amp;sort=$sort\">&laquo; ".__('Previous',"wp-download_monitor")."</a> "; 
						} else echo "&laquo; ".__('Previous',"wp-download_monitor")."";

						for($i = 1; $i <= $total_pages; $i++){ 
							if(($page) == $i){ 
								echo " $i "; 
								} else { 
									echo " <a href=\"?page=Downloads&amp;p=$i&amp;sort=$sort\">$i</a> "; 
							} 
						} 

						// Build Next Link 
						if($page < $total_pages){ 
							$next = ($page + 1); 
							echo "<a href=\"?page=Downloads&amp;p=$next&amp;sort=$sort\">".__('Next',"wp-download_monitor")." &raquo;</a>"; 
						} else echo __('Next',"wp-download_monitor")." &raquo;";
						
					}
				?>	
            </div>
        	<br style="clear: both;"/>
        </div>			
    </div>
    <div id="poststuff" class="dlm">
        <div class="postbox close-me dlmbox">
            <h3><?php _e('Custom Download URL',"wp-download_monitor"); ?></h3>
            <div class="inside">
            	<?php _e('<p>Set the url of the downloads, e.g. <code>download/</code>. In this example a download link would look like this: 
                        <code>http://yoursite.com/download/2</code>.</p>
                        <p>Leave this option blank to use the default download path (<code>wp-content/plugins/wp-download_monitor/download.php?id=</code>)</p>
                        <p>You can also choose how to link to the download in it\'s url, e.g. selecting "filename" would make the link appear as <code>http://yoursite.com/download/filename.zip</code>.</p>',"wp-download_monitor"); ?>
                
                <form action="?page=Downloads&amp;action=saveurl" method="post">
                    <table class="niceblue">
                        <tr>
                            <th scope="col"><?php _e('Custom URL',"wp-download_monitor"); ?>:</th>
                            <td><?php echo get_bloginfo('wpurl'); ?>/<input type="text" name="url" value="<?php echo $downloadurl; ?>" />            
                            <select name="type" style="width:150px;padding:2px !important;cursor:pointer;">
                                    <option<?php if ($downloadtype=="ID") echo ' selected="selected" '; ?> value="ID"><?php _e('ID',"wp-download_monitor"); ?></option>
                                    <option<?php if ($downloadtype=="Title") echo ' selected="selected" '; ?> value="Title"><?php _e('Title',"wp-download_monitor"); ?></option>
                                    <option<?php if ($downloadtype=="Filename") echo ' selected="selected" '; ?> value="Filename"><?php _e('Filename',"wp-download_monitor"); ?></option>
                            </select></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" /></p>
                </form>
            </div>
        </div>
        <div class="postbox close-me dlmbox">
            <h3><?php _e('Instructions',"wp-download_monitor"); ?></h3>
            <div class="inside">
                <h4><?php _e('Adding a download',"wp-download_monitor"); ?></h4>
                <?php _e('<p>Add a download by clicking the \'Add new Download\' or \'Add Existing Download\' button. You will then be shown the \'Add Download\' form. On this form:</p>
                <ol>
                    <li>If using the <strong>\'add new download\'</strong> form, choose to upload your file from your hard disk by using the \'Select a file\' input</br>
                    or if using the <strong>\'add existing download\'</strong> form enter the url of an already existing file in the \'URL\' input (e.g <code>http://www.blue-anvil.com/download.zip</code>).</li>
                    <li>Add a <strong>Title</strong> for your download, and optionally a <strong>version</strong>. This name will be used when displaying the download link.</li>
                </ol>',"wp-download_monitor"); ?>
                
                <h4><?php _e('Displaying a download link',"wp-download_monitor"); ?></h4>
                <?php _e('<p>This plugin has special tags for use in Wordpress posts to display download links. If using the rich text editor you must enter these
                manually, however, if using the normal plain editor there is a dropdown menu to add them quicker. Replace \'id\' with the downloads id.</p>
                <ol>			
                    <li><strong>Link/hits</strong> - <code>[download#id]</code> <strong>Output example:</strong> <code><a href="download.php?id=1" title="Version 1 downloaded 10 times">Download title (10)</a></code></li>
                    <li><strong>Link w/o hits</strong> - <code>[download#id#nohits]</code> <strong>Output example:</strong> <code><a href="download.php?id=1" title="Version 1 downloaded 10 times">Download title</a></code></li>
                    <li><strong>URL only</strong> - <code>[download#id#url]</code> <strong>Output example:</strong> <code>download.php?id=1</code></li>
                    <li><strong>Hits only</strong> - <code>[download#id#hits]</code> <strong>Output example:</strong> <code>10</code></li>
                    <li><strong>Image with download link and hits</strong> - <code>[download#id#image]</code> <strong>Output example:</strong><br />
                    <a style="display:block;text-align:center;margin:0.5em;" class="dlimg" href="#" title="Download the File"><img src="../wp-content/plugins/wp-download_monitor/img/download.gif" alt="Download the File" /></a>
                    <p style="display:block;text-align:center;margin-bottom:12px;" class="dlstat">Downloaded a total of 10 times</p>
                    To help style the text/image from this output, the image is wrapped in an anchor with class <code>dlimg</code>, and the stats below <code>dlstat</code>.
                    </li>
					<li><strong>Link/hits/filesize</strong> - <code>[download#id#size]</code> <strong>Output example:</strong> <code><a href="download.php?id=1" title="Version 1 downloaded 10 times">Download title (10) - 2MB</a></code></li>
					<li><strong>Link/filesize</strong> - <code>[download#id#size#nohits]</code> <strong>Output example:</strong> <code><a href="download.php?id=1" title="Version 1 downloaded 10 times">Download title (2MB)</a></code></li>
                </ol>',"wp-download_monitor"); ?>
                
                <h4><?php _e('Additional template tags',"wp-download_monitor"); ?></h4>
                <?php _e('<p>There are a few other template tags to use in your wordpress templates. Replace \'$no\' with the amount of downloads to show.</p>
                <ol>
                    <li><strong>Most downloaded</strong> - <code>&lt;?php wp_dlm_show_downloads(1,$no); ?&gt;</code></li>
                    <li><strong>Most recent</strong> - <code>&lt;?php wp_dlm_show_downloads(2,$no); ?&gt;</code></li>
                    <li><strong>Random</strong> - <code>&lt;?php wp_dlm_show_downloads(3,$no); ?&gt;</code></li>
                </ol>		',"wp-download_monitor"); ?>
                <h4><?php _e('Show all downloads',"wp-download_monitor"); ?></h4>
                <?php _e('<p>Simply add the tag <code>[#show_downloads]</code> to a page.</p>',"wp-download_monitor"); ?>
                
            </div>
        </div>
        <div class="postbox close-me dlmbox">
            <h3><?php _e('About this plugin',"wp-download_monitor"); ?></h3>
            <div class="inside">
            	<?php _e('<p>The Wordpress Download monitor plugin was created by <a href="http://blue-anvil.com/">Mike Jolley</a>. The development
                of this plugin took a lot of time and effort, therefore donations are very welcome, and encouraged 
                (I have living costs you know) :)</p>',"wp-download_monitor"); ?>
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                    <input type="hidden" name="cmd" value="_s-xclick" />
                    <input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" />
                    <img alt="" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1" />
                    <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHmAYJKoZIhvcNAQcEoIIHiTCCB4UCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYBBcwapPTXpPF47IaRDJNW7rzSb7LQBCAUTzQ3JyVbyL/Lvfk8s1R3tpnCc+0KWkIsGa4Hml9sz77zshMIsQZveo6/wniQgfK100n9ks03KXPblXYFn4OgnTW1C9y36f2kAw1GCK7uCHk51M1ouPXcOdpHijkTlYhYw2f7o8m6vSTELMAkGBSsOAwIaBQAwggEUBgkqhkiG9w0BBwEwFAYIKoZIhvcNAwcECCwkOMnfKDIugIHwNoDAhA1OzWclNhaPBY1bq0weaJNCRs7Pg/Z1QMiw9+w+yBy0H54ahFdkJ4IJcFYPAGoFi+npTsuPd5j9GMsr52RzRtNQjdhv6UqnMDWBJuYQdJ4/iEoRmUjpIS2CUyq5GIQwb2nTkEu1ZpP5cLCaudOVZS8W7nJzHzwJmk58A2SYnKCchwpHsZUQfdXJTaXg14I55DyHV3Rg+7P53zCnHfNrsAkw8aNNZLKz0B1Xiv8JFOYR2dBOPMRGpofmxdO/UDjZQjvqyxr1Hggm8To3VKZhrjoss8vs4NrJ3/Swg6fV7S1x9Fft5e2PQ3JXZI0/oIIDhzCCA4MwggLsoAMCAQICAQAwDQYJKoZIhvcNAQEFBQAwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tMB4XDTA0MDIxMzEwMTMxNVoXDTM1MDIxMzEwMTMxNVowgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDBR07d/ETMS1ycjtkpkvjXZe9k+6CieLuLsPumsJ7QC1odNz3sJiCbs2wC0nLE0uLGaEtXynIgRqIddYCHx88pb5HTXv4SZeuv0Rqq4+axW9PLAAATU8w04qqjaSXgbGLP3NmohqM6bV9kZZwZLR/klDaQGo1u9uDb9lr4Yn+rBQIDAQABo4HuMIHrMB0GA1UdDgQWBBSWn3y7xm8XvVk/UtcKG+wQ1mSUazCBuwYDVR0jBIGzMIGwgBSWn3y7xm8XvVk/UtcKG+wQ1mSUa6GBlKSBkTCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb22CAQAwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOBgQCBXzpWmoBa5e9fo6ujionW1hUhPkOBakTr3YCDjbYfvJEiv/2P+IobhOGJr85+XHhN0v4gUkEDI8r2/rNk1m0GA8HKddvTjyGw/XqXa+LST            lDYkqI8OwR8GEYj4efEtcRpRYBxV8KxAW93YDWzFGvruKnnLbDAF6VR5w/cCMn5hzGCAZowggGWAgEBMIGUMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbQIBADAJBgUrDgMCGgUAoF0wGAYJKoZIhvcNAQkDMQsGCSqGSIb3DQEHATAcBgkqhkiG9w0BCQUxDxcNMDYwOTEzMDkzNzIyWjAjBgkqhkiG9w0BCQQxFgQULYNalwI9CNnoxfsE3a8NfxujX7gwDQYJKoZIhvcNAQEBBQAEgYBnFV3uy0eVWLUaRL4tCtpP3Q70MbAV1Gu6CPf/AbpJrDdqgAwDlv3krA7rIkB+JT1tVsKqw9iBfOgphOSlOn47w25wt2/X6zmLBawnibHnYIWn1ZeTCgn6izgeb/zb4P7xZwUbN6FrgayWXP6owhSKClhwsMvvegHK8zrqbZVDaw==-----END PKCS7-----
                    " />
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript">
		<!--
		jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
		jQuery('.togbox').click( function() { jQuery(jQuery(this).parent().parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox.close-me').each(function(){
			jQuery(this).addClass("closed");
		});
		//-->
	</script>
<?php
	}
}



################################################################################
// mod_rewrite rules
################################################################################
function wp_dlm_rewrite($rewrite) {
		$url = get_option('wp_dlm_url');
		$blog = get_bloginfo('wpurl');

		$rule = ('
Options +FollowSymLinks
RewriteEngine on
RewriteRule ^'.$url.'(.*) wp-content/plugins/wp-download_monitor/download.php?id=$1 [L]
');
		return $rule.$rewrite;	
}

// Hook in.
$url = get_option('wp_dlm_url');
if (!empty($url)) add_filter('mod_rewrite_rules', 'wp_dlm_rewrite');



################################################################################
// TEMPLATE TAG
################################################################################
function wp_dlm_show_downloads($mode,$no) {
	//shows downloads in the sidebar
	//set globals
	global $table_prefix,$wpdb,$wp_dlm_root,$allowed_extentions,$max_upload_size,$wp_dlm_db;
	
	switch ($mode) {
		case (1) :
			$query = sprintf("SELECT * FROM %s ORDER BY hits DESC LIMIT %s;",
			$wpdb->escape( $wp_dlm_db ),
			$wpdb->escape( $no ));
		break;
		case (2) :
			$query = sprintf("SELECT * FROM %s ORDER BY postDate LIMIT %s;",
			$wpdb->escape( $wp_dlm_db ),
			$wpdb->escape( $no ));
		break;
		case (3) :
			$query = sprintf("SELECT * FROM %s ORDER BY rand() LIMIT %s;",
			$wpdb->escape( $wp_dlm_db ),
			$wpdb->escape( $no ));
		break;
	}
	if (!empty($query)) {
	
		$url = get_option('wp_dlm_url');
		$downloadurl = get_bloginfo('wpurl').'/'.$url;	
		if (empty($url)) $downloadurl = $wp_dlm_root.'download.php?id=';
		
	
		$dl = $wpdb->get_results($query);
		
		$downloadtype = get_option('wp_dlm_type');		
	
		if (!empty($dl)) {
			echo '<ul class="downloadList">';
			foreach($dl as $d) {
				$date = date("jS M Y", strtotime($d->postDate)); // FIXED: 1.6 - Capital D modded
				switch ($downloadtype) {
					case ("Title") :
							$downloadlink = $d->title;
					break;
					case ("Filename") :
							$downloadlink = $d->filename;
							$links = explode("/",$downloadlink);
							$downloadlink = end($links);
					break;
					default :
							$downloadlink = $d->id;
					break;
				}
				switch ($mode) {
					case (1) :
						echo '<li><a href="'.$downloadurl.$downloadlink.'" title="'.__('Version',"wp-download_monitor").' '.$d->dlversion.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a></li>';
					break;
					case (2) :
						echo '<li><a href="'.$downloadurl.$downloadlink.'" title="'.__('Version',"wp-download_monitor").' '.$d->dlversion.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" >'.$d->title.' <span>('.$date.')</span></a></li>';
					break;
					case (3) :
						echo '<li><a href="'.$downloadurl.$downloadlink.'" title="'.__('Version',"wp-download_monitor").' '.$d->dlversion.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a></li>';
					break;
				}
			}
			echo '</ul>';
		}
	}	
	return;
}
function wp_dlm_all() {
	//shows full download list
	global $table_prefix,$wpdb,$wp_dlm_root,$allowed_extentions,$max_upload_size,$wp_dlm_db;
	
	$query = sprintf("SELECT * FROM %s ORDER BY postDate DESC;",
			$wpdb->escape( $wp_dlm_db ));

	if (!empty($query)) {
	
		$url = get_option('wp_dlm_url');
		$downloadurl = get_bloginfo('wpurl').'/'.$url;	
		if (empty($url)) $downloadurl = $wp_dlm_root.'download.php?id=';
	
		$dl = $wpdb->get_results($query);
		
		$downloadtype = get_option('wp_dlm_type');		
	
		if (!empty($dl)) {
			$retval = '<ul class="downloadList">';
			foreach($dl as $d) {
				$date = date("jS F Y", strtotime($d->postDate));
				switch ($downloadtype) {
					case ("Title") :
							$downloadlink = $d->title;
					break;
					case ("Filename") :
							$downloadlink = $d->filename;
							$links = explode("/",$downloadlink);
							$downloadlink = end($links);
					break;
					default :
							$downloadlink = $d->id;
					break;
				}
				$retval .= '<li><a href="'.$downloadurl.$downloadlink.'" title="'.__('Version',"wp-download_monitor").' '.$d->dlversion.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").' - '.__('Added',"wp-download_monitor").' '.$date.'" >'.$d->title.' ('.$d->hits.')</a></li>';
			}
			$retval .='</ul>';
		}
	}	
	return $retval;
}
################################################################################
// SHOW ALL DOWNLOADS TAG
################################################################################
function wp_dlm_ins_all($data) {
	//echo "-Test 1-";
	if (substr_count($data,"[#show_downloads]")) {
		return str_replace("[#show_downloads]",wp_dlm_all(), $data);
	} else return $data;
} 
add_filter('the_content', 'wp_dlm_ins_all',1,1); 

/*
Easy PHP Upload - version 2.31
A easy to use class for your (multiple) file uploads

Copyright (c) 2004 - 2006, Olaf Lederer
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
    * Neither the name of the finalwebsites.com nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

______________________________________________________________________
available at http://www.finalwebsites.com/snippets.php
Comments & suggestions: http://www.webdigity.com/index.php/board,73.0.html,ref.olaf
*/
 
class wp_dlm_file_upload {

    var $the_file;
	var $the_temp_file;
    var $upload_dir;
	var $replace;
	var $do_filename_check;
	var $max_length_filename = 100;
    var $extensions;
	var $ext_string;
	var $http_error;
	var $rename_file; // if this var is true the file copy get a new name
	var $file_copy; // the new name
	var $message = array();
	var $create_directory = true;
	
	function wp_dlm_file_upload() {
		$this->rename_file = false;
		$this->ext_string = "";
	}
	function show_error_string() {
		$msg_string = "";
		foreach ($this->message as $value) {
			$msg_string .= $value."<br />\n";
		}
		return $msg_string;
	}
	function set_file_name($new_name = "") { // this "conversion" is used for unique/new filenames 
		if ($this->rename_file) {
			if ($this->the_file == "") return;
			$name = ($new_name == "") ? strtotime("now") : $new_name;
			sleep(3);
			$name = $name.$this->get_extension($this->the_file);
		} else {
			$name = str_replace(" ", "_", $this->the_file); // space will result in problems on linux systems
		}
		return $name;
	}
	function upload($to_name = "") {
		$new_name = $this->set_file_name($to_name);
		if ($this->check_file_name($new_name)) {
			if ($this->validateExtension()) {
				if (is_uploaded_file($this->the_temp_file)) {
					$this->file_copy = $new_name;
					if ($this->move_upload($this->the_temp_file, $this->file_copy)) {
						$this->message[] = $this->error_text($this->http_error);
						if ($this->rename_file) $this->message[] = $this->error_text(16);
						return true;
					}
				} else {
					$this->message[] = $this->error_text($this->http_error);
					return false;
				}
			} else {
				$this->show_extensions();
				$this->message[] = $this->error_text(11);
				return false;
			}
		} else {
			return false;
		}
	}
	function check_file_name($the_name) {
		if ($the_name != "") {
			if (strlen($the_name) > $this->max_length_filename) {
				$this->message[] = $this->error_text(13);
				return false;
			} else {
				if ($this->do_filename_check == "y") {
					if (preg_match("/^[a-z0-9_]*\.(.){1,5}$/i", $the_name)) {
						return true;
					} else {
						$this->message[] = $this->error_text(12);
						return false;
					}
				} else {
					return true;
				}
			}
		} else {
			$this->message[] = $this->error_text(10);
			return false;
		}
	}
	function get_extension($from_file) {
		$ext = strtolower(strrchr($from_file,"."));
		return $ext;
	}
	function validateExtension() {
		$extension = $this->get_extension($this->the_file);
		$ext_array = $this->extensions;
		if (in_array($extension, $ext_array)) { 
			// check mime type hier too against allowed/restricted mime types (boolean check mimetype)
			return true;
		} else {
			return false;
		}
	}
	// this method is only used for detailed error reporting
	function show_extensions() {
		$this->ext_string = implode(" ", $this->extensions);
	}
	function move_upload($tmp_file, $new_file) {
		if ($this->existing_file($new_file)) {
			$newfile = $this->upload_dir.$new_file;
			if ($this->check_dir($this->upload_dir)) {
				if (move_uploaded_file($tmp_file, $newfile)) {
					umask(0);
					chmod($newfile , 0644);
					return true;
				} else {
					return false;
				}
			} else {
				$this->message[] = $this->error_text(14);
				return false;
			}
		} else {
			$this->message[] = $this->error_text(15);
			return false;
		}
	}
	function check_dir($directory) {
		if (!is_dir($directory)) {
			if ($this->create_directory) {
				umask(0);
				mkdir($directory, 0777);
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}
	function existing_file($file_name) {
		if ($this->replace == "y") {
			return true;
		} else {
			if (file_exists($this->upload_dir.$file_name)) {
				return false;
			} else {
				return true;
			}
		}
	}
	function get_uploaded_file_info($name) {
		$str = "File name: ".basename($name)."\n";
		$str .= "File size: ".filesize($name)." bytes\n";
		if (function_exists("mime_content_type")) {
			$str .= "Mime type: ".mime_content_type($name)."\n";
		}
		if ($img_dim = getimagesize($name)) {
			$str .= "Image dimensions: x = ".$img_dim[0]."px, y = ".$img_dim[1]."px\n";
		}
		return $str;
	}
	// this method was first located inside the foto_upload extension
	function del_temp_file($file) {
		$delete = @unlink($file); 
		clearstatcache();
		if (@file_exists($file)) { 
			$filesys = eregi_replace("/","\\",$file); 
			$delete = @system("del $filesys");
			clearstatcache();
			if (@file_exists($file)) { 
				$delete = @chmod ($file, 0644); 
				$delete = @unlink($file); 
				$delete = @system("del $filesys");
			}
		}
	}
	// this function creates a file field and if $show_alternate is true it will show a text field if the given file already exists
	// there is also a submit button to remove the text field value 
	function create_file_field($element, $label = "", $length = 25, $show_replace = true, $replace_label = "Replace old file?", $file_path = "", $file_name = "", $show_alternate = false, $alt_length = 30, $alt_btn_label = "Delete image") {
		$field = ($label != "") ? "<label>".$label."</label>\n" : "";
		$file_field = "<input type=\"file\" name=\"".$element."\" size=\"".$length."\" />\n";
		$file_field .= ($show_replace) ? "<span>".$replace_label."</span><input type=\"checkbox\" name=\"replace\" value=\"y\" />" : "";
		if ($file_name != "" && $show_alternate) {
			$field .= "<input type=\"text\" name=\"".$element."\" size=\"".$alt_length."\" value=\"".$file_name."\" readonly=\"readonly\"";
			$field .= (!@file_exists($file_path.$file_name)) ? " title=\"".sprintf($this->error_text(17), $file_name)."\" />\n" : " />\n";
			$field .= "<input type=\"checkbox\" name=\"del_img\" value=\"y\" /><span>".$alt_btn_label."</span>\n";
		} else {
			$field .= $file_field;
		} 
		return $field;
	}
	// some error (HTTP)reporting, change the messages or remove options if you like.
	function error_text($err_num) {

		// start http errors
		$error[0] = __('File',"wp-download_monitor").": <b>".$this->the_file."</b> ".__('successfully uploaded!',"wp-download_monitor");
		$error[1] = __("The uploaded file exceeds the max. upload filesize directive in the server configuration.","wp-download_monitor");
		$error[2] = __("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.","wp-download_monitor");
		$error[3] = __("The uploaded file was only partially uploaded","wp-download_monitor");
		$error[4] = __("No file was uploaded","wp-download_monitor");
		// end  http errors
		$error[10] = __("Please select a file for upload.","wp-download_monitor");
		$error[11] = __("Only files with the following extensions are allowed:","wp-download_monitor")." <b>".$this->ext_string."</b>";
		$error[12] = __("Sorry, the filename contains invalid characters. Use only alphanumerical chars and separate parts of the name (if needed) with an underscore. <br>A valid filename ends with one dot followed by the extension.","wp-download_monitor");
		$error[13] = __("The filename exceeds the maximum length of ","wp-download_monitor").$this->max_length_filename.__("characters.","wp-download_monitor");
		$error[14] = __("Sorry, the upload directory doesn't exist!","wp-download_monitor");
		$error[15] = __("Uploading","wp-download_monitor")." <b>".$this->the_file."...Error!</b> ".__("Sorry, a file with this name already exitst.","wp-download_monitor");
		$error[16] = __("The uploaded file is renamed to","wp-download_monitor")." <b>".$this->file_copy."</b>";
		$error[17] = __("The file %s does not exist.","wp-download_monitor");

		return $error[$err_num];
	}
}
?>