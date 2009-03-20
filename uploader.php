<?php
//define(ABSPATH,'../../../');
//require_once( ABSPATH . 'wp-config.php' );

//require_once('../../../wp-config.php' );

/** Load WordPress Administration Bootstrap */
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
		echo '<p>Failed to load bootstrap.</p>';
		exit;
	}

}

require_once(ABSPATH.'wp-admin/admin.php');

load_plugin_textdomain('wp-download_monitor', 'wp-content/plugins/download-monitor/', 'download-monitor/');

// REPLACE ADMIN URL
if (function_exists('admin_url')) {
	wp_admin_css_color('classic', __('Blue'), admin_url("css/colors-classic.css"), array('#073447', '#21759B', '#EAF3FA', '#BBD8E7'));
	wp_admin_css_color('fresh', __('Gray'), admin_url("css/colors-fresh.css"), array('#464646', '#6D6D6D', '#F1F1F1', '#DFDFDF'));
} else {
	wp_admin_css_color('classic', __('Blue'), get_bloginfo('wpurl').'/wp-admin/css/colors-classic.css', array('#073447', '#21759B', '#EAF3FA', '#BBD8E7'));
	wp_admin_css_color('fresh', __('Gray'), get_bloginfo('wpurl').'/wp-admin/css/colors-fresh.css', array('#464646', '#6D6D6D', '#F1F1F1', '#DFDFDF'));
}

wp_enqueue_script( 'common' );
wp_enqueue_script( 'jquery-color' );

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

if (!current_user_can('upload_files'))
	wp_die(__('You do not have permission to upload files.'));
	
load_plugin_textdomain('wp-download_monitor', '/');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<title><?php bloginfo('name') ?> &rsaquo; <?php _e('Uploads'); ?> &#8212; <?php _e('WordPress'); ?></title>
	<?php
		wp_enqueue_style( 'global' );
		wp_enqueue_style( 'wp-admin' );
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
	?>
	<script type="text/javascript">
	//<![CDATA[
		function addLoadEvent(func) {if ( typeof wpOnload!='function'){wpOnload=func;}else{ var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}}
	//]]>
	</script>
	<?php
	do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head');
	if ( is_string($content_func) )
		do_action( "admin_head_{$content_func}" );
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
		
		jQuery.noConflict();
		(function($) { 
		  $(function() {								 
		  										  	
		  	$('#customfield_list tr.alternate').each(function(i){
		  	
		  		var index = i + 1;
		  		$("input[name='meta[" + index + "][remove]']").click(function(){
		    		$('input, textarea', $(this).parent().parent()).val('');
		    		$(this).parent().parent().hide();
		    		return false;
		    	});
		  	
		  	});										  	
		    
		    $('#addmetasub').click(function(){
		    
		    	var newfield = $('#customfield_list tr.alternate').size() + 1;
		    	
		    	$('#addmetarow').before('<tr class="alternate"><td class="left" style="vertical-align:top;"><label class="hidden" for="meta[' + newfield + '][key]">Key</label><input name="meta[' + newfield +'][key]" id="meta[' + newfield +'][key]" tabindex="6" size="20" value="" type="text" style="width:95%"><input type="submit" name="meta[' + newfield +'][remove]" class="button" value="<?php _e('remove',"wp-download_monitor"); ?>" /></td><td style="vertical-align:top;"><label class="hidden" for="meta[' + newfield + '][value]">Value</label><textarea name="meta[' + newfield + '][value]" id="meta[' + newfield + '][value]" tabindex="6" rows="2" cols="30" style="width:95%"></textarea></td></tr>');									    	
		    	
		    	$("input[name='meta[" + newfield + "][remove]']").click(function(){
		    		$('input, textarea', $(this).parent().parent()).val('');
		    		$(this).parent().parent().hide();
		    		return false;
		    	});
		    	
		    	return false;
		    	
		    });
		    
		  });
		})(jQuery);

	/* ]]> */
	</script>
</head>
<body id="media-upload">
	<div id="media-upload-header">
		<ul id='sidemenu'>
			<li id='tab-add'><a href='uploader.php?tab=add' <?php if ($_GET['tab']=='add') echo "class='current'"; ?>><?php _e('Add New Download',"wp-download_monitor"); ?></a></li>
			<li id='tab-downloads'><a href='uploader.php?tab=downloads' <?php if ($_GET['tab']=='downloads') echo "class='current'"; ?>><?php _e('View Downloads',"wp-download_monitor"); ?></a></li>
		</ul>
	</div>
	<?php
	// Get the Tab
	$tab = $_GET['tab'];
	switch ($tab) {
	
		case 'add' :
			if ($_POST) {
						
				// Form processing
				global $table_prefix,$wpdb;
				$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
				$wp_dlm_db_cats = $table_prefix."DLM_CATS";	
				$wp_dlm_db_meta = $table_prefix."DLM_META";
											
				//get postdata
				$title = htmlspecialchars(trim(stripslashes($_POST['title'])));
				$filename = htmlspecialchars(trim(stripslashes($_POST['filename'])));									
				$dlversion = htmlspecialchars(trim(stripslashes($_POST['dlversion'])));
				$dlhits = htmlspecialchars(trim($_POST['dlhits']));
				$postDate = $_POST['postDate'];
				$user = $_POST['user'];
				$members = (isset($_POST['memberonly'])) ? 1 : 0;
				$download_cat = $_POST['download_cat'];
				$mirrors = htmlspecialchars(trim($_POST['mirrors']));
				$file_description = trim(stripslashes($_POST['file_description']));
				
				if ($_POST['insertonlybutton']) {
											
					//validate fields
					if (empty( $_POST['title'] )) $errors=__('<div id="media-upload-error">Required field: <strong>Title</strong> omitted</div>',"wp-download_monitor");
					if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
					if (!is_numeric($_POST['dlhits'] )) $errors=__('<div id="media-upload-error">Invalid <strong>hits</strong> entered</div>',"wp-download_monitor");
												
					if (empty( $filename ) && $_FILES['upload'] && empty($errors)) {
						//attempt to upload file
						$time = current_time('mysql');
						$overrides = array('test_form'=>false);
	
						$file = wp_handle_upload($_FILES['upload'], $overrides, $time);
	
						if ( !isset($file['error']) ) {
							$full_path = $file['url'];
							$info = $file['url'];
							$filename = $file['url'];
						} 
						else $errors = '<div class="error">'.$file['error'].'</div>';	
															
					} elseif (empty($errors)) {
						if ( empty( $filename ) ) $errors=__('<div id="media-upload-error">No file selected</div>',"wp-download_monitor");
					} 
												
					//save to db
					if ( empty($errors ) ) {	
			
						if ($my_upload->replace=="y") { /* !!!!*/
								$query_del = sprintf("DELETE FROM %s WHERE filename='%s';",
								$wpdb->escape( $wp_dlm_db ),
								$wpdb->escape( $filename ));
								$wpdb->query($query_del);
						} 
						
						$query_add = sprintf("INSERT INTO %s (title, filename, dlversion, postDate, hits, user, members,category_id,mirrors, file_description) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
						$wpdb->escape( $wp_dlm_db ),
						$wpdb->escape( $title ),
						$wpdb->escape( $filename ),
						mysql_real_escape_string( $dlversion ),
						$wpdb->escape( $_POST['postDate'] ),
						mysql_real_escape_string( $_POST['dlhits'] ),
						$wpdb->escape( $_POST['user'] ),
						$wpdb->escape( $members ),
						$wpdb->escape($download_cat),
						$wpdb->escape($mirrors),
						$wpdb->escape($file_description)
						);										
							
						$result = $wpdb->query($query_add);
						if ($result) {								
							$newdownloadID = $wpdb->insert_id;
							
							// Process and save meta/custom fields
							$index = 1;
							$values = array();
							if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
							{
								if (trim($meta['key'])) {
									$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim(stripslashes($meta['key'])))))).'", "'.$wpdb->escape(stripslashes($meta['value'])).'", '.$newdownloadID.')';
									$index ++;
								}
							}
							$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");							
							
						}
						else _e('<div id="media-upload-error">Error saving to database - check downloads table exists.</div>',"wp-download_monitor");						
						
					}
				}
				
			}
			if ($errors || !$_POST['insertonlybutton']) {
			?>
			<form enctype="multipart/form-data" method="post" action="uploader.php?tab=add" id="media-upload-form type-form validate" id="download-form">
								
				<h3><?php _e('Download Information',"wp-download_monitor"); ?></h3>
				<?php echo $errors; ?>
				
				<table class="describe"><tbody>
					<tr>
						<th valign="top" scope="row" class="label" >
							<span class="alignleft"><label for="dltitle"><?php _e('Title',"wp-download_monitor"); ?></label></span>
							<span class="alignright"><abbr title="required" class="required">*</abbr></span>
						</th>
						<td class="field"><input type="text" value="<?php echo $title; ?>" name="title" id="dltitle" maxlength="200" /></td> 
					</tr>
					<tr>
                        <th valign="top" scope="row" class="label"><?php _e('Description',"wp-download_monitor"); ?>:</th> 
                        <td class="field"><textarea name="file_description" cols="50" rows="2"><?php echo $file_description; ?></textarea></td> 
                    </tr>
					<tr>
						<th valign="top" scope="row" class="label">
							<span class="alignleft"><label for="dlversion"><?php _e('Version',"wp-download_monitor"); ?></label></span>
						</th> 
						<td class="field"><input type="text" value="<?php echo $dlversion; ?>" name="dlversion" id="dlversion" /></td> 
					</tr>
					<tr>
						<th valign="top" scope="row" class="label">
							<span class="alignleft"><label for="dlhits"><?php _e('Starting hits',"wp-download_monitor");?></label></span>
						</th> 
						<td class="field"><input type="text" value="<?php if ($dlhits>0) echo $dlhits; else echo 0; ?>" name="dlhits" id="dlhits" maxlength="50" /></td> 
					</tr>
					<tr>												
						<th valign="top" scope="row" class="label">
							<span class="alignleft"><label for="download_cat"><?php _e('Category',"wp-download_monitor"); ?></label></span>
						</th> 
						<td class="field"><select name="download_cat" id="download_cat">
							<option value="">N/A</option>
							<?php
								$query_select_cats = sprintf("SELECT * FROM %s WHERE parent=0 ORDER BY id;",
									$wpdb->escape( $wp_dlm_db_cats ));	
								$cats = $wpdb->get_results($query_select_cats);
								
								if (!empty($cats)) {
									foreach ( $cats as $c ) {
										echo '<option ';
										if ($_POST['download_cat']==$c->id) echo 'selected="selected"';
										echo 'value="'.$c->id.'">'.$c->id.' - '.$c->name.'</option>';
										echo get_option_children_cats($c->id, "$c->name &mdash; ", $_POST['download_cat']);
									}
								} 
							?>
						</select></td>
					</tr>
					<tr><td></td><td class="help" style="font-size:11px;"><?php _e('Categories are optional and allow you to group and organise simular downloads.',"wp-download_monitor"); ?></td></tr>
					<tr>												
						<th valign="top" scope="row" class="label">
							<span class="alignleft"><label for="memberonly"><?php _e('Member only file?',"wp-download_monitor"); ?></label></span>
						</th> 
						<td class="field"><input type="checkbox" name="memberonly" id="memberonly" <?php if ($members==1) echo "checked='checked'"; ?> /></td>
					</tr>
					<tr><td></td><td class="help" style="font-size:11px;"><?php _e('If chosen, only logged in users will be able to access the file via a download link. It is a good idea to give the file a name which cannot be easily guessed and accessed directly. BONUS: Add a meta key called min-level to set the minimum user level needed to download the file.',"wp-download_monitor"); ?></td></tr>					
				</tbody></table>
				
				<h3><?php _e('Upload/link to existing file',"wp-download_monitor"); ?></h3>
				
				<input type="hidden" name="MAX_FILE_SIZE" value="<?php 
					
					$max_upload_size = "";
					
					if (!function_exists('let_to_num')) {
						function let_to_num($v){ 
							$l = substr($v, -1);
						    $ret = substr($v, 0, -1);
						    switch(strtoupper($l)){
						    case 'P':
						        $ret *= 1024;
						    case 'T':
						        $ret *= 1024;
						    case 'G':
						        $ret *= 1024;
						    case 'M':
						        $ret *= 1024;
						    case 'K':
						        $ret *= 1024;
						        break;
						    }
						    return $ret;
						}
					}

					if (function_exists('ini_get')) {
						$max_upload_size = min(let_to_num(ini_get('post_max_size')), let_to_num(ini_get('upload_max_filesize')));
						$max_upload_size_text = __(' (defined in php.ini)',"wp-download_monitor");
					}
					
					if (!$max_upload_size || $max_upload_size==0) {
						$max_upload_size = 8388608;
						$max_upload_size_text = '';
					}	
					
					echo $max_upload_size;
	
				 ?>" />
				<input type="hidden" name="postDate" value="<?php echo date(__('Y-m-d H:i:s',"wp-download_monitor")) ;?>" />
				<?php 
										global $userdata;
										get_currentuserinfo();										
										echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
				?>	
				<table class="describe"><tbody>
					<tr>
						<th valign="top" scope="row" class="label">
							<span class="alignleft"><label for="file"><?php _e('Upload File',"wp-download_monitor"); ?></label></span>
						</th> 
						<td class="field"><input type="file" name="upload" style="width:320px;" id="file" /></td>												
					</tr>
					<tr><td></td><td class="help" style="font-size:11px;"><?php _e('Max. filesize',"wp-download_monitor"); echo $max_upload_size_text; ?> = <?php echo $max_upload_size; ?> <?php _e('bytes',"wp-download_monitor"); ?>. <?php _e('If a file with the same name already exists in the upload directly, this file will be renamed automatically.',"wp-download_monitor"); ?></td></tr>
					<tr valign="top">
						<td colspan="2"><p style="text-align:center"><?php _e('&mdash; OR &mdash;'); ?></p></td>				
					</tr>
					<tr>
						<th valign="top" scope="row" class="label">
							<span class="alignleft"><label for="filename"><?php _e('File URL'); ?></label></span>
						</th>
						<td class="field"><input id="filename" name="filename" value="<?php echo $filename; ?>" type="text"></td>
					</tr>
				</tbody></table>
				
				<h3><?php _e('Download Mirrors',"wp-download_monitor"); ?></h3>
				<table class="describe"><tbody>
					<tr>												
                        <th valign="top" scope="row" class="label"><strong><?php _e('Download Mirrors',"wp-download_monitor"); ?></strong></th> 
                        <td class="field"><textarea name="mirrors" cols="50" rows="2"><?php echo $mirrors; ?></textarea></td>
                    </tr>
                    <tr><td></td><td class="help" style="font-size:11px;"><?php _e('Optionally list the url\'s of any mirrors here (1 per line). Download monitor will randomly pick one of these mirrors when serving the download.',"wp-download_monitor"); ?></td></tr>
				</tbody></table>
	            <h3><?php _e('Custom fields',"wp-download_monitor"); ?></h3>	            
				<table style="width:100%">
					<thead>
						<tr>
							<th class="left"><?php _e('Name',"wp-download_monitor"); ?></th>
							<th><?php _e('Value',"wp-download_monitor"); ?></th>
						</tr>			
					</thead>
					<tbody id="customfield_list">
						<?php
						$index = 1;
						if ($_POST) {
							if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
							{
								if (!$meta['remove']) {
									if (trim($meta['key'])) {
									echo '<tr class="alternate">
										<td class="left" style="vertical-align:top;">
											<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="'.strtolower((str_replace(' ','-',stripslashes($meta['key'])))).'" type="text" style="width:95%">
											<input type="submit" name="meta['.$index.'][remove]" class="button" value="'.__('remove',"wp-download_monitor").'" />
										</td>
										<td style="vertical-align:top;"><label class="hidden" for="meta['.$index.'][value]">Value</label><textarea name="meta['.$index.'][value]" id="meta['.$index.'][value]" tabindex="6" rows="2" cols="30" style="width:95%">'.stripslashes($meta['value']).'</textarea></td>
									</tr>';	
									}							
								}		
								$index ++;					
							}
							if ($_POST['addmeta']) {
								echo '<tr class="alternate">
										<td class="left" style="vertical-align:top;">
											<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="" type="text" style="width:95%">
											<input type="submit" name="meta['.$index.'][remove]" class="button" value="'.__('remove',"wp-download_monitor").'" />
										</td>
										<td style="vertical-align:top;"><label class="hidden" for="meta['.$index.'][value]">Value</label><textarea name="meta['.$index.'][value]" id="meta['.$index.'][value]" tabindex="6" rows="2" cols="30" style="width:95%"></textarea></td>
								</tr>';
							}
						} 											
						?>
						<tr id="addmetarow">
							<td colspan="2" class="submit"><input id="addmetasub" name="addmeta" value="<?php _e('Add Custom Field',"wp-download_monitor"); ?>" type="submit"></td>
						</tr>
					</tbody>
				</table>				
				<p class="submit"><input type="submit" class="button button-primary" name="insertonlybutton" value="<?php _e('Save new download',"wp-download_monitor"); ?>" /></p>
				
			</form>
			<?php } else {
				// GENERATE CODE TO INSERT
				$html = '[download id="'.$newdownloadID.'"';
				?>
				<div style="margin:1em;">
				<h3><?php _e('Insert new download into post'); ?></h3>
				
				<p class="submit"><label for="format"><?php _e('Insert into post using format:',"wp-download_monitor"); ?></label> <select style="vertical-align:middle;" name="format" id="format">
							<option value="0">Default</option>
							<?php								
								$query_select_formats = sprintf("SELECT * FROM %s ORDER BY id;",
									$wpdb->escape( $wp_dlm_db_formats ));	
								$formats = $wpdb->get_results($query_select_formats);
			
								if (!empty($formats)) {
									foreach ( $formats as $f ) {
										echo '<option value="'.$f->id.'">'.$f->name.'</option>';
									}
								}
							?>         
						</select>
				<?php echo '<input type="submit" id="insertdownload" class="button button-primary" name="insertintopost" value="'.__('Insert into post').'" />'; ?></p>

				<script type="text/javascript">
					/* <![CDATA[ */
					jQuery('#insertdownload').click(function(){
					var win = window.dialogArguments || opener || parent || top;
					if (jQuery('#format').val()>0) win.send_to_editor('<?php echo $html; ?> format="' + jQuery('#format').val() + '"]');
					else win.send_to_editor('<?php echo $html; ?>]');
					});
					/* ]]> */
				</script>
				</div>
			<?php
			exit;
			}
			
		break;
		case 'downloads' :
			// Show table of downloads			
			?>
			<form enctype="multipart/form-data" method="post" action="uploader.php?tab=downloads" class="media-upload-form" id="gallery-form">
			
			<table style="float:right;width:300px;margin-bottom:8px;" cellpadding="0" cellspacing="0">
				<tr>
					<th scope="row" style="vertical-align:middle;">
						<label for="format" style="font-size:12px;text-align:right;margin-right:8px;margin-top:4px;"><?php _e('Insert into post using format:',"wp-download_monitor"); ?></label>
					</th>
					<td style="vertical-align:middle;text-align:right;"><select name="format" id="format">
						<option value="0"><?php _e('Default',"wp-download_monitor"); ?></option>
						<?php								
							$query_select_formats = sprintf("SELECT * FROM %s ORDER BY id;",
								$wpdb->escape( $wp_dlm_db_formats ));	
							$formats = $wpdb->get_results($query_select_formats);
		
							if (!empty($formats)) {
								foreach ( $formats as $f ) {
									echo '<option value="'.$f->id.'">'.$f->name.'</option>';
								}
							}
						?>         
					</select></td>
				</tr>
			</table>	
			<h3><?php _e('Downloads'); ?></h3>
	        <table class="widefat" style="width:100%;" cellpadding="0" cellspacing="0"> 
				<thead>
					<tr>				
						<th scope="col" style="text-align:center;vertical-align:middle"><a href="?tab=downloads&amp;sort=id"><?php _e('ID',"wp-download_monitor"); ?></a></th>
						<th scope="col" style="vertical-align:middle"><a href="?tab=downloads&amp;sort=title"><?php _e('Title',"wp-download_monitor"); ?></a></th>
						<th scope="col" style="vertical-align:middle"><a href="?tab=downloads&amp;sort=filename"><?php _e('File',"wp-download_monitor"); ?></a></th>
		                <th scope="col" style="text-align:center;vertical-align:middle;"><?php _e('Category',"wp-download_monitor"); ?></th>
						<th scope="col" style="text-align:left;width:100px;vertical-align:middle;"><?php _e('Description',"wp-download_monitor"); ?></th>
		                <th scope="col" style="text-align:center;text-align:center"><?php _e('Member only',"wp-download_monitor"); ?></th>
						<th scope="col" style="text-align:center;vertical-align:middle"><?php _e('Action',"wp-download_monitor"); ?></th>
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
							
							$path = get_bloginfo('wpurl')."/wp-content/uploads/";
							$file = str_replace($path, "", $d->filename);
							$links = explode("/",$file);
							$file = end($links);
							echo ('<tr class="alternate">');
							echo '<td style="text-align:center;vertical-align:middle">'.$d->id.'</td>
							<td style="vertical-align:middle">'.$d->title.'</td>
							<td style="vertical-align:middle">'.$file.'</td>
							<td style="text-align:center;vertical-align:middle">';
							if ($d->category_id=="" || $d->category_id==0) echo "N/A"; else {
								$c = $wpdb->get_row("SELECT * FROM $wp_dlm_db_cats where id=".$d->category_id." LIMIT 1;");
								$chain = $c->name;
								while ($c->parent>0) {
									$c = $wpdb->get_row("SELECT * FROM $wp_dlm_db_cats where id=".$c->parent." LIMIT 1;");
									$chain = $c->name.' &mdash; '.$chain;
								}
								echo $d->category_id." - ".$chain;
							}						
							echo '</td>';
							
							if (strlen($d->file_description) > 25)
	      						$file_description = substr(htmlspecialchars($d->file_description), 0, strrpos(substr(htmlspecialchars($d->file_description), 0, 25), ' ')) . ' [...]';
	      					else $file_description = htmlspecialchars($d->file_description);
	      
							echo '<td style="text-align:left;vertical-align:middle">'.nl2br($file_description).'</td>
							<td style="text-align:center;vertical-align:middle">';
							if ($d->members) echo __('Yes',"wp-download_monitor"); else echo __('No',"wp-download_monitor");
							echo '</td>
							<td style="text-align:center;vertical-align:middle"><a href="#" style="display:block" class="button insertdownload" id="download-'.$d->id.'">Insert</a></td>';
							
						}
						echo '</tbody>';
					} 
			?>			
			</table>
	        <div class="tablenav">
	        	<div style="float:left" class="tablenav-pages">
					<?php
						// FIXED: 2 - Moved around to make more sense
						if ($total_pages>1)  { // FIXED: 1.6 - Stops it displaying when un-needed
						
							// Build Page Number Hyperlinks 
							if($page > 1){ 
								$prev = ($page - 1); 
								echo "<a href=\"?tab=downloads&amp;p=$prev&amp;sort=$sort\">&laquo; ".__('Previous',"wp-download_monitor")."</a> "; 
							} else echo "<span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span>";
	
							for($i = 1; $i <= $total_pages; $i++){ 
								if(($page) == $i){ 
									echo " <span class='page-numbers current'>$i</span> "; 
									} else { 
										echo " <a href=\"?tab=downloads&amp;p=$i&amp;sort=$sort\">$i</a> "; 
								} 
							} 
	
							// Build Next Link 
							if($page < $total_pages){ 
								$next = ($page + 1); 
								echo "<a href=\"?tab=downloads&amp;p=$next&amp;sort=$sort\">".__('Next',"wp-download_monitor")." &raquo;</a>"; 
							} else echo "<span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span>";
							
						}
					?>	
	            </div>        	
	        </div>
	        <br style="clear: both; margin-bottom:1px; height:2px; line-height:2px;" />
	    </div>
		<script type="text/javascript">
			/* <![CDATA[ */
			jQuery('.insertdownload').click(function(){
				var win = window.dialogArguments || opener || parent || top;
				var did = jQuery(this).attr('id');
				did=did.replace('download-', '');
				if (jQuery('#format').val()>0) win.send_to_editor('[download id="' + did + '" format="' + jQuery('#format').val() + '"]');
				else win.send_to_editor('[download id="' + did + '"]');
			});
			/* ]]> */
		</script>

		<?php
			
		break;
	}
	?>
</body>
</html>
