<?php
/*
Plugin Name: Wordpress Download Monitor
Plugin URI: http://wordpress.org/extend/plugins/download-monitor/
Description: Manage downloads on your site, view and show hits, and output in posts. If you are upgrading Download Monitor it is a good idea to <strong>back-up your database</strong> just in case.
Version: 3.1.5
Author: Mike Jolley
Author URI: http://blue-anvil.com
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

################################################################################
// Vars and version
################################################################################

// Pre 2.6 compatibility (BY Stephen Rider)
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	if ( defined( 'WP_SITEURL' ) ) define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
	else define( 'WP_CONTENT_URL', get_option( 'url' ) . '/wp-content' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

$dlm_build="B20090622";
$wp_dlm_root = WP_PLUGIN_URL."/download-monitor/";
global $table_prefix;
$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
$wp_dlm_db_cats = $table_prefix."DLM_CATS";
$wp_dlm_db_formats = $table_prefix."DLM_FORMATS";
$wp_dlm_db_stats = $table_prefix."DLM_STATS";
$wp_dlm_db_log = $table_prefix."DLM_LOG";
$wp_dlm_db_meta = $table_prefix."DLM_META";

$def_format = get_option('wp_dlm_default_format');
$dlm_url = get_option('wp_dlm_url');
$downloadtype = get_option('wp_dlm_type');
if (empty($dlm_url)) 
	$downloadurl = $wp_dlm_root.'download.php?id=';
else
	$downloadurl = get_bloginfo('url').'/'.$dlm_url;
	//$downloadurl = get_bloginfo('wpurl').'/'.$dlm_url;
	/* Changed to url so that wordpress in a sub dir works with custom urls */	
	

load_plugin_textdomain('wp-download_monitor', WP_PLUGIN_URL.'/download-monitor/languages/', 'download-monitor/languages/');

################################################################################
// ADD MEDIA BUTTONS AND FORMS
################################################################################
       
function wp_dlm_add_media_button() {
	global $wp_dlm_root;
	echo '<a href="'.WP_PLUGIN_URL.'/download-monitor/uploader.php?tab=add&TB_iframe=true&amp;height=500&amp;width=640" class="thickbox" title="'.__('Add Download','wp-download_monitor').'"><img src="'.$wp_dlm_root.'media-button-download.gif" alt="'.__('Add Download','wp-download_monitor').'"></a>';
}


################################################################################
// HANDLE UPDATES
################################################################################

function wp_dlm_update() {

	global $dlm_build;

	add_option('wp_dlm_build', $dlm_build, 'Version of DLM plugin', 'no');

	if ( get_option('wp_dlm_build') != $dlm_build ) {
	
		// Init again
		wp_dlm_init();	
			
		// Update the build
		update_option('wp_dlm_build', $dlm_build);
		
	}
	
}
																					
################################################################################
// Set up menus within the wordpress admin sections
################################################################################
function wp_dlm_menu() { 
	global $wp_dlm_root;		
// Add a new top-level menu:
    add_menu_page(__('Downloads','wp-download_monitor'), __('Downloads','wp-download_monitor'), 6, __FILE__ , 'wp_dlm_admin', $wp_dlm_root.'/img/menu_icon.png');
// Add submenus to the custom top-level menu:
	add_submenu_page(__FILE__, __('Edit','wp-download_monitor'),  __('Edit','wp-download_monitor') , 6, __FILE__ , 'wp_dlm_admin');
	add_submenu_page(__FILE__, __('Add New','wp-download_monitor') , __('Add New','wp-download_monitor') , 6, 'dlm_addnew', 'dlm_addnew');
	add_submenu_page(__FILE__, __('Add Existing','wp-download_monitor') , __('Add Existing','wp-download_monitor') , 6, 'dlm_addexisting', 'dlm_addexisting');
    add_submenu_page(__FILE__, __('Configuration','wp-download_monitor') , __('Configuration','wp-download_monitor') , 6, 'dlm_config', 'wp_dlm_config');
    add_submenu_page(__FILE__, __('Log','wp-download_monitor') , __('Log','wp-download_monitor') , 6, 'dlm_log', 'wp_dlm_log');
}
add_action('admin_menu', 'wp_dlm_menu');


################################################################################
// ADMIN HEADER
################################################################################
function wp_dlm_head() {
	global $wp_db_version, $wp_dlm_root;
	// Provide css based on wordpress version.
	if ($wp_db_version < 9872) {
		// 2.5 + 2.6 with new interface
		echo '<link rel="stylesheet" type="text/css" href="'.$wp_dlm_root.'css/wp-download_monitor25.css" />';
	} else {
		// 2.7
		echo '<link rel="stylesheet" type="text/css" href="'.$wp_dlm_root.'css/wp-download_monitor27.css" />';
	}
	if ($_GET['activate'] && $_GET['activate']==true) {
		wp_dlm_init();
	}
	
	if (
		$_REQUEST['page']=='dlm_addnew' ||
		$_REQUEST['page']=='dlm_addexisting' ||
		$_REQUEST['page']=='download-monitor/wp-download_monitor.php' ||
		$_REQUEST['page']=='download-monitor/wp-download_monitor.php'
	) {
	?>
	<link rel="stylesheet" type="text/css" href="<?php echo $wp_dlm_root; ?>js/jqueryFileTree/jqueryFileTree.css" />
	<script type="text/javascript" src="<?php echo $wp_dlm_root; ?>js/jqueryFileTree/jqueryFileTree.js"></script>
	<script type="text/javascript">
	/* <![CDATA[ */
		
		jQuery.noConflict();
		(function($) { 
		  $(function() {
		  
		    $('#file_browser').hide().fileTree({
		      root: '<?php echo ABSPATH; ?>',
		      script: '<?php echo $wp_dlm_root; ?>js/jqueryFileTree/connectors/jqueryFileTree.php',
		    }, function(file) {
		        var path = file.replace('<?php echo ABSPATH; ?>', '<?php bloginfo('wpurl'); ?>/');
		        $('#filename, #dlfilename').val(path);
		        $('#file_browser').slideToggle();
		    });
		    
		    $('a.browsefiles').show().click(function(){
		    	$('#file_browser').slideToggle();
		    });			 
		  										  	
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
	<?php
	}
}
add_action('admin_head', 'wp_dlm_head');

################################################################################
// Set up database
################################################################################
function wp_dlm_init() {

	add_option('wp_dlm_url', '', 'URL for download', 'no');	
	add_option('wp_dlm_type', 'ID', 'wp_dlm_type', 'no');
	add_option('wp_dlm_default_format', '0', 'wp_dlm_default_format', 'no');
	add_option('wp_dlm_does_not_exist','','no');
	add_option('wp_dlm_image_url',WP_PLUGIN_URL."/download-monitor/img/download.gif",'no');
	
 	global $wp_dlm_db,$wp_dlm_db_cats,$wp_dlm_db_formats,$wpdb,$wp_dlm_db_stats,$wp_dlm_db_log,$wp_dlm_db_meta;
 	
 	// Get Collation
	$collate = "";
	if($wpdb->supports_collation()) {
		if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
	} 
	
	// Create tables 	
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT, 
			`title`   	VARCHAR (200) NOT NULL ,
			`filename`  LONGTEXT  NOT NULL ,
			`file_description`  LONGTEXT  NULL ,
			`dlversion` VARCHAR (200) NOT NULL ,
			`postDate`  DATETIME  NOT NULL ,
			`hits`   	INT (12) UNSIGNED NOT NULL ,
			`user`   	VARCHAR (200) NOT NULL ,
			`category_id` INT (12) NULL,
			`members` INT (1) NULL,
			`mirrors` LONGTEXT NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_cats." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT, 
			`name`   	LONGTEXT  NOT NULL ,
			`parent`  	INT (12) UNSIGNED NOT NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_formats." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT, 
			`name`   	VARCHAR (250)  NOT NULL ,
			`format`  	LONGTEXT NOT NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_stats." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`download_id` INT UNSIGNED NOT NULL,
			`date`   	DATE  NOT NULL ,
			`hits`  	INT (12) UNSIGNED NOT NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_log." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`download_id` INT UNSIGNED NOT NULL,
			`user_id` INT UNSIGNED NOT NULL,
			`date`   	DATETIME  NULL ,
			`ip_address`  	VARCHAR (200) NULL ,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_meta." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`meta_name` 	LONGTEXT  NOT NULL ,
			`meta_value`   	LONGTEXT  NOT NULL ,
			`download_id`  	INT (12) UNSIGNED NOT NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);

	$q = $wpdb->get_results("select * from $wp_dlm_db;");
	if ( empty( $q ) ) {
		$wpdb->query("TRUNCATE table $wp_dlm_db");
	}
	
    return;
}

################################################################################
// Reinstall function
################################################################################

function wp_dlm_reinstall() {
	global $wpdb, $wp_dlm_db, $wp_dlm_db_cats;
	// GET OLD DATA
	$wpdb->show_errors;
	$query = sprintf("SELECT * from %s;",
		$wpdb->escape( $wp_dlm_db ));
	$result_d = $wpdb->get_results($query);
	if($result_d && $wpdb->num_rows>0) {
		$values="";
		foreach($result_d as $d) {
			$id=$d->id;
			$title=$d->title;
			$filename=$d->filename;
			$dlversion=$d->dlversion;
			$postDate=$d->postDate;
			$hits=$d->hits;
			$user=$d->user;
			$members=$d->members;
			$category_id=$d->category_id;	
			$mirrors=$d->mirrors;
			$file_description=$d->file_description;						
			$values.='("'.$id.'","'.$title.'","'.$filename.'","'.$dlversion.'","'.$postDate.'","'.$hits.'","'.$user.'","'.$members.'","'.$category_id.'","'.$mirrors.'","'.$file_description.'"),';
		}
		$values = substr_replace($values,"",-1);
	}
	$query = sprintf("SELECT * from %s;",
		$wpdb->escape( $wp_dlm_db_cats ));
	$result_cats = $wpdb->get_results($query);
	if($result_cats && $wpdb->num_rows>0) {
		$values2="";
		foreach($result_cats as $d) {
			$id=$d->id;
			$name=$d->name;
			$parent=$d->parent;							
			$values2.='("'.$id.'","'.$name.'","'.$parent.'"),';
		}
		$values2 = substr_replace($values2,"",-1);
	}
	// DROP TABLES
	$sql = 'DROP TABLE IF EXISTS `'.$wp_dlm_db.'`';
	$wpdb->query($sql);
	$sql = 'DROP TABLE IF EXISTS `'.$wp_dlm_db_cats.'`';
	$wpdb->query($sql);
	wp_dlm_init();
	
	// Change Collation (we forgot to do this in older versions)
	if($wpdb->supports_collation()) {
		if(!empty($wpdb->charset)) $char_set = $wpdb->charset;
		if(!empty($wpdb->collate)) $collate = 'collate '.$wpdb->collate;
	} 	
	if ($char_set) {
		global $wp_dlm_db_formats,$wp_dlm_db_stats,$wp_dlm_db_log,$wp_dlm_db_meta;
		$wpdb->query('ALTER TABLE '.$wp_dlm_db_formats.' convert to character set '.$char_set.' '.$collate.';');
		$wpdb->query('ALTER TABLE '.$wp_dlm_db_stats.' convert to character set '.$char_set.' '.$collate.';');
		$wpdb->query('ALTER TABLE '.$wp_dlm_db_log.' convert to character set '.$char_set.' '.$collate.';');
		$wpdb->query('ALTER TABLE '.$wp_dlm_db_meta.' convert to character set '.$char_set.' '.$collate.';');
	}
	
	// ADD OLD DATA
	if (!empty($values)) {
		$query_ins = sprintf("INSERT INTO %s (id, title, filename, dlversion, postDate, hits, user, members, category_id, mirrors, file_description) VALUES %s;",
			$wpdb->escape( $wp_dlm_db ),
			$values);
		$wpdb->query($query_ins);
	}
	if (!empty($values2)) {
		$query_ins = sprintf("INSERT INTO %s (id, name, parent) VALUES %s;",
			$wpdb->escape( $wp_dlm_db_cats ),
			$values2);
		$wpdb->query($query_ins);
	}	
}

################################################################################
// MAGIC QUOTES - WORDPRESS DOES THIS BUT ADDS THE SLASHES BACK - I DONT WANT THEM!
################################################################################

if (!function_exists('wp_dlm_magic')) {
function wp_dlm_magic() { 
	if (!function_exists('stripit')) {
	function stripit($in) {
		if (!is_array($in)) $out = stripslashes($in); else $out = $in;
		return $out;
	}
	}
	//if (get_magic_quotes_gpc() || get_magic_quotes_runtime() ){ 
		$_GET = array_map('stripit', $_GET); 
		$_POST = array_map('stripit', $_POST);
		$_REQUEST = array_map('stripit', $_REQUEST); 
	//}
	return;
}
}

################################################################################
// INSERT BUTTON ON POST SCREEN
################################################################################

function wp_dlm_ins_button() {
	//set globals
	global $table_prefix,$wpdb,$wp_dlm_db,$wp_dlm_db_cats;
  	
  	if( strpos($_SERVER['REQUEST_URI'], 'post.php')
	|| strstr($_SERVER['PHP_SELF'], 'page-new.php')
	|| strstr($_SERVER['PHP_SELF'], 'page.php')
	|| strstr($_SERVER['PHP_SELF'], 'post-new.php') )
	{      	
		// select all downloads
		$query_select = sprintf("SELECT * FROM %s ORDER BY id;",
		$wpdb->escape($wp_dlm_db));
  	
  		$downloads = $wpdb->get_results($query_select);
		
		$js .= '<optgroup label="'.__("Show","wp-download_monitor").'">';
		$js .= '<option value=\"s\">'.__('All Downloads','wp-download_monitor').'</option>';
		$js .= '<option value=\"a\">'.__('Downloads and categories','wp-download_monitor').'</option>';
		$js .= '</optgroup>';
  	
  		if (!empty($downloads)) {
  			
			$js .= '<optgroup label="'.__("Downloads","wp-download_monitor").'">';
			foreach( $downloads as $d )
			{
				$js .= '<option value=\"d'.$d->id.'\">'.$d->id.' - '.$d->title.'</option>';
			}
			$js .= '</optgroup>';
			
			// select all cats
			$query_select_cats = sprintf("SELECT * FROM %s WHERE parent=0 ORDER BY id;",
				$wpdb->escape( $wp_dlm_db_cats ));	
			$cats = $wpdb->get_results($query_select_cats);
			
			if (!empty($cats)) {
				$js .= '<optgroup label="'.__("Categories","wp-download_monitor").'">';
				foreach ( $cats as $c ) {
					$js .= '<option value=\"'.$c->id.'\">'.$c->id.' - '.$c->name.'</option>';
					$js .= addslashes(get_option_children_cats($c->id, "$c->name &mdash; ", 0));
				}
				$js .= '</optgroup>';
			}
      	
			?>
				<script type="text/javascript">
                    <!--
					jQuery(function() {
						jQuery("#ed_toolbar,td.mceToolbar.first").append('<select style=\"width:120px;margin:3px 2px 2px;\" class=\"ed_button\" id=\"downloadMon\" size=\"1\" onChange=\"return wpdlmins(this);\"><option selected="selected" value=\"\"><?php _e('Downloads','wp-download_monitor'); ?></option><?php echo $js;?></select>');
					});
					function wpdlmins(ele) {
						try{
							if( ele != undefined && ele.value != '') {
								if (ele.value.substring(0,1)=='d') {
									edInsertContent(edCanvas, '[download id="'+ ele.value.substring(1) +'"]');
								} else if (ele.value.substring(0,1)=='s') {
									edInsertContent(edCanvas, '[#show_downloads]');
								} else if (ele.value.substring(0,1)=='a') {
									edInsertContent(edCanvas, '[#advanced_downloads]');
								} else {
									edInsertContent(edCanvas, '[download_cat#'+ ele.value +']');
								}
							}
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

################################################################################
// GET FORMAT FROM DB
################################################################################
function wp_dlm_get_custom_format($id) {
	global $wpdb,$wp_dlm_db_formats;
	$query_select = sprintf("SELECT format FROM %s WHERE id = '%s';",
		$wpdb->escape($wp_dlm_db_formats),
		$wpdb->escape($id));
	$format = $wpdb->get_row($query_select);
	return $format->format;	
}

################################################################################
// SHORTCODES
################################################################################
function wp_dlm_shortcode_download( $atts ) {

	extract(shortcode_atts(array(
		'id' => '0',
		'format' => '0',
		'autop' => false
	), $atts));
	
	$output = '';
	
	$cached_code = wp_cache_get('download_'.$id.'_'.$format);
	
	if($cached_code == false) {
	
		global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_formats,$wp_dlm_db_cats, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;
	
		if ($id>0) {
			// Handle Formats
			if (!$format && $def_format>0) {
				$format = wp_dlm_get_custom_format($def_format);
			} elseif ($format>0 && is_numeric($format) ) {
				$format = wp_dlm_get_custom_format($format);
			} else {
				// Format is set!
				$format = html_entity_decode($format);
			}	
			if (empty($format) || $format=='0') {
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").'", ""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits})</a>';	
				
			}
			
			// Get download info
			$d = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wp_dlm_db WHERE id = %s" , $id ) );		
					
			if ($d) {	
	            	
	        	switch ($downloadtype) {
						case ("Title") :
								$downloadlink = urlencode($d->title);
						break;
						case ("Filename") :
								$downloadlink = $d->filename;
								$links = explode("/",$downloadlink);
								$downloadlink = urlencode(end($links));
						break;
						default :
								$downloadlink = $d->id;
						break;
				}
				
				$format = str_replace('\\"',"'",$format);
						
				$fpatts = array('{url}', '{id}', '{version}', '{title}', '{size}', '{hits}', '{image_url}', '{description}', '{description-autop}', '{category}');
				$fsubs = array( $downloadurl.$downloadlink , $d->id, $d->dlversion , $d->title , wp_dlm_get_size($d->filename) , $d->hits , get_option('wp_dlm_image_url') , $d->file_description , wpautop($d->file_description) );
					
				// Category
				if ($d->category_id>0) {
					$c = $wpdb->get_row("SELECT name FROM $wp_dlm_db_cats where id=".$d->category_id." LIMIT 1;");
					$fsubs[]  = $c->name;
					preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
					$fpatts[] = $match[0];
					$fsubs[]  = $match[1].$c->name.$match[2];
				} else {
					$fsubs[]  = "";
					preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
					$fpatts[] = $match[0];
					$fsubs[]  = "";
				}
					
				// Hits (special) {hits, none, one, many)
				preg_match("/{hits,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				$fpatts[] = $match[0];
				if ( $d->hits == 1 ) 
				{
					$text = str_replace('%',$d->hits,$match[2]);
					$fsubs[]  = $text; 
				}
				elseif ( $d->hits > 1 ) 
				{
					$text = str_replace('%',$d->hits,$match[3]);
					$fsubs[]  = $text; 
				}
				else 
				{
					$text = str_replace('%',$d->hits,$match[1]);
					$fsubs[]  = $text; 
				}						
				
				// Version
				preg_match("/{version,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				$fpatts[] = $match[0];
				if ($d->dlversion) $fsubs[]  = $match[1].$d->dlversion.$match[2]; else $fsubs[]  = "";
				
				// Date
				preg_match("/{date,\s*\"([^\"]*?)\"}/", $format, $match);
				$fpatts[] = $match[0];
				if ($d->postDate) $fsubs[] = date_i18n($match[1],strtotime($d->postDate)); else $fsubs[]  = "";					
				
				// Other
				preg_match("/{description,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				$fpatts[] = $match[0];
				if ($d->file_description) $fsubs[]  = $match[1].$d->file_description.$match[2]; else $fsubs[]  = "";
				
				preg_match("/{description-autop,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				$fpatts[] = $match[0];
				if ($d->file_description) $fsubs[]  = $match[1].wpautop($d->file_description).$match[2]; else $fsubs[]  = "";
				
				// meta
				if (preg_match("/{meta-([^,]*?)}/", $format, $match)) {
					$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wp_dlm_db_meta WHERE download_id = %s" , $id ) );
					$meta_names = array();
					foreach($meta_data as $meta) {
						$fpatts[] = "{meta-".$meta->meta_name."}";
						$fsubs[] = $meta->meta_value;
						$fpatts[] = "{meta-autop-".$meta->meta_name."}";
						$fsubs[] = wpautop($meta->meta_value);
						$meta_names[] = $meta->meta_name;
					}
					// Blank Meta
					$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wp_dlm_db_meta WHERE meta_name NOT IN ( %s )" , implode(',', $meta_names) ) );
					foreach($meta_data as $meta) {
						$fpatts[] = "{meta-".$meta->meta_name."}";
						$fsubs[] = '';
						$fpatts[] = "{meta-autop-".$meta->meta_name."}";
						$fsubs[] = '';
					}
				}
				
				$output = str_replace( $fpatts , $fsubs , $format );
	   			
	   		} else $output = '[Download not found]';
		
		} else $output = '[Download id not defined]';
		
		wp_cache_set('download_'.$id.'_'.$format, $output);
	
	} else {
		$output = $cached_code;
	}
	
	if ($autop) return wpautop($output);
	
	return $output;

}
add_shortcode('download', 'wp_dlm_shortcode_download');

################################################################################
// LEGACY TAGS SUPPORT
################################################################################

function wp_dlm_parse_downloads($data) {
	
	if (substr_count($data,"[download#")) {		
	
		preg_match_all("/\[download#([0-9]+)#format=([0-9]+)\]/", $data, $matches, PREG_SET_ORDER);
		
		if ( sizeof( $matches ) > 0 ) foreach ($matches as $val) {
		
			$code = '[download id="'.$val[1].'" format="'.$val[2].'"]';
			$data = str_replace( $val[0] , $code , $data );
	   		
   		} // End foreach
   		
   		// Handle Non-formatted downloads
   		preg_match_all("/\[download#([0-9]+)/", $data, $matches, PREG_SET_ORDER);
		
		$patts = array();
		$subs = array();
			
		if ( sizeof( $matches ) > 0 ) foreach ($matches as $val) {
					
				$patts[] = "[download#" . $val[1] . "]";
				$subs[] = '[download id="'.$val[1].'"]';
				
				// No hit counter				
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").' ",""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title}</a>';
				
				$patts[] = "[download#" . $val[1] . "#nohits]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// URL only
				$format = '{url}';
				$patts[] = "[download#" . $val[1] . "#url]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// Description only
				$format = '{description}';	
				$patts[] = "[download#" . $val[1] . "#description]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// Description (autop) only
				$format = '{description-autop}';
				$patts[] = "[download#" . $val[1] . "#description_autop]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';	
				
				// Hits only
				$format = '{hits}';;
				$patts[] = "[download#" . $val[1] . "#hits]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// Image link	
				$format = '<a class="downloadlink dlimg" href="{url}" title="{version,"'.__("Version","wp-download_monitor").' ",""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" ><img src="{image_url}" alt="'.__("Download","wp-download_monitor").' {title} {version,"'.__("Version","wp-download_monitor").' ",""}" /></a>';				
				$patts[] = "[download#" . $val[1] . "#image]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// Regular download link WITH filesize
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").' ",""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits}) - {size}</a>';
				$patts[] = "[download#" . $val[1] . "#size]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
								
				// No hit counter + filesize
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").' ",""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({size})</a>';
				$patts[] = "[download#" . $val[1] . "#size#nohits]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
		
		} // End foreach
		
		$data = str_replace($patts, $subs, $data);
				
	} // End if [download# found

	
	
	
	
	global $wpdb, $wp_dlm_db, $wp_dlm_db_meta, $wp_dlm_db_cats, $downloadurl, $downloadtype;
	
	// Handle CATEGORIES
	if (substr_count($data,"[download_cat#")) {
		
		$patts = array();
		$subs = array();
		
		preg_match_all("/\[download_cat#([0-9]+)#format=([0-9]+)\]/", $data, $result, PREG_SET_ORDER);

		if ($result) foreach ($result as $val) {

			$format = wp_dlm_get_custom_format($val[2]);
			
			if ($format) {
			
				$format = str_replace('\\"',"'",$format);
			
				// Traverse through categories to get sub-cats
				$the_cats = array();
				$the_cats[] = $val[1];
				$the_cats = wp_dlm_get_sub_cats($the_cats);
			
				// Get downloads for category and sub-categories
				$query = "SELECT * FROM $wp_dlm_db WHERE category_id IN (".implode(',',$the_cats).") ORDER BY 'title'";
				$downloads = $wpdb->get_results($query);
				
				// GENERATE LIST
				$links = '<ul>';	
				
				if (!empty($downloads)) {
					
					foreach($downloads as $d) {
						
						switch ($downloadtype) {
							case ("Title") :
									$downloadlink = urlencode($d->title);
							break;
							case ("Filename") :
									$downloadlink = $d->filename;
									$link = explode("/",$downloadlink);
									$downloadlink = urlencode(end($link));
							break;
							default :
									$downloadlink = $d->id;
							break;
						}
						
						$fpatts = array('{url}', '{version}', '{title}', '{size}', '{hits}', '{image_url}', '{description}', '{description-autop}', '{category}');
						$fsubs = array( $downloadurl.$downloadlink , $d->dlversion , $d->title , wp_dlm_get_size($d->filename) , $d->hits , get_option('wp_dlm_image_url') , $d->file_description , wpautop($d->file_description) );
							
						// Category
						if ($d->category_id>0) {
							$c = $wpdb->get_row("SELECT name FROM $wp_dlm_db_cats where id=".$d->category_id." LIMIT 1;");
							$fsubs[]  = $c->name;
							preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
							$fpatts[] = $match[0];
							$fsubs[]  = $match[1].$c->name.$match[2];
						} else {
							$fsubs[]  = "";
							preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
							$fpatts[] = $match[0];
							$fsubs[]  = "";
						}
						
						// Hits (special) {hits, none, one, many)
						preg_match("/{hits,\s*\"([^\"]*?)\",\s*\"([^']*?)\",\s*\"([^']*?)\"}/", $format, $match);
						$fpatts[] = $match[0];
						if ( $d->hits == 1 ) 
						{
							$text = str_replace('%',$d->hits,$match[2]);
							$fsubs[]  = $text; 
						}
						elseif ( $d->hits > 1 ) 
						{
							$text = str_replace('%',$d->hits,$match[3]);
							$fsubs[]  = $text; 
						}
						else 
						{
							$text = str_replace('%',$d->hits,$match[1]);
							$fsubs[]  = $text; 
						}
						
						// Version
						preg_match("/{version,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
						$fpatts[] = $match[0];
						if ($d->dlversion) $fsubs[]  = $match[1].$d->dlversion.$match[2]; else $fsubs[]  = "";
						
						// Date
						preg_match("/{date,\s*\"([^\"]*?)\"}/", $format, $match);
						$fpatts[] = $match[0];
						if ($d->postDate) $fsubs[] = date_i18n($match[1],strtotime($d->postDate)); else $fsubs[]  = "";							
						
						// Other
						preg_match("/{description,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
						$fpatts[] = $match[0];
						if ($d->file_description) $fsubs[]  = $match[1].$d->file_description.$match[2]; else $fsubs[]  = "";
						
						preg_match("/{description-autop,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
						$fpatts[] = $match[0];
						if ($d->file_description) $fsubs[]  = $match[1].wpautop($d->file_description).$match[2]; else $fsubs[]  = "";
						
						// meta
						if (preg_match("/{meta-([^,]*?)}/", $format, $match)) {
							$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wp_dlm_db_meta WHERE download_id = %s" , $d->id ) );
							$meta_names = array();
							foreach($meta_data as $meta) {
								$fpatts[] = "{meta-".$meta->meta_name."}";
								$fsubs[] = $meta->meta_value;
								$fpatts[] = "{meta-autop-".$meta->meta_name."}";
								$fsubs[] = wpautop($meta->meta_value);
								$meta_names[] = $meta->meta_name;
							}
							// Blank Meta
							$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wp_dlm_db_meta WHERE meta_name NOT IN ( %s )" , implode(',', $meta_names) ) );
							foreach($meta_data as $meta) {
								$fpatts[] = "{meta-".$meta->meta_name."}";
								$fsubs[] = '';
								$fpatts[] = "{meta-autop-".$meta->meta_name."}";
								$fsubs[] = '';
							}
						}
						
						$code = str_replace( $fpatts , $fsubs , $format );	
						
						$links.= '<li>' . $code . '</li>';
						
					}
					
				} else {
					
					$links .= '<li>No Downloads Found</li>';
				
				}
				
				$links .= '</ul>';
				
				$patts[] = "[download_cat#" . $val[1] . "#format=" . $val[2] . "]";
				
				$subs[] = $links;		

	   		} // End if format
	   				
		} // End foreach
		
		$data = str_replace($patts, $subs, $data);
		
		$patts = array();
		$subs = array();
		
		preg_match_all("|\[download_cat#([0-9]+)\]|U",$data,$result,PREG_SET_ORDER);
		
		if ($result) foreach ($result as $val) {
		
			// Traverse through categories to get sub-cats
			$the_cats = array();
			$the_cats[] = $val[1];
			$the_cats = wp_dlm_get_sub_cats($the_cats);
		
			// Get downloads for category and sub-categories
			$query ="SELECT * FROM $wp_dlm_db WHERE category_id IN (".implode(',',$the_cats).") ORDER BY 'title'";
			$downloads = $wpdb->get_results($query);
			
			// GENERATE LIST
			$links = '<ul>';	
			if (!empty($downloads)) {
				foreach($downloads as $d) {
					switch ($downloadtype) {
						case ("Title") :
								$downloadlink = urlencode($d->title);
						break;
						case ("Filename") :
								$downloadlink = $d->filename;
								$link = explode("/",$downloadlink);
								$downloadlink = urlencode(end($link));
						break;
						default :
								$downloadlink = $d->id;
						break;
					}
					if (!empty($d->dlversion)) 				
						$links.= '<li><a href="'.$downloadurl.$downloadlink.'" title="'.__("Version","wp-download_monitor").' '.$d->dlversion.' '.__("downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a></li>';
					else $links.= '<li><a href="'.$downloadurl.$downloadlink.'" title="'.__("Downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a></li>';									
					
				}
			} else {
				$links .= '<li>No Downloads Found</li>';
			}
			$links .= '</ul>';
			$patts[] = "[download_cat#" . $val[1] . "]";
			$subs[] = $links;
			
		} // endforeach
		
		$data = str_replace($patts, $subs, $data);
	
	} // End if [download_cat# found
	
	return $data;		

} 

################################################################################
// File size formatting
################################################################################

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
// Get children categories
################################################################################

function get_option_children_cats($parent,$chain,$current,$showid=1) {
	global $wp_dlm_db_cats,$wpdb;
	$sql = sprintf("SELECT * FROM %s WHERE parent=%s ORDER BY id;",
		$wpdb->escape( $wp_dlm_db_cats ),
		$wpdb->escape( $parent ));	
	$scats = $wpdb->get_results($sql);
	if (!empty($scats)) {
		foreach ( $scats as $c ) {
			$string.= '<option ';
			if ($current==$c->id) $string.= 'selected="selected"';
			$string.= 'value="'.$c->id.'">';
			if ($showid==1) $string.= $c->id.' - ';
			$string.= $chain.$c->name.'</option>';
			$string.= get_option_children_cats($c->id, "$chain$c->name &mdash; ",$current,$showid);
		}
	}
	return $string;
}

if (!function_exists('wp_dlm_get_sub_cats')) {
	function wp_dlm_get_sub_cats($the_cats) {
		global $wpdb, $wp_dlm_db_cats;
		$a=sizeof($the_cats);
		$query = sprintf("SELECT id from %s WHERE parent IN (%s);",
			$wpdb->escape( $wp_dlm_db_cats ),
			$wpdb->escape( implode(",",$the_cats) ));
		$res = $wpdb->get_results($query);
		if ($res) {
			foreach($res as $r) {
				if (!in_array($r->id, $the_cats)) $the_cats[]=$r->id;
			}
		}
		$b=sizeof($the_cats);
		if ($a!=$b) {
			$sub_cats = wp_dlm_get_sub_cats($the_cats);
			$the_cats = $the_cats + $sub_cats;
		}
		return $the_cats;
	}
}

################################################################################
// ADMIN PAGE
################################################################################

function wp_dlm_admin()
{
	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_cats,$wp_dlm_db_formats,$wp_dlm_db_stats, $wp_dlm_db_meta, $wp_dlm_db_log;

	// turn off magic quotes
	wp_dlm_magic();
	
	wp_dlm_update();
	
	echo '<div class="download_monitor">';
	
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
				case "delete" :
					wp_cache_flush();
					$d = $wpdb->get_row($query_select_1);
					global $wp_db_version;
					$adminpage = 'admin.php';
					?>
						<div class="wrap">
							<div id="downloadadminicon" class="icon32"><br/></div>
							<h2><?php _e('Sure?',"wp-download_monitor"); ?></h2>
							<p><?php _e('Are you sure you want to delete',"wp-download_monitor"); ?> "<?php echo $d->title; ?>"<?php _e('? (If originally uploaded by this plugin, this will also remove the file from the server)',"wp-download_monitor"); ?> <a href="<?php echo get_bloginfo('wpurl'); ?>/wp-admin/<?php echo $adminpage; ?>?page=download-monitor/wp-download_monitor.php&amp;action=confirmed&amp;id=<?php echo $_GET['id']; ?>&amp;sort=<?php echo $_GET['sort']; ?>&amp;p=<?php echo $_GET['p']; ?>"><?php _e('[yes]',"wp-download_monitor"); ?></a> <a href="<?php echo get_bloginfo('wpurl'); ?>/wp-admin/<?php echo $adminpage; ?>?page=download-monitor/wp-download_monitor.php&amp;action=cancelled&amp;sort=<?php echo $_GET['sort']; ?>&amp;p=<?php echo $_GET['p']; ?>"><?php _e('[no]',"wp-download_monitor"); ?></a>
						</div>
					<?php					
				break;
				case "edit" :
					wp_cache_flush();
					if ( $_POST['sub'] ) {
						$title = $_POST['title'];
						$dlversion = $_POST['dlversion'];
						$dlhits = $_POST['dlhits'];
						$dlfilename =$_POST['dlfilename'];
						$members = (isset($_POST['memberonly'])) ? 1 : 0;
						$download_cat = $_POST['download_cat'];
						$mirrors = $_POST['mirrors'];
						$file_description = $_POST['file_description'];
						$custom_fields = $_POST['meta'];
						if ( $_POST['save'] )
						{
							//save and validate
							if (empty( $_POST['title'] )) $errors.='<div class="error">'.__('Required field: <strong>Title</strong> omitted',"wp-download_monitor").'</div>';
							if (empty( $_POST['dlfilename'] ) && empty($_FILES['upload']['tmp_name'])) $errors.='<div class="error">'.__('Required field: <strong>File URL</strong> omitted',"wp-download_monitor").'</div>';						
							if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
							if (!is_numeric($_POST['dlhits'] )) $errors.='<div class="error">'.__('Invalid <strong>hits</strong> entered',"wp-download_monitor").'</div>';
							$members = (isset($_POST['memberonly'])) ? 1 : 0;
							
							$removefile = (isset($_POST['removefile'])) ? 1 : 0;
								
							if (empty($errors)) {
									if (!empty($_FILES['upload']['tmp_name'])) {
																	
											$time = current_time('mysql');
											$overrides = array('test_form'=>false);
											
											// Remove old file
											if ($removefile){		
												$d = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wp_dlm_db WHERE id=%s;",$_GET['id'] ) );
												$file = $d->filename;
												if ( strstr ( $d->filename, "/uploads/" ) ) {
													
													$path = WP_CONTENT_URL."/uploads/";
													$file = str_replace( $path , "" , $d->filename);
													if(is_file(WP_CONTENT_DIR.'/uploads/'.$file)){
															chmod(WP_CONTENT_DIR.'/uploads/'.$file, 0777);  
															unlink(WP_CONTENT_DIR.'/uploads/'.$file);
													 }					    
												}										
											}
	
											$file = wp_handle_upload($_FILES['upload'], $overrides, $time);
	
											if ( !isset($file['error']) ) {
												$info = $file['url'];
												$filename = $file['url'];	
											} 
											else $errors = '<div class="error">'.$file['error'].'</div>';				
	
											// update download & file
											$query_update_file = sprintf("UPDATE %s SET title='%s', dlversion='%s', hits='%s', filename='%s', postDate='%s', user='%s',members='%s',category_id='%s', mirrors='%s', file_description='%s' WHERE id=%s;",
												$wpdb->escape( $wp_dlm_db ),
												$wpdb->escape( $_POST['title'] ),
												mysql_real_escape_string( $_POST['dlversion'] ),
												mysql_real_escape_string( $_POST['dlhits'] ),
												$wpdb->escape( $filename ),
												$wpdb->escape( $_POST['postDate'] ),
												$wpdb->escape( $_POST['user'] ),
												$wpdb->escape( $members ),
												$wpdb->escape( $_POST['download_cat'] ),
												$wpdb->escape( trim($_POST['mirrors']) ) ,
												$wpdb->escape( trim($_POST['file_description']) ) ,
												$wpdb->escape( $_GET['id'] ));
			
											$d = $wpdb->get_row($query_update_file);
											$show=true;
											
											// Process and save meta/custom fields
											$wpdb->query("DELETE FROM $wp_dlm_db_meta WHERE download_id = ".$_GET['id']."");
											$index = 1;
											$values = array();
											if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
											{
												if (trim($meta['key'])) {
													$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim($meta['key']))))).'", "'.$wpdb->escape($meta['value']).'", '.$_GET['id'].')';
													$index ++;
												}
											}
											if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
											
											echo '<div id="message" class="updated fade"><p><strong>'.__('Download edited Successfully',"wp-download_monitor").' - '.$info.'</strong></p></div>';
									} else {
											//not replacing file
											$query_update = sprintf("UPDATE %s SET title='%s', dlversion='%s', hits='%s', filename='%s',members='%s',category_id='%s', mirrors='%s', file_description='%s' WHERE id=%s;",
												$wpdb->escape( $wp_dlm_db ),
												$wpdb->escape( $_POST['title'] ),
												mysql_real_escape_string( $_POST['dlversion'] ),
												mysql_real_escape_string( $_POST['dlhits'] ),
												$wpdb->escape( $_POST['dlfilename'] ),
												$wpdb->escape( $members ),
												$wpdb->escape( $_POST['download_cat'] ),
												$wpdb->escape( trim($_POST['mirrors']) ) ,
												$wpdb->escape( trim($_POST['file_description']) ) ,
												$wpdb->escape( $_GET['id'] ));
											$d = $wpdb->get_row($query_update);
											$show=true;
											
											
											// Process and save meta/custom fields
											$wpdb->query("DELETE FROM $wp_dlm_db_meta WHERE download_id = ".$_GET['id']."");
											$index = 1;
											$values = array();
											if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
											{
												if (trim($meta['key'])) {
													$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim($meta['key']))))).'", "'.$wpdb->escape($meta['value']).'", '.$_GET['id'].')';
													$index ++;
												}
											}
											if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
				
				
											echo '<div id="message" class="updated fade"><p><strong>'.__('Download edited Successfully',"wp-download_monitor").'</strong></p></div>';
									}
							} 
							if (!empty($errors)) {
								echo $errors;								
							}
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
						$members = $d->members;
						$download_cat = $d->category_id;
						$mirrors =  $d->mirrors;
						$file_description = $d->file_description;
						$fields = $wpdb->get_results("SELECT * FROM $wp_dlm_db_meta WHERE download_id= ".$d->id."");
						$index=1;
						$custom_fields = array();
						if ($fields) foreach ($fields as $meta) 
						{
							$custom_fields[$index]['key'] = $meta->meta_name;
							$custom_fields[$index]['value'] = $meta->meta_value;
							$custom_fields[$index]['remove'] = 0;
							$index++;
						}
					}	

					if ($show==false) {
											
						$max_upload_size_text = '';
						
						if (function_exists('ini_get')) {
							$max_upload_size = min(let_to_num(ini_get('post_max_size')), let_to_num(ini_get('upload_max_filesize')));
							$max_upload_size_text = __(' (defined in php.ini)',"wp-download_monitor");
						}
						
						if (!$max_upload_size || $max_upload_size==0) {
							$max_upload_size = 8388608;
							$max_upload_size_text = '';
						}	
					
					?>
								<div class="wrap">
								<div id="downloadadminicon" class="icon32"><br/></div>
								<h2><?php _e('Edit Download Information',"wp-download_monitor"); ?></h2>
								<form enctype="multipart/form-data" action="?page=download-monitor/wp-download_monitor.php&amp;action=edit&amp;id=<?php echo $_GET['id']; ?>" method="post" id="wp_dlm_add" name="edit_download" class="form-table" cellpadding="0" cellspacing="0"> 

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
											<tr valign="middle">
	                                            <th scope="row"><strong><?php _e('Description',"wp-download_monitor"); ?>: </strong></th> 
	                                            <td><textarea name="file_description" cols="50" rows="6"><?php echo $file_description; ?></textarea></td> 
	                                        </tr>
											<tr valign="top">
												<th scope="row"><strong><?php _e('Change hit count',"wp-download_monitor"); ?>: </strong></th> 
												<td>
													<input type="text" style="width:100px;" class="cleardefault" value="<?php echo $dlhits; ?>" name="dlhits" id="dlhits" maxlength="50" />
												</td> 
											</tr>
                                        <tr valign="top">												
                                            <th scope="row"><strong><?php _e('Category',"wp-download_monitor"); ?></strong></th> 
                                            <td>
                                            <select name="download_cat">
                                            	<option value="0"><?php _e('N/A',"wp-download_monitor"); ?></option>
												<?php
                                                    $query_select_cats = sprintf("SELECT * FROM %s WHERE parent=0 ORDER BY id;",
                                                        $wpdb->escape( $wp_dlm_db_cats ));	
                                                    $cats = $wpdb->get_results($query_select_cats);
													
                                                    if (!empty($cats)) {
                                                        foreach ( $cats as $c ) {
                                                            echo '<option ';
															if ($download_cat==$c->id) echo 'selected="selected"';
															echo 'value="'.$c->id.'">'.$c->id.' - '.$c->name.'</option>';
                                                            echo get_option_children_cats($c->id, "$c->name &mdash; ", $download_cat);
                                                        }
                                                    } 
                                                ?>
                                            </select><br /><span class="setting-description"><?php _e('Categories are optional and allow you to group and organise similar downloads.',"wp-download_monitor"); ?></span></td>
                                        </tr>  
                                            <tr valign="top">												
                                                <th scope="row"><strong><?php _e('Member only file?',"wp-download_monitor"); ?></strong></th> 
                                                <td><input type="checkbox" name="memberonly" style="vertical-align:top" <?php if ($members==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, only logged in users will be able to access the file via a download link. It is a good idea to give the file a name which cannot be easily guessed and accessed directly. BONUS: Add a meta key called min-level to set the minimum user level needed to download the file.',"wp-download_monitor"); ?></span></td>
                                            </tr> 
											<tr valign="top">
												<th scope="row"><strong><?php _e('File URL (required)',"wp-download_monitor"); ?>: </strong></th> 
												<td>
													<input type="text" style="width:320px;" class="cleardefault" value="<?php echo $dlfilename; ?>" name="dlfilename" id="dlfilename" /> <a class="browsefiles" style="display:none" href="#"><?php _e('Toggle File Browser',"wp-download_monitor"); ?></a><br /><span class="setting-description"><?php _e('Note: changes to the file url will only work if not uploading a new file below.',"wp-download_monitor"); ?></span> 
													<div id="file_browser"></div>
												</td> 
											</tr>
											<tr valign="top">												
												<th scope="row"><strong><?php _e('Download Mirrors',"wp-download_monitor"); ?></strong></th> 
												<td><textarea name="mirrors" cols="50" rows="2"><?php echo $mirrors; ?></textarea><br /><span class="setting-description"><?php _e('Optionally list the url\'s of any mirrors here (1 per line). Download monitor will randomly pick one of these mirrors when serving the download.',"wp-download_monitor"); ?></span></td>
											</tr>
										</table>
																
										<h3><?php _e('Upload a new file',"wp-download_monitor"); ?></h3>
										<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_upload_size; ?>" />										
										<p><?php _e('Here you can upload/re-upload the file from your computer.',"wp-download_monitor"); ?></p>
										
										<table class="optiontable niceblue">  
											<tr valign="top">
												<th scope="row"><strong><?php _e('Select a file...',"wp-download_monitor"); ?></strong></th> 
												<td><input type="file" name="upload" style="width:320px;" /><br /><span class="setting-description"><?php _e('Max. filesize',"wp-download_monitor"); echo $max_upload_size_text; ?> = <?php echo $max_upload_size; ?> <?php _e('bytes',"wp-download_monitor"); ?>.</span></td>
							                </tr> 
											<tr valign="top">												
                                                <th scope="row"><strong><?php _e('Remove old file?',"wp-download_monitor"); ?></strong></th> 
                                                <td><input type="checkbox" name="removefile" style="vertical-align:top" <?php if ($removefile==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, the old file will be deleted from the server (if it exists) before uploading the new file.',"wp-download_monitor"); ?></span></td>
                                            </tr>											
										</table>
									<input type="hidden" name="sort" value="<?php echo $_REQUEST['sort']; ?>" />
									<input type="hidden" name="p" value="<?php echo $_REQUEST['p']; ?>" />
									<input type="hidden" name="sub" value="1" />
									<input type="hidden" name="postDate" value="<?php echo date_i18n(__('Y-m-d H:i:s',"wp-download_monitor")) ;?>" />
									<?php 
										global $userdata;
										get_currentuserinfo();										
										echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
									?>
									<hr/>
						            <h3><?php _e('Custom fields',"wp-download_monitor"); ?></h3>
						            <p><?php _e('Custom fields can be used to add extra metadata to a download. Leave blank to add none. Name should be lower case with no spaces (changed automatically, e.g. <code>Some Name</code> will become <code>some-name</code>.',"wp-download_monitor"); ?></p>
									<table style="width:80%">
										<thead>
											<tr>
												<th class="left"><?php _e('Name',"wp-download_monitor"); ?></th>
												<th><?php _e('Value',"wp-download_monitor"); ?></th>
											</tr>			
										</thead>
										<tbody id="customfield_list">
											<?php
											$index = 1;
											if ($custom_fields) foreach ($custom_fields as $meta) 
											{
												if (!$meta['remove']) {
													if (trim($meta['key'])) {
														echo '<tr class="alternate">
															<td class="left" style="vertical-align:top;">
																<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="'.strtolower((str_replace(' ','-',trim($meta['key'])))).'" type="text" style="width:95%">
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
											?>
											<tr id="addmetarow">
												<td colspan="2" class="submit"><input id="addmetasub" name="addmeta" value="<?php _e('Add Custom Field',"wp-download_monitor"); ?>" type="submit"></td>
											</tr>
										</tbody>
									</table>
									<hr />									
									<p class="submit"><input type="submit" class="btn button-primary" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" /></p>
								</form>
								</div>													
							<?php	
					}
				
				break;
				case "confirmed" :
					wp_cache_flush();
					//load values
					$d = $wpdb->get_row($query_select_1);
					$file = $d->filename;
					if ( strstr ( $d->filename, "/uploads/" ) ) {
						
						$path = WP_CONTENT_URL."/uploads/";
						$file = str_replace( $path , "" , $d->filename);
						if(is_file(WP_CONTENT_DIR.'/uploads/'.$file)){
								chmod(WP_CONTENT_DIR.'/uploads/'.$file, 0777);  
								unlink(WP_CONTENT_DIR.'/uploads/'.$file);
						 }					    
					}
					$query_delete = sprintf("DELETE FROM $wp_dlm_db WHERE id=%s;",
						$wpdb->escape( $_GET['id'] ));
					$wpdb->query($query_delete);
					
					$query_delete = sprintf("DELETE FROM $wp_dlm_db_stats WHERE download_id=%s;",
						$wpdb->escape( $_GET['id'] ));
					$wpdb->query($query_delete);
					
					$query_delete = sprintf("DELETE FROM $wp_dlm_db_log WHERE download_id=%s;",
						$wpdb->escape( $_GET['id'] ));
					$wpdb->query($query_delete);
					
					$query_delete = sprintf("DELETE FROM $wp_dlm_db_meta WHERE download_id=%s;",
						$wpdb->escape( $_GET['id'] ));
					$wpdb->query($query_delete);					

					echo '<div id="message" class="updated fade"><p><strong>'.__('Download deleted Successfully',"wp-download_monitor").'</strong></p></div>';
					
					// Truncate table if empty
					$q=$wpdb->get_results("select * from $wp_dlm_db;");
					if ( empty( $q ) ) {
						$wpdb->query("TRUNCATE table $wp_dlm_db");
					}
					$show=true;
				break;
				case "cancelled" :
					$show=true;
				break;
		}
	}
	//show downloads page
	if ( ($show==true) || ( empty($action) ) )
	{
	
	global $downloadurl, $downloadtype;
	
	?>
	<div class="wrap alternate">    
    	<div id="downloadadminicon" class="icon32"><br/></div>
        <h2><?php _e('Edit Downloads',"wp-download_monitor"); ?></h2>
		<form id="downloads-filter" action="admin.php?page=download-monitor/wp-download_monitor.php" method="POST">
			<p class="search-box">
				<label class="hidden" for="post-search-input"><?php _e('Search Downloads:',"wp-download_monitor"); ?></label>
				<input class="search-input" id="post-search-input" name="search_downloads" value="<?php echo $_REQUEST['search_downloads']; ?>" type="text" />
				<input value="<?php _e('Search Downloads',"wp-download_monitor"); ?>" class="button" type="submit" />
			</p>
		</form>
        <br class="" style="clear: both;"/>
        <table class="widefat" style="margin-top:4px"> 
			<thead>
				<tr>
				<th scope="col" style="text-align:center"><a href="?page=download-monitor/wp-download_monitor.php&amp;sort=id"><?php _e('ID',"wp-download_monitor"); ?></a></th>
				<th scope="col"><a href="?page=download-monitor/wp-download_monitor.php&amp;sort=title"><?php _e('Title',"wp-download_monitor"); ?></a></th>
				<th scope="col"><a href="?page=download-monitor/wp-download_monitor.php&amp;sort=filename"><?php _e('File',"wp-download_monitor"); ?></a></th>
                <th scope="col" style="text-align:center"><?php _e('Category',"wp-download_monitor"); ?></th>
				<th scope="col" style="text-align:center"><?php _e('Version',"wp-download_monitor"); ?></th>
				<th scope="col" style="text-align:left;width:150px;"><?php _e('Description',"wp-download_monitor"); ?></th>
                <th scope="col" style="text-align:center"><?php _e('Member only',"wp-download_monitor"); ?></th>
				<th scope="col"><a href="?page=download-monitor/wp-download_monitor.php&amp;sort=postDate"><?php _e('Posted',"wp-download_monitor"); ?></a></th>
				<th scope="col" style="text-align:center"><?php _e('Custom fields',"wp-download_monitor"); ?></th>
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
				
				// Search
				if(!isset($_REQUEST['search_downloads'])){ 
					$search = ""; 
				} else { 
					$search = " WHERE (title LIKE '%".$wpdb->escape($_REQUEST['search_downloads'])."%' OR filename LIKE '%".$wpdb->escape($_REQUEST['search_downloads'])."%') ";
				}
				
				// Sort column
				$sort = "title";
				$sort_ex = '';
				if ($_REQUEST['sort'] && ($_REQUEST['sort']=="id" || $_REQUEST['sort']=="filename" || $_REQUEST['sort']=="postDate")) $sort = $_REQUEST['sort'];
				
				if ($_REQUEST['sort']=="id") $sort_ex =' ASC';
				
				$total_results = sprintf("SELECT COUNT(id) FROM %s %s;",
					$wpdb->escape($wp_dlm_db), $search );
					
				// Figure out the limit for the query based on the current page number. 
				$from = (($page * 20) - 20); 
			
				$paged_select = sprintf("SELECT $wp_dlm_db.*, $wp_dlm_db_cats.parent, $wp_dlm_db_cats.name FROM $wp_dlm_db  
				LEFT JOIN $wp_dlm_db_cats ON $wp_dlm_db.category_id = $wp_dlm_db_cats.id 
				%s
				ORDER BY %s LIMIT %s,20;",
					$search,
					$wpdb->escape( $sort.$sort_ex ),
					$wpdb->escape( $from ));
					
				$download = $wpdb->get_results($paged_select);
				$total = $wpdb->get_var($total_results);
			
				// Figure out the total number of pages. Always round up using ceil() 
				$total_pages = ceil($total / 20);
			
				if (!empty($download)) {
					echo '<tbody id="the-list">';
					foreach ( $download as $d ) {
						$date = date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->postDate));
						
						$path = WP_CONTENT_URL."/uploads/";
						$file = str_replace($path, "", $d->filename);
						$links = explode("/",$file);
						$file = end($links);
						echo ('<tr class="alternate">');
						echo '<td style="text-align:center">'.$d->id.'</td>
						<td>'.$d->title.'</td>
						<td>'.$file.'</td>
						<td style="text-align:center">';
						if ($d->category_id=="" || $d->category_id==0) _e('N/A',"wp-download_monitor"); else {
							//$c = $wpdb->get_row("SELECT * FROM $wp_dlm_db_cats where id=".$d->category_id." LIMIT 1;");
							//$chain = $c->name;
							$chain = $d->name;
							$c = $d->parent;
							while ($c>0) {
								$c = $wpdb->get_row("SELECT * FROM $wp_dlm_db_cats where id=".$c->parent." LIMIT 1;");
								$chain = $d->name.' &mdash; '.$chain;
							}
							echo $d->category_id." - ".$chain;
						}						
						echo '</td>
						<td style="text-align:center">'.$d->dlversion.'</td>';
						
						if (strlen($d->file_description) > 50)
      						$file_description = substr(htmlspecialchars($d->file_description), 0, strrpos(substr(htmlspecialchars($d->file_description), 0, 50), ' ')) . ' [...]';
      					else $file_description = htmlspecialchars($d->file_description);
      
						echo '<td style="text-align:left">'.nl2br($file_description).'</td>
						<td style="text-align:center">';
						if ($d->members) echo __('Yes',"wp-download_monitor"); else echo __('No',"wp-download_monitor");
						echo '</td>
						<td>'.$date.' '.__('by',"wp-download_monitor").' '.$d->user.'</td>
						<td style="text-align:center">';
						echo $wpdb->get_var('SELECT COUNT(id) FROM '.$wp_dlm_db_meta.' WHERE download_id = '.$d->id.'');
						echo '</td>
						<td style="text-align:center">'.$d->hits.'</td>
						<td><a href="?page=download-monitor/wp-download_monitor.php&amp;action=edit&amp;id='.$d->id.'&amp;sort='.$sort.'&amp;p='.$page.'"><img src="'.WP_CONTENT_URL.'/plugins/download-monitor/img/edit.png" alt="Edit" title="Edit" /></a> <a href="?page=download-monitor/wp-download_monitor.php&amp;action=delete&amp;id='.$d->id.'&amp;sort='.$sort.'&amp;p='.$page.'"><img src="'.WP_CONTENT_URL.'/plugins/download-monitor/img/cross.png" alt="Delete" title="Delete" /></a></td>';
						
					}
					echo '</tbody>';
				} else echo '<tr><th colspan="11">'.__('No downloads found.',"wp-download_monitor").'</th></tr>'; // FIXED: 1.6 - Colspan changed
		?>			
		</table>

        <div class="tablenav">
        	<div style="float:left" class="tablenav-pages">
				<?php
					if ($total_pages>1) {
					
						$arr_params = array (
							'sort' => $sort,
							'page' => 'download-monitor/wp-download_monitor.php',
							'search_downloads' => $_REQUEST['search_downloads'],
							'p' => "%#%"
						);
					
						echo paginate_links( array(
							'base' => add_query_arg( $arr_params ),
							'prev_text' => __('&laquo; Previous'),
							'next_text' => __('Next &raquo;'),
							'total' => $total_pages,
							'current' => $page,
							'end_size' => 1,
							'mid_size' => 5,
						));
					}
				?>	
            </div>        	
        </div>
        <br style="clear: both; margin-bottom:1px; height:2px; line-height:2px;" />
    </div>
    <hr />
    <div class="about">	    
	    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right">
	        <input type="hidden" name="cmd" value="_s-xclick" />
	        <input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" />
	        <img alt="" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1" />
	        <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHmAYJKoZIhvcNAQcEoIIHiTCCB4UCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYBBcwapPTXpPF47IaRDJNW7rzSb7LQBCAUTzQ3JyVbyL/Lvfk8s1R3tpnCc+0KWkIsGa4Hml9sz77zshMIsQZveo6/wniQgfK100n9ks03KXPblXYFn4OgnTW1C9y36f2kAw1GCK7uCHk51M1ouPXcOdpHijkTlYhYw2f7o8m6vSTELMAkGBSsOAwIaBQAwggEUBgkqhkiG9w0BBwEwFAYIKoZIhvcNAwcECCwkOMnfKDIugIHwNoDAhA1OzWclNhaPBY1bq0weaJNCRs7Pg/Z1QMiw9+w+yBy0H54ahFdkJ4IJcFYPAGoFi+npTsuPd5j9GMsr52RzRtNQjdhv6UqnMDWBJuYQdJ4/iEoRmUjpIS2CUyq5GIQwb2nTkEu1ZpP5cLCaudOVZS8W7nJzHzwJmk58A2SYnKCchwpHsZUQfdXJTaXg14I55DyHV3Rg+7P53zCnHfNrsAkw8aNNZLKz0B1Xiv8JFOYR2dBOPMRGpofmxdO/UDjZQjvqyxr1Hggm8To3VKZhrjoss8vs4NrJ3/Swg6fV7S1x9Fft5e2PQ3JXZI0/oIIDhzCCA4MwggLsoAMCAQICAQAwDQYJKoZIhvcNAQEFBQAwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tMB4XDTA0MDIxMzEwMTMxNVoXDTM1MDIxMzEwMTMxNVowgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDBR07d/ETMS1ycjtkpkvjXZe9k+6CieLuLsPumsJ7QC1odNz3sJiCbs2wC0nLE0uLGaEtXynIgRqIddYCHx88pb5HTXv4SZeuv0Rqq4+axW9PLAAATU8w04qqjaSXgbGLP3NmohqM6bV9kZZwZLR/klDaQGo1u9uDb9lr4Yn+rBQIDAQABo4HuMIHrMB0GA1UdDgQWBBSWn3y7xm8XvVk/UtcKG+wQ1mSUazCBuwYDVR0jBIGzMIGwgBSWn3y7xm8XvVk/UtcKG+wQ1mSUa6GBlKSBkTCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb22CAQAwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOBgQCBXzpWmoBa5e9fo6ujionW1hUhPkOBakTr3YCDjbYfvJEiv/2P+IobhOGJr85+XHhN0v4gUkEDI8r2/rNk1m0GA8HKddvTjyGw/XqXa+LST            lDYkqI8OwR8GEYj4efEtcRpRYBxV8KxAW93YDWzFGvruKnnLbDAF6VR5w/cCMn5hzGCAZowggGWAgEBMIGUMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbQIBADAJBgUrDgMCGgUAoF0wGAYJKoZIhvcNAQkDMQsGCSqGSIb3DQEHATAcBgkqhkiG9w0BCQUxDxcNMDYwOTEzMDkzNzIyWjAjBgkqhkiG9w0BCQQxFgQULYNalwI9CNnoxfsE3a8NfxujX7gwDQYJKoZIhvcNAQEBBQAEgYBnFV3uy0eVWLUaRL4tCtpP3Q70MbAV1Gu6CPf/AbpJrDdqgAwDlv3krA7rIkB+JT1tVsKqw9iBfOgphOSlOn47w25wt2/X6zmLBawnibHnYIWn1ZeTCgn6izgeb/zb4P7xZwUbN6FrgayWXP6owhSKClhwsMvvegHK8zrqbZVDaw==-----END PKCS7-----
	        " />
	    </form>
	    <?php _e('<p>Need help? FAQ, Usage instructions and other notes can be found on the plugin page <a href="http://wordpress.org/extend/plugins/download-monitor/">here</a>.</p>',"wp-download_monitor"); ?>
		<?php _e('<p>The Wordpress Download monitor plugin was created by <a href="http://blue-anvil.com/">Mike Jolley</a>. The development
	    of this plugin took a lot of time and effort, so please don\'t forget to donate if you found this plugin useful to ensure continued development.</p>',"wp-download_monitor"); ?>
    </div>

<?php
	}
	
	echo '</div>';
}

################################################################################
// Configuration page
################################################################################

function wp_dlm_config() {

	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_cats,$wp_dlm_db_formats,$dlm_url,$downloadtype;

	// turn off magic quotes
	wp_dlm_magic();
	
	wp_dlm_update();
	
	?>
	
	<div class="download_monitor">
	
	<div class="wrap alternate">
    
    <div id="downloadadminicon" class="icon32"><br/></div>
    <h2><?php _e('Download Monitor Configuration',"wp-download_monitor"); ?></h2>
    
    <?php
	$action = $_GET['action'];
	if (!empty($action)) {
		switch ($action) {
				case "saveurl" :
				  $dlm_url = $_POST['url'];						 
					update_option('wp_dlm_url', trim($dlm_url));
					update_option('wp_dlm_type', $_POST['type']);
					$downloadtype = get_option('wp_dlm_type');
					if (!empty($dlm_url)) {
						echo '<div id="message"class="updated fade">';	
						_e('<p>Download URL updated - You need to <strong>re-save your permalinks settings</strong> (Options/settings -> Permalinks) for 
						the changes to occur in your blog.</p>
						<p>If your .htaccess file cannot be written to by WordPress, add the following to your 
					.htaccess file above the "# BEGIN WordPress" line:</p>
						<p>Options +FollowSymLinks<br/>
						RewriteEngine on<br/>
						RewriteRule ^download/([^/]+)$ *your wp-content dir*/plugins/download-monitor/download.php?id=$1 [L]</p>
						<p>replacing "download/" with your custom url and "*your wp-content dir*" with your wp-content directory.</p>',"wp-download_monitor");			
						echo '</div>';
					} else {
					echo '<div id="message"class="updated fade">';				
						_e('<p>Download URL updated - You need to <strong>re-save your permalinks settings</strong> (Options/settings -> Permalinks) for 
						the changes to occur in your blog.</p>
						<p>If your .htaccess file cannot be written to by WordPress, remove the following from your 
					.htaccess file if it exists above the "# BEGIN WordPress" line:</p>
						<p>Options +FollowSymLinks<br/>
						RewriteEngine on<br/>
						RewriteRule ^download/([^/]+)$ *your wp-content dir*/plugins/download-monitor/download.php?id=$1 [L]</p>
						<p>replacing "download/" with your previous custom url and "*your wp-content dir*" with your wp-content directory.</p>',"wp-download_monitor");
					echo '</div>';
					}
					$save_url = true;
					$show=true;
				break;
				case "saveoptions" :
					update_option('wp_dlm_image_url', $_POST['wp_dlm_image_url']);
					update_option('wp_dlm_default_format', $_POST['wp_dlm_default_format']);
					update_option('wp_dlm_does_not_exist', $_POST['wp_dlm_does_not_exist']);
					update_option('wp_dlm_member_only', $_POST['wp_dlm_member_only']);
					$save_opt=true;
					echo '<div id="message"class="updated fade">';	
						_e('<p>Options updated</p>',"wp-download_monitor");			
						echo '</div>';
					$show=true;
				break;				
				case "categories" :
					$name = $_POST['cat_name'];
					if (!empty($name)) {
						$parent = $_POST['cat_parent'];
						if (!$parent) $parent=0;
						$query_ins = sprintf("INSERT INTO %s (name, parent) VALUES ('%s','%s')",
							$wpdb->escape( $wp_dlm_db_cats ),
							$wpdb->escape( $name ),
							$wpdb->escape( $parent ));
						$wpdb->query($query_ins);
						if ($wpdb->insert_id>0)	echo '<div id="message" class="updated fade"><p><strong>'.__('Category added',"wp-download_monitor").'</strong></p></div>';
						else echo '<div id="message" class="updated fade"><p><strong>'.__('Category was not added. Try Recreating the download database from the configuration page.',"wp-download_monitor").'</strong></p></div>';
						$ins_cat=true;
					}
					$show=true;
				break;
				case "deletecat" :
					$id = $_GET['id'];
					// Get 'em
					$delete_cats=array();
					$delete_cats[]=$id;
					// Sub cats
					function dlm_get_cats($delete_cats) {
						global $wpdb, $wp_dlm_db_cats;
						$query = sprintf("SELECT id from %s WHERE parent IN (%s);",
							$wpdb->escape( $wp_dlm_db_cats ),
							$wpdb->escape( implode(",",$delete_cats) ));
						$res = $wpdb->get_results($query);
						$b=sizeof($delete_cats);
						if ($res) {
							foreach($res as $r) {
								if (!in_array($r->id,$delete_cats)) $delete_cats[]=$r->id;
							}
						}
						$a=sizeof($delete_cats);
						while ($b!=$a) {
							$query = sprintf("SELECT id from %s WHERE parent IN (%s);",
								$wpdb->escape( $wp_dlm_db_cats ),
								$wpdb->escape( implode(",",$delete_cats) ));
							$res = $wpdb->get_results($query);
							$b=sizeof($delete_cats);
							if ($res) {
								foreach($res as $r) {
									if (!in_array($r->id,$delete_cats)) $delete_cats[]=$r->id;
								}
							}
							$a=sizeof($delete_cats);
						} 
						return $delete_cats;
					}
					$delete_cats = dlm_get_cats($delete_cats);
					// Delete
					$query_delete = sprintf("DELETE FROM %s WHERE id IN (%s);",
						$wpdb->escape( $wp_dlm_db_cats ),
						$wpdb->escape( implode(",",$delete_cats) ));
					$wpdb->query($query_delete);
					// Remove from downloads
					$query_update = sprintf("UPDATE %s SET category_id='0' WHERE category_id IN (%s);",
						$wpdb->escape( $wp_dlm_db ),
						$wpdb->escape( implode(",",$delete_cats) ));		
					$d = $wpdb->get_row($query_update);
					echo '<div id="message" class="updated fade"><p><strong>'.__('Category deleted Successfully',"wp-download_monitor").'</strong></p></div>';
					$ins_cat=true;
					$show=true;
				break;
				case "reinstall" :
					wp_dlm_reinstall();
					echo '<div id="message" class="updated fade"><p><strong>'.__('Database recreated',"wp-download_monitor").'</strong></p></div>';
					$show=true;				
				break;
				case "formats" :
					wp_cache_flush();
					if ($_POST['savef']) {
						$loop = 0;
						if (is_array($_POST['formatfieldid'])) {
							foreach($_POST['formatfieldid'] as $formatid) {
								if ($_POST['formatfield'][$loop]) {
									$query_update = sprintf("UPDATE %s SET `format`='%s' WHERE id = %s;",
										$wpdb->escape( $wp_dlm_db_formats ),
										$wpdb->escape( stripslashes($_POST['formatfield'][$loop]) ),
										$wpdb->escape( $formatid ));
									$wpdb->query($query_update);
								}
								//echo htmlspecialchars($wpdb->escape( stripslashes($_POST['formatfield'][$loop]) ));
								$loop++;	
							}						
							echo '<div id="message" class="updated fade"><p><strong>'.__('Formats updated',"wp-download_monitor").'</strong></p></div>';											$ins_format=true;
						}
					} else {
						$name = $_POST['format_name'];
						$format = $_POST['format'];
						if (!empty($name) && !empty($format)) {
							$query_ins = sprintf("INSERT INTO %s (name, format) VALUES ('%s','%s')",
								$wpdb->escape( $wp_dlm_db_formats ),
								$wpdb->escape( $name ),
								$wpdb->escape( $format ));
							$wpdb->query($query_ins);
							echo '<div id="message" class="updated fade"><p><strong>'.__('Format added',"wp-download_monitor").'</strong></p></div>';
							$ins_format=true;
						}
					}
					$show=true;
				break;
				case "deleteformat" :
					wp_cache_flush();
					$id = $_GET['id'];
					// Delete
					$query_delete = sprintf("DELETE FROM %s WHERE id=%s;",
						$wpdb->escape( $wp_dlm_db_formats ),
						$wpdb->escape( $id ));
					$wpdb->query($query_delete);					
					echo '<div id="message" class="updated fade"><p><strong>'.__('Format deleted Successfully',"wp-download_monitor").'</strong></p></div>';
					$ins_format=true;
					$show=true;
				break;
		}
	}
	
	if ( ($show==true) || ( empty($action) ) )
	{
	
	?>
	<br class="" style="clear: both;"/>
    <div id="poststuff" class="dlm meta-box-sortables">
    
        <div class="postbox <?php if (!$ins_cat) echo 'close-me';?> dlmbox">
            <h3 class="hndle"><?php _e('Download Categories',"wp-download_monitor"); ?></h3>
            <div class="inside">
            	<?php _e('<p>You can categorise downloads using these categories. You can then show groups of downloads using the category tags or a dedicated download page (see documentation). Please note, deleting a category also deletes it\'s child categories.</p>',"wp-download_monitor"); ?>
                
                <form action="?page=dlm_config&amp;action=categories" method="post">
                    <table class="widefat"> 
                        <thead>
                            <tr>
                                <th scope="col" style="text-align:center"><?php _e('ID',"wp-download_monitor"); ?></th>
                                <th scope="col"><?php _e('Name',"wp-download_monitor"); ?></th>
                                <th scope="col" style="text-align:center"><?php _e('Action',"wp-download_monitor"); ?></th>
                            </tr>
                        </thead>
                        <tbody id="the-list">
                        	<?php
								function get_children_cats($parent,$chain) {
									global $wp_dlm_db_cats,$wpdb;
									$sql = sprintf("SELECT * FROM %s WHERE parent=%s ORDER BY id;",
										$wpdb->escape( $wp_dlm_db_cats ),
										$wpdb->escape( $parent ));	
									$scats = $wpdb->get_results($sql);
									if (!empty($scats)) {
										foreach ( $scats as $c ) {
											echo '<tr><td style="text-align:center">'.$c->id.'</td><td>'.$chain.''.$c->name.'</td><td style="text-align:center"><a href="?page=dlm_config&amp;action=deletecat&amp;id='.$c->id.'"><img src="'.WP_CONTENT_URL.'/plugins/download-monitor/img/cross.png" alt="Delete" title="Delete" /></a></td></tr>';
											get_children_cats($c->id, "$chain$c->name &mdash; ");
										}
									}
									return;
								}
								
								$query_select_cats = sprintf("SELECT * FROM %s WHERE parent=0 ORDER BY id;",
									$wpdb->escape( $wp_dlm_db_cats ));	
								$cats = $wpdb->get_results($query_select_cats);
			
								if (!empty($cats)) {
									foreach ( $cats as $c ) {
										echo '<tr><td style="text-align:center">'.$c->id.'</td><td>'.$c->name.'</td><td style="text-align:center"><a href="?page=dlm_config&amp;action=deletecat&amp;id='.$c->id.'"><img src="'.WP_CONTENT_URL.'/plugins/download-monitor/img/cross.png" alt="Delete" title="Delete" /></a></td></tr>';
										get_children_cats($c->id, "$c->name &mdash; ");
									}
								} else {
									echo '<tr><td colspan="3">'.__('No categories exist',"wp-download_monitor").'</td></tr>';
								}
							?>
                        </tbody>
                    </table>
                	<h4><?php _e('Add category',"wp-download_monitor"); ?></h4>
                    <table class="niceblue small-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <th scope="col"><?php _e('Name',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" name="cat_name" /></td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Parent',"wp-download_monitor"); ?>:</th>
                            <td><select name="cat_parent">
                            	<option value=""><?php _e('None',"wp-download_monitor"); ?></option>
                                <?php
									if (!empty($cats)) {
										foreach ( $cats as $c ) {
											echo '<option value="'.$c->id.'">'.$c->id.' - '.$c->name.'</option>';
											echo get_option_children_cats($c->id, "$c->name &mdash; ", 0);
										}
									} 
								?>
                            </select></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" value="<?php _e('Add',"wp-download_monitor"); ?>" /></p>
                </form>
            </div>
        </div>
        <div class="postbox <?php if (!$ins_format) echo 'close-me';?> dlmbox">
            <h3><?php _e('Custom Output Formats',"wp-download_monitor"); ?></h3>
            <div class="inside">
            	<?php _e('<p>This allows you to define formats in which to output your downloads however you want.</p>',"wp-download_monitor"); ?>                
                <form action="?page=dlm_config&amp;action=formats" method="post">
                    <table class="widefat"> 
                        <thead>
                            <tr>
                            	<th scope="col"><?php _e('ID',"wp-download_monitor"); ?></th>
                                <th scope="col"><?php _e('Name',"wp-download_monitor"); ?></th>
                                <th scope="col"><?php _e('Format',"wp-download_monitor"); ?></th>
                                <th scope="col" style="text-align:center"><?php _e('Action',"wp-download_monitor"); ?></th>
                            </tr>
                        </thead>
                        <tbody id="the-list">
                        	<?php								
								$query_select_formats = sprintf("SELECT * FROM %s ORDER BY id;",
									$wpdb->escape( $wp_dlm_db_formats ));	
								$formats = $wpdb->get_results($query_select_formats);
			
								if (!empty($formats)) {
									foreach ( $formats as $f ) {
										echo '<tr><td style="vertical-align:middle;">'.$f->id.'</td><td style="vertical-align:middle;">'.$f->name.'</td>
										<td style="vertical-align:middle;"><input type="hidden" value="'.$f->id.'" name="formatfieldid[]" /><input type="text" name="formatfield[]" value="'.htmlspecialchars($f->format).'" style="width:100%" /></td>
										<td style="text-align:center;vertical-align:middle;"><a href="?page=dlm_config&amp;action=deleteformat&amp;id='.$f->id.'"><img src="'.WP_CONTENT_URL.'/plugins/download-monitor/img/cross.png" alt="Delete" title="Delete" /></a></td></tr>';
									}
								} else {
									echo '<tr><td colspan="3">'.__('No formats exist',"wp-download_monitor").'</td></tr>';
								}
							?>
                        </tbody>
                    </table>
                    <p class="submit" style="margin:0;"><input name="savef" type="submit" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" /></p>
                	<h4><?php _e('Add format',"wp-download_monitor"); ?></h4>
                	<?php _e('<p>Use the following tags in your custom formats: <em>note</em> if you use <code>"</code> (quote) characters within the special attributes e.g. <code>"before"</code> you should either escape them or use html entities.</p><ul style="margin-left:16px;margin-bottom:12px;">
	                	<li><code>{url}</code> - Url of download (does not include hyperlink)</li>
	                	<li><code>{version}</code> - Version of download</li>
	                	<li><code>{version,"before","after"}</code> - Version of download. Not outputted if none set. Replace "before" with preceding text/html and "after" with succeeding text/html.</li>
	                	<li><code>{title}</code> - Title of download</li>
	                	<li><code>{size}</code> - Filesize of download</li>
	                	<li><code>{category,"before","after"}</code> or <code>{category}</code> - Download Category. Replace "before" with preceding text/html and "after" with succeeding text/html.</li>
	                	<li><code>{hits}</code> - Current hit count</li>
	                	<li><code>{hits,"No hits","1 Hit","% hits"}</code> - Formatted hit count depending on hits. <code>%</code> replaced with hit count.</li>
	                	<li><code>{image_url}</code> - URL of the download image</li>
	                	<li><code>{description,"before","after"}</code> or <code>{description}</code> - Description you gave download. Not outputted if none set. Replace "before" with preceding text/html and "after" with succeeding text/html.</li>
	                	<li><code>{description-autop,"before","after"}</code> or <code>{description-autop}</code> - Description formatted with autop (converts double line breaks to paragraphs)</li>
	                	<li><code>{date,"Y-m-d"}</code> - Date posted. Second argument is for date format.</li>
	                	<li><code>{meta-<em>key</em>}</code> - Custom field value</li>
	                	<li><code>{meta-autop-<em>key</em>}</code> - Custom field value formatted with autop</li>
	                </ul>
	                	<p><strong>Example Format -</strong> Link and description of download with hits in title:</p>
	                	<p><code>&lt;a href="{url}" title="Downloaded {hits} times"&gt;{title}&lt;/a&gt; - {description}</code></p>
	                ',"wp-download_monitor"); ?>
                    <table class="niceblue small-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <th scope="col"><?php _e('Name',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" name="format_name" /></td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Format',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" name="format" style="width:360px;" /></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" value="<?php _e('Add',"wp-download_monitor"); ?>" /></p>
                </form>
            </div>
        </div>
        <div class="postbox <?php if (!$save_url) echo 'close-me';?> dlmbox">
            <h3><?php _e('Custom Download URL',"wp-download_monitor"); ?></h3>
            <div class="inside">
            	<?php _e('<p>Set a custom url for your downloads, e.g. <code>download/</code>. You can also choose how to link to the download in it\'s url, e.g. selecting "filename" would make the link appear as <code>http://yoursite.com/download/filename.zip</code>. This option will only work if using wordpress permalinks (other than default).</p>
            	
                        <p>Leave this option blank to use the default download path (<code>/download-monitor/download.php?id=</code>)</p>
                        <p>If you fill in this option ensure the custom directory does not exist on the server nor does it match a page or post\'s url as this can cause problems redirecting to download.php.</p>',"wp-download_monitor"); ?>
                 
                 <div style="display:block; width:716px; clear:both; margin:12px auto 4px; border:3px solid #eee; -moz-border-radius: 4px; -webkit-border-radius: 4px;">
                 <p style="background:#eee;padding:4px; margin:0;"><strong><?php _e('Without Custom URL:',"wp-download_monitor"); ?></strong></p>
                 <img style="padding:8px" src="<?php echo $wp_dlm_root; ?>img/explain.gif" alt="Explanation" />
                 </div>
                 
                 <div style="display:block; width:716px; clear:both; margin:12px auto 4px; border:3px solid #eee; -moz-border-radius: 4px; -webkit-border-radius: 4px;">
                 <p style="background:#eee;padding:4px; margin:0;"><strong><?php _e('With Custom URL (downloads/ID):',"wp-download_monitor"); ?></strong></p>
                 <img style="padding:8px" src="<?php echo $wp_dlm_root; ?>img/explain2.gif" alt="Explanation" /></div>
                
                <form action="?page=dlm_config&amp;action=saveurl" method="post">
                    <table class="niceblue form-table">
                        <tr>
                            <th scope="col"><strong><?php _e('Custom URL',"wp-download_monitor"); ?>:</strong></th>
                            <td><?php echo get_bloginfo('url'); ?>/<input type="text" name="url" value="<?php echo $dlm_url; ?>" />            
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
        <div class="postbox <?php if (!$save_opt) echo 'close-me';?> dlmbox">
            <h3><?php _e('General Options',"wp-download_monitor"); ?></h3>
            <div class="inside">               
                <form action="?page=dlm_config&amp;action=saveoptions" method="post">
                    <table class="niceblue form-table">
                        <tr>
                            <th scope="col"><?php _e('"Download not found" redirect URL',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" value="<?php echo get_option('wp_dlm_does_not_exist'); ?>" name="wp_dlm_does_not_exist" /> <span class="setting-description"><?php _e('Leave blank for no redirect.',"wp-download_monitor"); ?></span></td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Member-only files non-member redirect',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" value="<?php echo get_option('wp_dlm_member_only'); ?>" name="wp_dlm_member_only" /> <span class="setting-description"><?php _e('Leave blank for no redirect.',"wp-download_monitor"); ?> <?php _e('Note: <code>{referrer}</code> will be replaced with current url. Useful if sending user to the login page and then back to the download :) e.g. <code>http://yourdomain.com/wp-login.php?redirect_to={referrer}</code>.',"wp-download_monitor"); ?></span></td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Download image path',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" value="<?php echo get_option('wp_dlm_image_url'); ?>" name="wp_dlm_image_url" /> <span class="setting-description"><?php _e('This image is used when using the <code>#image</code> download tag and the <code>{image_url}</code> tag on this page. Please use an absolute url (e.g. <code>http://yoursite.com/image.gif</code>).',"wp-download_monitor"); ?></span></td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Default output format',"wp-download_monitor"); ?>:</th>
                            <td><select name="wp_dlm_default_format" id="wp_dlm_default_format">
                            	<option value="0"><?php _e('None',"wp-download_monitor"); ?></option>
                        	<?php								
								$query_select_formats = sprintf("SELECT * FROM %s ORDER BY id;",
									$wpdb->escape( $wp_dlm_db_formats ));	
								$formats = $wpdb->get_results($query_select_formats);
			
								if (!empty($formats)) {
									foreach ( $formats as $f ) {
										echo '<option ';
										if (get_option('wp_dlm_default_format')==$f->id) echo 'selected="selected" ';
										echo 'value="'.$f->id.'">'.$f->name.'</option>';
									}
								}
							?>                            	
                            </select></td>
                        </tr>                        
                    </table>
                    <p class="submit"><input type="submit" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" /></p>
                </form>
            </div>
        </div>
        <div class="postbox close-me dlmbox">
            <h3><?php _e('Recreate Download Database',"wp-download_monitor"); ?></h3>
            <div class="inside">
            	<?php _e('<p>This will delete the old download monitor tables and recreate them. You should only do this as a last resort if experiencing database errors after updating the plugin. Download monitor will attempt to re-add any downloads currently in the database.</p>',"wp-download_monitor"); ?>
                <?php _e('<p>WARNING: THIS MAY DELETE DOWNLOAD DATA IN THE DATABASE; BACKUP YOUR DATABASE FIRST!</p>',"wp-download_monitor"); ?>
                <form action="?page=dlm_config&amp;action=reinstall" method="post">
                    <p class="submit"><input type="submit" value="<?php _e('Recreate Download Database',"wp-download_monitor"); ?>" /></p>
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript">
		<!--
		<?php
			global $wp_db_version;
			if ($wp_db_version >= 9872) {
				echo "jQuery('.postbox h3').before('<div class=\"handlediv\" title=\"Click to toggle\"><br /></div>');";
			} else {
				echo "jQuery('.postbox h3').prepend('<a class=\"togbox\">+</a> ');";
			}
		?>
		
		jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox.close-me').each(function(){
			jQuery(this).addClass("closed");
		});
		//-->
	</script>
	<?php
	}
	
	echo '</div>';
}

################################################################################
// let_to_num used for file sizes
################################################################################

function let_to_num($v){ //This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
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

################################################################################
// Add Download Page
################################################################################

function dlm_addnew() {

	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_cats,$wp_dlm_db_formats,$wp_dlm_db_meta;

	// turn off magic quotes
	wp_dlm_magic();
	
	wp_dlm_update();
	
		?>
	
	<div class="download_monitor">
	
	<div class="wrap alternate">
    
    <div class="wrap">

    <div id="downloadadminicon" class="icon32"><br/></div>
    <h2><?php _e('Add New Download',"wp-download_monitor"); ?></h2>
    
    <?php
    if ($_POST) {
		//get postdata
		$title = htmlspecialchars(trim($_POST['title']));
		$filename = htmlspecialchars(trim($_POST['filename']));									
		$dlversion = htmlspecialchars(trim($_POST['dlversion']));
		$dlhits = htmlspecialchars(trim($_POST['dlhits']));
		$postDate = $_POST['postDate'];
		$user = $_POST['user'];
		$members = (isset($_POST['memberonly'])) ? 1 : 0;
		$download_cat = $_POST['download_cat'];
		$mirrors = htmlspecialchars(trim($_POST['mirrors']));
		$file_description = trim($_POST['file_description']);
    }
	if ( $_POST['save'] ) {
										
		//validate fields
		if (empty( $_POST['title'] )) $errors.=__('<div class="error">Required field: <strong>Title</strong> omitted</div>',"wp-download_monitor");
		if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
		if (!is_numeric($_POST['dlhits'] )) $errors.=__('<div class="error">Invalid <strong>hits</strong> entered</div>',"wp-download_monitor");
									
		//attempt to upload file
		if ( empty($errors ) ) {
															
					$time = current_time('mysql');
					$overrides = array('test_form'=>false);

					$file = wp_handle_upload($_FILES['upload'], $overrides, $time);

					if ( !isset($file['error']) ) {
						$full_path = $file['url'];
						$info = $file['url'];
						$filename = $file['url'];
					} 
					else $errors = '<div class="error">'.$file['error'].'</div>';												
					
		}										
		//save to db
		if ( empty($errors ) ) {
		
			// Add download							
			$query_add = sprintf("INSERT INTO %s (title, filename, dlversion, postDate, hits, user, members,category_id, mirrors, file_description) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
			$wpdb->escape( $wp_dlm_db ),
			$wpdb->escape( $_POST['title'] ),
			$wpdb->escape( $filename ),
			mysql_real_escape_string( $_POST['dlversion'] ),
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
			
				// Process and save meta/custom fields
				$index = 1;
				$values = array();
				if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
				{
					if (trim($meta['key'])) {
						$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim($meta['key']))))).'", "'.$wpdb->escape($meta['value']).'", '.$wpdb->insert_id.')';
						$index ++;
					}
				}
				if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
				
				if (empty($info)) echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully","wp-download_monitor").'</strong></p></div>';
				else echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully","wp-download_monitor").' - '.$info.'</strong></p></div>';											
				// Redirect
				echo '<meta http-equiv="refresh" content="1;url=admin.php?page=download-monitor/wp-download_monitor.php"/>';
				exit;
			}
			else _e('<div class="error">Error saving to database</div>',"wp-download_monitor");										
		} else echo $errors;									
		
	} 
								
	$max_upload_size_text = '';
	
	if (function_exists('ini_get')) {
		$max_upload_size = min(let_to_num(ini_get('post_max_size')), let_to_num(ini_get('upload_max_filesize')));
		$max_upload_size_text = __(' (defined in php.ini)',"wp-download_monitor");
	}
	
	if (!$max_upload_size || $max_upload_size==0) {
		$max_upload_size = 8388608;
		$max_upload_size_text = '';
	}				
		
	?>
		<form enctype="multipart/form-data" action="?page=dlm_addnew&amp;action=add&amp;method=upload" method="post" id="wp_dlm_add" name="add_download" class="form-table"> 
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_upload_size; ?>" />
            <table class="optiontable niceblue" cellpadding="0" cellspacing="0"> 
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Title (required)',"wp-download_monitor"); ?>: </strong></th> 
                    <td>
                        <input type="text" style="width:320px;" class="cleardefault" value="<?php echo $title; ?>" name="title" id="dltitle" maxlength="200" />												
                    </td> 
                </tr>
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Version',"wp-download_monitor"); ?>: </strong></th> 
                    <td>
                        <input type="text" style="width:320px;" class="cleardefault" value="<?php echo $dlversion; ?>" name="dlversion" id="dlversion" />
                    </td> 
                </tr>
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Description',"wp-download_monitor"); ?>: </strong></th> 
                    <td><textarea name="file_description" cols="50" rows="6"><?php echo $file_description; ?></textarea></td> 
                </tr>
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Starting hits',"wp-download_monitor");?>: </strong></th> 
                    <td>
                        <input type="text" style="width:100px;" class="cleardefault" value="<?php if ($dlhits>0) echo $dlhits; else echo 0; ?>" name="dlhits" id="dlhits" maxlength="50" />
                    </td> 
                </tr>
				<tr valign="top">
						<th scope="row"><strong><?php _e('Select a file...',"wp-download_monitor"); ?></strong></th> 
						<td><input type="file" name="upload" style="width:320px;" /><br /><span class="setting-description"><?php _e('Max. filesize',"wp-download_monitor"); echo $max_upload_size_text; ?> = <?php echo $max_upload_size; ?> <?php _e('bytes',"wp-download_monitor"); ?>. <?php _e('If a file with the same name already exists in the upload directly, this file will be renamed automatically.',"wp-download_monitor"); ?></span></td>
                </tr> 
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Category',"wp-download_monitor"); ?></strong></th> 
                    <td>
                    <select name="download_cat">
                    	<option value="0"><?php _e('N/A',"wp-download_monitor"); ?></option>
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
                    </select><br /><span class="setting-description"><?php _e('Categories are optional and allow you to group and organise simular downloads.',"wp-download_monitor"); ?></span></td>
                </tr>  
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Member only file?',"wp-download_monitor"); ?></strong></th> 
                    <td><input type="checkbox" name="memberonly" style="vertical-align:top" <?php if ($members==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, only logged in users will be able to access the file via a download link. It is a good idea to give the file a name which cannot be easily guessed and accessed directly. BONUS: Add a meta key called min-level to set the minimum user level needed to download the file.',"wp-download_monitor"); ?></span></td>
                </tr>
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Download Mirrors',"wp-download_monitor"); ?></strong></th> 
                    <td><textarea name="mirrors" cols="50" rows="2"><?php echo $mirrors; ?></textarea><br /><span class="setting-description"><?php _e('Optionally list the url\'s of any mirrors here (1 per line). Download monitor will randomly pick one of these mirrors when serving the download.',"wp-download_monitor"); ?></span></td>
                </tr>

            </table>
            <h3><?php _e('Custom fields',"wp-download_monitor"); ?></h3>
            <p><?php _e('Custom fields can be used to add extra metadata to a download. Leave blank to add none. Name should be lower case with no spaces (changed automatically, e.g. <code>Some Name</code> will become <code>some-name</code>.',"wp-download_monitor"); ?></p>
			<table style="width:80%">
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
											<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="'.strtolower((str_replace(' ','-',trim($meta['key'])))).'" type="text" style="width:95%">
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
			<hr />

            <p class="submit"><input type="submit" class="btn button-primary" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Upload &amp; save',"wp-download_monitor"); ?>" /></p>
			<input type="hidden" name="postDate" value="<?php echo date_i18n(__('Y-m-d H:i:s',"wp-download_monitor")) ;?>" />
			<?php 
				global $userdata;
				get_currentuserinfo();										
				echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
			?>									
		</form>
	</div>
	<?php	

	

	echo '</div>';
}


################################################################################
// Add Existing Download Page
################################################################################

function dlm_addexisting() {

	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_cats,$wp_dlm_db_formats,$wp_dlm_db_meta;

	// turn off magic quotes
	wp_dlm_magic();
	
	wp_dlm_update();
	
		?>
	
	<div class="download_monitor">
	
	<div class="wrap alternate">
    
    <div class="wrap">

    <div id="downloadadminicon" class="icon32"><br/></div>
    <h2><?php _e('Add Existing Download',"wp-download_monitor"); ?></h2>
    
    <?php
    if ($_POST) {
		//get postdata
		$title = htmlspecialchars(trim($_POST['title']));
		$filename = htmlspecialchars(trim($_POST['filename']));									
		$dlversion = htmlspecialchars(trim($_POST['dlversion']));
		$dlhits = htmlspecialchars(trim($_POST['dlhits']));
		$postDate = $_POST['postDate'];
		$user = $_POST['user'];
		$members = (isset($_POST['memberonly'])) ? 1 : 0;
		$download_cat = $_POST['download_cat'];
		$mirrors = htmlspecialchars(trim($_POST['mirrors']));
		$file_description = trim($_POST['file_description']);
    }
	if ( $_POST['save'] ) {
											
		//validate fields
		if (empty( $_POST['title'] )) $errors.=__('<div class="error">Required field: <strong>Title</strong> omitted</div>',"wp-download_monitor");
		if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
		if (!is_numeric($_POST['dlhits'] )) $errors.=__('<div class="error">Invalid <strong>hits</strong> entered</div>',"wp-download_monitor");
		if ( empty( $_POST['filename']) ) $errors.=__('<div class="error">No file selected</div>',"wp-download_monitor");
												
		//save to db
		if ( empty($errors ) ) {	
										
			$query_add = sprintf("INSERT INTO %s (title, filename, dlversion, postDate, hits, user, members,category_id, mirrors, file_description) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
			$wpdb->escape( $wp_dlm_db ),
			$wpdb->escape( $_POST['title'] ),
			$wpdb->escape( $filename ),
			mysql_real_escape_string( $_POST['dlversion'] ),
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
			
				// Process and save meta/custom fields
				$index = 1;
				$values = array();
				if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
				{
					if (trim($meta['key'])) {
						$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim($meta['key']))))).'", "'.$wpdb->escape($meta['value']).'", '.$wpdb->insert_id.')';
						$index ++;
					}
				}
				if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
			
				if (empty($info)) echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully","wp-download_monitor").'</strong></p></div>';
				else echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully","wp-download_monitor").' - '.$info.'</strong></p></div>';											

				// Redirect
				echo '<meta http-equiv="refresh" content="1;url=admin.php?page=download-monitor/wp-download_monitor.php"/>';
				exit;
			}
			else _e('<div class="error">Error saving to database</div>',"wp-download_monitor");										
		} else echo $errors;									
		
	} 				
		
	?>
		<form action="?page=dlm_addexisting&amp;action=add&amp;method=upload" method="post" id="wp_dlm_add" name="add_download" class="form-table">
            <table class="optiontable niceblue" cellpadding="0" cellspacing="0"> 
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
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Description',"wp-download_monitor"); ?>: </strong></th> 
                    <td><textarea name="file_description" cols="50" rows="6"><?php echo $file_description; ?></textarea></td> 
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
                        <input type="text" style="width:320px;" class="cleardefault" value="<?php echo $filename; ?>" name="filename" id="filename" /> <a class="browsefiles" style="display:none" href="#"><?php _e('Toggle File Browser',"wp-download_monitor"); ?></a>
                        <div id="file_browser"></div>
                    </td> 
                </tr>
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Category',"wp-download_monitor"); ?></strong></th> 
                    <td>
                    <select name="download_cat">
                    	<option value="0"><?php _e('N/A',"wp-download_monitor"); ?></option>
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
                    </select><br /><span class="setting-description"><?php _e('Categories are optional and allow you to group and organise simular downloads.',"wp-download_monitor"); ?></span></td>
                </tr>  
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Member only file?',"wp-download_monitor"); ?></strong></th> 
                    <td><input type="checkbox" style="vertical-align:top" name="memberonly" <?php if ($members==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, only logged in users will be able to access the file via a download link. It is a good idea to give the file a name which cannot be easily guessed and accessed directly. BONUS: Add a meta key called min-level to set the minimum user level needed to download the file.',"wp-download_monitor"); ?></span></td>
                </tr> 
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Download Mirrors',"wp-download_monitor"); ?></strong></th> 
                    <td><textarea name="mirrors" cols="50" rows="2"><?php echo $mirrors; ?></textarea><br /><span class="setting-description"><?php _e('Optionally list the url\'s of any mirrors here (1 per line). Download monitor will randomly pick one of these mirrors when serving the download.',"wp-download_monitor"); ?></span></td>
                </tr>
            </table>
            <h3><?php _e('Custom fields',"wp-download_monitor"); ?></h3>
            <p><?php _e('Custom fields can be used to add extra metadata to a download. Leave blank to add none. Name should be lower case with no spaces (changed automatically, e.g. <code>Some Name</code> will become <code>some-name</code>.',"wp-download_monitor"); ?></p>
			<table style="width:80%">
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
										<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="'.strtolower((str_replace(' ','-',trim($meta['key'])))).'" type="text" style="width:95%">
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
			<hr />
            
			<p class="submit"><input type="submit" class="btn button-primary" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Save',"wp-download_monitor"); ?>" /></p>
			<input type="hidden" name="postDate" value="<?php echo date_i18n(__('Y-m-d H:i:s',"wp-download_monitor")) ;?>" />
			<?php 
				global $userdata;
				get_currentuserinfo();										
				echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
			?>									
		</form>
	</div>
	<?php	

	echo '</div>';
}


################################################################################
// LOG VIEWER PAGE
################################################################################

function wp_dlm_log()
{
	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_cats,$wp_dlm_db_formats, $wp_dlm_db_log;

	wp_dlm_update();
	
	echo '<div class="download_monitor">';
	
	$action = $_GET['action'];
	if (!empty($action)) {
		switch ($action) {
				case "clear_logs" :
					$wpdb->query("DELETE FROM $wp_dlm_db_log;");
				break;
		}
	}	
	?>
	
    <div class="wrap alternate">
    	<div id="downloadadminicon" class="icon32"><br/></div>
        <h2><?php _e('Download Logs',"wp-download_monitor"); ?></h2>
        <p><a href="?page=dlm_log&action=clear_logs" class="button" id="dlm_clearlog"><?php _e('Clear Log',"wp-download_monitor"); ?></a></p>
        <table class="widefat"> 
			<thead>
				<tr>
				<th scope="col" style="text-align:center"><?php _e('ID',"wp-download_monitor"); ?></th>
				<th scope="col"><?php _e('Title',"wp-download_monitor"); ?></th>
				<th scope="col"><?php _e('File',"wp-download_monitor"); ?></th>
                <th scope="col"><?php _e('User',"wp-download_monitor"); ?></th>
                <th scope="col"><?php _e('IP Address',"wp-download_monitor"); ?></th>
				<th scope="col"><?php _e('Date',"wp-download_monitor"); ?></th>
				</tr>
			</thead>						
		<?php	
				// If current page number, use it 
				if(!isset($_REQUEST['p'])){ 
					$page = 1; 
				} else { 
					$page = $_REQUEST['p']; 
				}
									
				// Figure out the limit for the query based on the current page number. 
				$from = (($page * 20) - 20); 
			
				$paged_select = sprintf("SELECT $wp_dlm_db.*, $wp_dlm_db_log.ip_address, $wp_dlm_db_log.date, $wp_dlm_db_log.user_id
					FROM $wp_dlm_db_log  
					INNER JOIN $wp_dlm_db ON $wp_dlm_db_log.download_id = $wp_dlm_db.id 
					ORDER BY $wp_dlm_db_log.date DESC LIMIT %s,20;",
						$wpdb->escape( $from ));
					
				$logs = $wpdb->get_results($paged_select);
				$total = $wpdb->get_var("SELECT COUNT(*) FROM $wp_dlm_db_log INNER JOIN $wp_dlm_db ON $wp_dlm_db_log.download_id = $wp_dlm_db.id;");
			
				// Figure out the total number of pages. Always round up using ceil() 
				$total_pages = ceil($total / 20);
			
				if (!empty($logs)) {
					echo '<tbody id="the-list">';
					foreach ( $logs as $log ) {
						$date = date_i18n(__("jS M Y H:i:s","wp-download_monitor"), strtotime($log->date));
						$path = WP_CONTENT_URL."/uploads/";
						$file = str_replace($path, "", $log->filename);
						$links = explode("/",$file);
						$file = end($links);
						echo '<tr class="alternate">';
						echo '<td style="text-align:center">'.$log->id.'</td>
						<td>'.$log->title.'</td>
						<td>'.$file.'</td>
						<td>';
						if ($log->user_id) {
							$user_info = get_userdata($log->user_id);
				    		echo $user_info->user_login . ' ('.$user_info->ID.')';
				    	}			
						echo '</td>
						<td><a href="http://ws.arin.net/whois/?queryinput='.$log->ip_address.'" target="_blank">'.$log->ip_address.'</a></td>
						<td>'.$date.'</td>';
						
					}
					echo '</tbody>';
				} else echo '<tr><th colspan="6">'.__('No downloads logged.',"wp-download_monitor").'</th></tr>';
		?>			
		</table>

        <div class="tablenav">
        	<div style="float:left" class="tablenav-pages">
				<?php
					if ($total_pages>1)  {
					
						// Build Page Number Hyperlinks 
						if($page > 1){ 
							$prev = ($page - 1); 
							echo "<a href=\"?page=dlm_log&amp;p=$prev\">&laquo; ".__('Previous',"wp-download_monitor")."</a> "; 
						} else echo "<span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span>";

						for($i = 1; $i <= $total_pages; $i++){ 
							if(($page) == $i){ 
								echo " <span class='page-numbers current'>$i</span> "; 
								} else { 
									echo " <a href=\"?page=dlm_log&amp;p=$i\">$i</a> "; 
							} 
						} 

						// Build Next Link 
						if($page < $total_pages){ 
							$next = ($page + 1); 
							echo "<a href=\"?page=dlm_log&amp;p=$next\">".__('Next',"wp-download_monitor")." &raquo;</a>"; 
						} else echo "<span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span>";
						
					}
				?>	
            </div>        	
        </div>
        <br style="clear: both; margin-bottom:1px; height:2px; line-height:2px;" />
    </div>

	</div>
	
	<?php
}


################################################################################
// mod_rewrite rules
################################################################################
// cheers to David Underhill for recent fix!
function wp_dlm_rewrite($rewrite) {
	global $dlm_url;
	$blog = get_bloginfo('wpurl');
	$base_url = get_bloginfo('url');

	/* Removed offset so that when using wordpress in a sub dir we don't need to show wordpress' sub directory name in the url..
	
	
	if(strlen($blog) > strlen($base_url))
		$offset = substr(str_replace($base_url, '', $blog), 1) . '/';
	else*/
		$offset = '';
	
	$rule = ('
Options +FollowSymLinks
RewriteEngine on
RewriteRule ^'.$offset.$dlm_url.'([^/]+)$ '.WP_PLUGIN_URL.'/download-monitor/download.php?id=$1 [L]
');
	return $rule.$rewrite;	
}

// Hook in.
global $dlm_url;
if (!empty($dlm_url)) 
	add_filter('mod_rewrite_rules', 'wp_dlm_rewrite');

################################################################################
// Main template tag (get) function
################################################################################

class download_object {
	var $id;
	var $url;
	var $size;
	var $version;
	var $image;
	var $desc;
	var $category;
	var $category_id;
	var $memberonly;
	var $dlversion;
	var $file_description;
	var $postDate;
	var $members;
	var $filename;
	var $hits;
	var $user;
	var $mirrors;
	var $title;
}
	
function get_downloads($args = null) {

	$defaults = array(
		'limit' => '', 
		'offset' => '0',
		'orderby' => 'id',
		'meta_name' => '',
		'vip' => '0',
		'category' => '',
		'tags' => '',	
		'order' => 'asc',
		'digforcats' => 'true',
		'exclude' => ''
	);
	
	$args = str_replace('&amp;','&',$args);

	$r = wp_parse_args( $args, $defaults );
	
	global $wpdb,$wp_dlm_root, $wp_dlm_db, $wp_dlm_db_cats, $wp_dlm_db_meta, $dlm_url, $downloadurl, $downloadtype;
	
	$where = array();
	$join = '';
	$select = '';
	
	// Handle $exclude
	$exclude_array = array();
	if ( $r['exclude'] ) {
		$exclude_unclean = explode(',',$r['exclude']);		
		foreach ($exclude_unclean as $e) {
			$e = trim($e);
			if (is_numeric($e)) $exclude_array[] = $e;
		}
	}
	if (sizeof($exclude_array) > 0) {
		$where[] = ' '.$wp_dlm_db.'.id NOT IN ('.implode(',',$exclude_array).') ';
	}	
		
	if ( empty( $r['limit'] ) || !is_numeric($r['limit']) )
		$r['limit'] = '';
		
	if ( !empty( $r['limit'] ) && (empty($r['offset']) || !is_numeric($r['offset'])) ) $r['offset'] = 0;
	elseif ( empty( $r['limit'] )) $r['offset'] = '';
	
	if ( !empty( $r['limit'] ) ) $limitandoffset = ' LIMIT '.$r['offset'].', '.$r['limit'].' ';
	if ( ! empty($r['category']) && $r['category']!='none' ) {
		$categories = explode(',',$r['category']);
		$the_cats = array();
		// Traverse through categories to get sub-cats
		foreach ($categories as $cat) {
			$the_cats[] = $cat;
			if ($r['digforcats']) $the_cats = wp_dlm_get_sub_cats($the_cats);
		}
		$categories = implode(',',$the_cats);		
		$where[] = ' category_id IN ('.$categories.') ';
	} elseif ($r['category']=='none') {
		$where[] = ' category_id = 0 ';
	} else $category = '';
	
	if ( ! empty($r['tags']) ) {
		$tags = explode(',', $r['tags']);
		if (sizeof($tags)>0) {
			// Get posts with tags
			$tagged = $wpdb->get_results( "SELECT * FROM $wp_dlm_db INNER JOIN $wp_dlm_db_meta ON $wp_dlm_db.id = $wp_dlm_db_meta.download_id WHERE $wp_dlm_db_meta.meta_name = 'tags';");
			$postIDS = array();
			foreach ($tagged as $t) {
				$my_tags = explode(',', $t->meta_value );
				$my_clean_tags = array();
				foreach ($my_tags as $tag) {
					$my_clean_tags[] = trim(strtolower($tag));
				}
				foreach ($tags as $tag) {
					if (in_array(trim(strtolower($tag)), $my_clean_tags)) $postIDS[] = $t->download_id;
				}
			}
			$where[] = ' '.$wp_dlm_db.'.id IN ('.implode(',',$postIDS).') ';
		}				
	} else $tags = '';
	
	if ( $vip==1 ) {
	
		global $user_ID;
		// If not logged in dont show member only files
		if (!isset($user_ID)) {
			$where[] = ' members = 0 ';
		}
		
	}

	if ( ! empty($r['orderby']) ) {
		// Can order by date/postDate, filename, title, id, hits, random
		$r['orderby'] = strtolower($r['orderby']);
		switch ($r['orderby']) {
			case 'postdate' : 
			case 'date' : 
				$orderby = 'postDate';
			break;
			case 'filename' : 
				$orderby = 'filename';
			break;
			case 'title' : 
				$orderby = 'title';
			break;
			case 'hits' : 
				$orderby = 'hits';
			break;
			case 'meta' : 
				$orderby = 'meta';
				$join = " LEFT JOIN $wp_dlm_db_meta ON $wp_dlm_db.id = $wp_dlm_db_meta.download_id ";
				$select = ", $wp_dlm_db_meta.meta_value as meta";
				$where[] = ' meta_name = "'.$r['meta_name'].'"';
			break;
			case 'rand' :
			case 'random' :
				$orderby = 'RAND()';
			break;
			case 'id' : 
			default :
				$orderby = $wp_dlm_db.'.id';
			break;
		}
	}
	
	if (strtolower($r['order'])!='desc' && strtolower($r['order'])!='asc') $r['order']='desc';
	
	// Process where clause
	if (sizeof($where)>0) $where = ' WHERE '.implode(' AND ', $where);
	else $where = '';
		
	$downloads = $wpdb->get_results( "SELECT $wp_dlm_db.*, $wp_dlm_db_cats.parent, $wp_dlm_db_cats.name 
		".$select."
		FROM $wp_dlm_db  
		LEFT JOIN $wp_dlm_db_cats ON $wp_dlm_db.category_id = $wp_dlm_db_cats.id 
		".$join."
		".$where."
		ORDER BY $orderby ".$r['order']."
		".$limitandoffset.";" );

	$return_downloads = array();

	// Process download variables
	foreach ($downloads as $dl) {
	
		switch ($downloadtype) {
			case ("Title") :
					$downloadlink = urlencode($dl->title);
			break;
			case ("Filename") :
					$downloadlink = $dl->filename;
					$link = explode("/",$downloadlink);
					$downloadlink = urlencode(end($link));
			break;
			default :
					$downloadlink = $dl->id;
			break;
		}
		
		$d = new download_object;

		// Can use size, url, title, version, hits, image, desc, category, category_id, id, date, memberonly
		$d->id = $dl->id;
		$d->title = $dl->title;
		$d->filename = $dl->filename;
		$d->mirrors = $dl->mirrors;
		$d->user = $dl->user;
		$d->hits = $dl->hits;
		$d->members = $dl->members;
		$d->postDate = $dl->postDate;
		$d->file_description = $dl->file_description;
		$d->dlversion = $dl->dlversion;		
		$d->size = wp_dlm_get_size($dl->filename);
		$d->url =  $downloadurl.$downloadlink;
		$d->version = $dl->dlversion;
		$d->image = get_option('wp_dlm_image_url');
		$d->desc = $dl->file_description;		
		$d->category = $dl->name;
		$d->category_id = $dl->category_id;
		$d->date = $dl->postDate;	
		$d->memberonly = $dl->members;
		
		$return_downloads[] = $d;
	}
	
	return $return_downloads;
		
}

################################################################################
// SHORTCODE FOR MULTIPLE DOWNLOADS
################################################################################		
		
function wp_dlm_shortcode_downloads( $atts ) {
		
	extract(shortcode_atts(array(
		'query' => 'limit=5&orderby=rand',
		'format' => 0,
		'autop' => false,
		'wrap' => 'ul',
		'before' => '<li>',
		'after' => '</li>'
	), $atts));
	
	$query = str_replace('&#038;','&', $query);
	
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_formats,$wp_dlm_db_cats, $def_format, $wp_dlm_db_meta;

	$dl = get_downloads($query);
	
	if (!empty($dl)) {
		
		// Handle Formats
		if (!$format && $def_format>0) {
			$format = wp_dlm_get_custom_format($def_format);
		} elseif ($format>0 && is_numeric($format) ) {
			$format = wp_dlm_get_custom_format($format);
		} else {
			// Format is set!
			$format = html_entity_decode($format);
		}	
		if (empty($format) || $format=='0') {
			$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").'", ""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits})</a>';		
			
		}
		
		$format = str_replace('\\"',"'",$format);
				
		foreach ($dl as $d) {
					
			$fpatts = array('{url}', '{id}', '{version}', '{title}', '{size}', '{hits}', '{image_url}', '{description}', '{description-autop}', '{category}', );
			$fsubs = array( $d->url , $d->id, $d->version , $d->title , $d->size , $d->hits , get_option('wp_dlm_image_url') , $d->desc , wpautop($d->desc) );
											
			// Category
			if ($d->category_id>0) {
				$fsubs[]  = $d->category;
				preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				$fpatts[] = $match[0];
				$fsubs[]  = $match[1].$d->category.$match[2];
			} else {
				$fsubs[]  = "";
				preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				$fpatts[] = $match[0];
				$fsubs[]  = "";
			}
			
			// Hits (special) {hits, none, one, many)
			preg_match("/{hits,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			$fpatts[] = $match[0];
			if ( $d->hits == 1 ) 
			{
				$text = str_replace('%',$d->hits,$match[2]);
				$fsubs[]  = $text; 
			}
			elseif ( $d->hits > 1 ) 
			{
				$text = str_replace('%',$d->hits,$match[3]);
				$fsubs[]  = $text; 
			}
			else 
			{
				$text = str_replace('%',$d->hits,$match[1]);
				$fsubs[]  = $text; 
			}	
			
			// Version
			preg_match("/{version,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			$fpatts[] = $match[0];
			if ($d->version) $fsubs[]  = $match[1].$d->version.$match[2]; else $fsubs[]  = "";	
			
			// Date
			preg_match("/{date,\s*\"([^\"]*?)\"}/", $format, $match);
			$fpatts[] = $match[0];
			if ($d->postDate) $fsubs[] = date_i18n($match[1],strtotime($d->postDate)); else $fsubs[]  = "";						
			
			// Other
			preg_match("/{description,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			$fpatts[] = $match[0];
			if ($d->desc) $fsubs[]  = $match[1].$d->desc.$match[2]; else $fsubs[]  = "";
			
			preg_match("/{description-autop,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			$fpatts[] = $match[0];
			if ($d->desc) $fsubs[]  = $match[1].wpautop($d->desc).$match[2]; else $fsubs[]  = "";
			
			// meta
			if (preg_match("/{meta-([^,]*?)}/", $format, $match)) {
				$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wp_dlm_db_meta WHERE download_id = %s" , $d->id ) );
				$meta_names = array();
				foreach($meta_data as $meta) {
					$fpatts[] = "{meta-".$meta->meta_name."}";
					$fsubs[] = $meta->meta_value;
					$fpatts[] = "{meta-autop-".$meta->meta_name."}";
					$fsubs[] = wpautop($meta->meta_value);
					$meta_names[] = $meta->meta_name;
				}
				// Blank Meta
				$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wp_dlm_db_meta WHERE meta_name NOT IN ( %s )" , implode(',', $meta_names) ) );
				foreach($meta_data as $meta) {
					$fpatts[] = "{meta-".$meta->meta_name."}";
					$fsubs[] = '';
					$fpatts[] = "{meta-autop-".$meta->meta_name."}";
					$fsubs[] = '';
				}
			}
			
			$output .= html_entity_decode($before).str_replace( $fpatts , $fsubs , $format ).html_entity_decode($after);

   		} 
	
	} else $output = '['.__("No Downloads found","wp-download_monitor").']';	
	
	if ($wrap=='ul') {
		$output = '<ul class="dlm_download_list">'.$output.'</ul>';
	}
	
	if ($autop) return wpautop($output);
	return $output;

}
add_shortcode('downloads', 'wp_dlm_shortcode_downloads');


################################################################################
// LEGACY TEMPLATE TAGS
################################################################################

function wp_dlm_show_downloads($mode = 1,$no = 5) {
	switch ($mode) {
		case 1 :
			$dl = get_downloads('limit='.$no.'&orderby=hits&order=desc');
		break;
		case 2 :
			$dl = get_downloads('limit='.$no.'&orderby=date&order=desc');
		break;
		case 3 :
			$dl = get_downloads('limit='.$no.'&orderby=random&order=desc');
		break;
	}
	if (!empty($dl)) {
		echo '<ul class="downloadList">';
		foreach($dl as $d) {
			$date = date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->date));
			switch ($mode) {
				case (1) :
				case (3) :
					echo '<li><a href="'.$d->url.'" title="'.__('Version',"wp-download_monitor").' '.$d->version.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a></li>';
				break;
				case (2) :
					echo '<li><a href="'.$d->url.'" title="'.__('Version',"wp-download_monitor").' '.$d->version.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" >'.$d->title.' <span>('. date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->date)).')</span></a></li>';
				break;
			}
		}
		echo '</ul>';
	}
}

function wp_dlm_all() {
	
	global $wpdb,$wp_dlm_root,$allowed_extentions,$max_upload_size,$wp_dlm_db;
	
	$dl = get_downloads('limit=&orderby=title&order=asc');		
	
	if (!empty($dl)) {
		$retval = '<ul class="downloadList">';
		foreach($dl as $d) {
			$retval .= '<li><a href="'.$d->url.'" title="'.__('Version',"wp-download_monitor").' '.$d->version.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").' - '.__('Added',"wp-download_monitor").' '.date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->date)).'" >'.$d->title.' ('.$d->hits.')</a></li>';
		}
		$retval .='</ul>';
	}
	
	return $retval;
}

function wp_dlm_advanced() {
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_cats,$downloadurl,$dlm_url,$downloadtype;
	// Get post data
	$showing = (int) $_POST['show_downloads'];
	if ($showing==0 || $showing=="") {
		$dl = get_downloads('limit=10&orderby=hits&order=desc');
	} else {
		$dl = get_downloads('limit=&orderby=title&order=asc&category='.$showing.'');
	}
	// Output selector box
	$retval = '<div class="download-box"><form method="post" action="#">
		<select name="show_downloads">
			<option value="0">'.__('Most Popular Downloads',"wp-download_monitor").'</option>
			<optgroup label="'.__('Categories',"wp-download_monitor").'">';
	// Echo categories;	
	$cats = $wpdb->get_results("SELECT * FROM $wp_dlm_db_cats WHERE parent=0 ORDER BY id;");
	if (!empty($cats)) {
		foreach ( $cats as $c ) {
			$retval .= '<option ';
			if ($showing==$c->id) $retval .= 'selected="selected"';
			$retval .= 'value="'.$c->id.'">'.$c->name.'</option>';
			$retval .= get_option_children_cats($c->id, "$c->name &mdash; ", $showing, 0);
		}
	} 
	$retval .= '</optgroup></select> <input type="submit" value="Go" /></form>';
	
	if (!empty($dl)) {
		$retval .= '<ul class="download-list">';
		foreach($dl as $d) {
			$retval .= '<li><a href="'.$d->url.'" title="'.__('Version',"wp-download_monitor").' '.$d->version.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").' - '.__('Added',"wp-download_monitor").' '.date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->date)).'" >'.$d->title.' ('.$d->hits.')</a></li>';
		}
		$retval .='</ul>';
	} else $retval .='<p>'.__('No Downloads Found',"wp-download_monitor").'</p>';
	$retval .= "</div>";
	return $retval;
}

function wp_dlm_parse_downloads_all($data) {
	if (substr_count($data,"[#show_downloads]")) {
		$data = str_replace("[#show_downloads]",wp_dlm_all(), $data);
	} 
	if (substr_count($data,"[#advanced_downloads]")) {
		$data = str_replace("[#advanced_downloads]",wp_dlm_advanced(), $data);
	}
	return $data;
} 


################################################################################
//	Gap filler for dates/stats
################################################################################

if (!function_exists('dlm_fill_date_gaps')) {
	function dlm_fill_date_gaps($prev, $date, $gapcalc, $dateformat) {
		global $wp_dlm_root;

		$string = array();
		$loop = 0;

		while ( $date>$prev ) :									
			
			$date = strtotime($gapcalc, $date );
			
			$string[] = '<tr>			
				<td style="width:25%;">'.date_i18n($dateformat, $date ).'</td>
				<td class="value"><img src="'.$wp_dlm_root.'img/bar.png" alt="" height="16" width="0%" />0</td>
			</tr>';									
			$loop++;
		endwhile;
		
		return implode('',array_reverse($string));
	}
}

################################################################################
// Dashboard widgets
################################################################################

// Only for wordpress 2.5 and above
if ($wp_db_version > 6124) {
	
	function dlm_download_stats_widget() {
		global $wp_dlm_db,$wpdb,$wp_dlm_db_stats, $wp_dlm_root;			
				
		// select all downloads 	
			$downloads = $wpdb->get_results("SELECT * FROM $wp_dlm_db ORDER BY id;");
		
		// Get stats for download
		if ($_REQUEST['download_stats_id']>0) 
			$d = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wp_dlm_db WHERE id = %s LIMIT 1;", $_REQUEST['download_stats_id'] ));
		else 
			$d = 0;	
			
		if ($_REQUEST['show_download_stats']=='monthly')
			$stattype = 'monthly';
		else
			$stattype = 'weekly';
			
		// Get post/get data
		$period = $_GET['download_stats_period'];
		if (!$period || !is_numeric($period)) 
			$period = '-1';
		else 
			$period = $period-1;
		
		$mindate = $wpdb->get_var( $wpdb->prepare("SELECT MIN(date) FROM $wp_dlm_db_stats;", $d->id));
		
		if ($stattype=='weekly') {
		
			if ($period<-1) 
				$maxdate = strtotime(($period+1).' week');
			else 
				$maxdate = strtotime(date('Y-m-d'));

			// get stats
			$max = $wpdb->get_var( $wpdb->prepare("SELECT MAX(hits) FROM $wp_dlm_db_stats WHERE download_id = %s AND date>=%s AND date<=%s;", $d->id, date('Y-m-d', strtotime("".$period." week") ), date('Y-m-d', strtotime("".($period+1)." week") ) ));						
		 												
			$stats = $wpdb->get_results( $wpdb->prepare("SELECT *, hits as thehits FROM $wp_dlm_db_stats WHERE download_id = %s AND date>=%s AND date<=%s ORDER BY date ASC LIMIT 7;", $d->id, date('Y-m-d', strtotime("".$period." week") ), date('Y-m-d', strtotime("".($period+1)." week") ) ));
				
			$prev = strtotime(''.$period.' week');
			
			$prevcalc = '+1 day';
			$gapcalc = '-1 day';	
			
			$dateformat = __('D j M',"wp-download_monitor");
			
			$previous_text = '&laquo; '.__('Previous Week',"wp-download_monitor").'';
			$this_text = ''.__('This Week',"wp-download_monitor").'';
			$next_text = ''.__('Next Week',"wp-download_monitor").' &raquo;';
		
		} elseif ($stattype=='monthly') {
		
			$monthperiod = $period*6;
		
			if ($period<-1) 
				$maxdate = strtotime(($monthperiod+6).' month');
			else 
				$maxdate = strtotime(date('Y-m-d'));
				
			// get stats
			$max = $wpdb->get_var( $wpdb->prepare("SELECT MAX(t1.thehits) FROM (SELECT SUM(hits) AS thehits FROM $wp_dlm_db_stats WHERE download_id = %s AND date>=%s AND date<=%s group by month(date)) AS t1;", $d->id, date('Y-m-d', strtotime("".$monthperiod." month") ), date('Y-m-d', strtotime("".($monthperiod+6)." month") ) ));						
		 												
			$stats = $wpdb->get_results( $wpdb->prepare("SELECT *, SUM(hits) as thehits FROM $wp_dlm_db_stats WHERE download_id = %s AND date>=%s AND date<=%s group by month(date) ORDER BY date ASC;", $d->id, date('Y-m-d', strtotime("".$monthperiod." month") ), date('Y-m-d', strtotime("".($monthperiod+6)." month") ) ));
				
			$prev = strtotime(''.$monthperiod.' month');
			
			$prevcalc = '+1 month';	
			$gapcalc = '-1 month';	
			
			$dateformat = __('F Y',"wp-download_monitor");
			
			$previous_text = '&laquo; '.__('Previous 6 months',"wp-download_monitor").'';
			$this_text = ''.__('Last 6 months',"wp-download_monitor").'';
			$next_text = ''.__('Next 6 months',"wp-download_monitor").' &raquo;';
		
		}		

		if (!empty($downloads)) {				

			// Output download select form
			echo '<form action="" method="post" style="margin-bottom:8px"><select name="show_download_stats">';
				echo '<option ';
				if ($_REQUEST['show_download_stats']=='weekly') echo 'selected="selected" '; 
				echo 'value="weekly">'.__('Weekly',"wp-download_monitor").'</option>';
				echo '<option ';
				if ($_REQUEST['show_download_stats']=='monthly') echo 'selected="selected" '; 
				echo 'value="monthly">'.__('Monthly',"wp-download_monitor").'</option>';
			echo '</select><select name="download_stats_id" style="width:50%;"><option value="">'.__('Select a download',"wp-download_monitor").'</option>';
				foreach( $downloads as $download )
				{
					echo '<option ';
					if ($_REQUEST['download_stats_id']==$download->id) echo 'selected="selected" '; 
					echo 'value="'.$download->id.'">'.$download->id.' - '.$download->title.'</option>';
				}
			echo '</select><input type="submit" value="'.__('Show',"wp-download_monitor").'" class="button" /></form>';
			
			if ($d) {
			
			echo '<div style="text-align:center;overflow:hidden">';
			
			if (strtotime($period.' week')>strtotime($mindate))
				echo '<a style="float:left" href="?download_stats_period='.($period).'&download_stats_id='.$d->id.'&show_download_stats='.$stattype.'">'.$previous_text.'</a>';
			
			if ($period<-1)
				echo '<a style="float:right" href="?download_stats_period='.($period+2).'&download_stats_id='.$d->id.'&show_download_stats='.$stattype.'">'.$next_text.'</a>';
				
			echo '<a style="margin:0 auto; width:100px; display:block" href="?download_stats_id='.$d->id.'&show_download_stats='.$stattype.'">'.$this_text.'</a>';
			
			echo '</div>';				
			echo '<div style="clear:both;margin-bottom:8px"></div>';
			
			
					
		?>
		<table class="download_chart" summary="<?php _e('Downloads per day for',"wp-download_monitor"); ?> <?php echo $d->title ?>" cellpadding="0" cellspacing="0">
			<tbody>
				<tr>
					<th scope="col"><span class="auraltext"><?php _e('Day',"wp-download_monitor"); ?></span> </th>
					<th scope="col"><span class="auraltext"><?php _e('Number of downloads',"wp-download_monitor"); ?></span> </th>
				</tr>
				<?php					
	
					if ($stats) {
					
						$loop = 1;
						
						foreach ($stats as $stat) {						
							$hits = $stat->thehits;
							$date = strtotime($stat->date);
							
							$width = ($hits / $max * 100) - 10;
							
							// Fill in gaps
							echo dlm_fill_date_gaps($prev, $date, $gapcalc, $dateformat);
							
							$prev = strtotime($prevcalc, $date);							
							
							echo '
							<tr>			
								<td style="width:25%;">'.date_i18n($dateformat,$date).'</td>
								<td class="value"><img src="'.$wp_dlm_root.'img/bar.png" alt="" height="16" width="'.$width.'%" />'.$hits.'</td>
							</tr>
							';
							$loop++;
						}
						
					} 
					echo dlm_fill_date_gaps($prev, $maxdate, $gapcalc, $dateformat);
				?>						
		</tbody></table>
		<?php
			}
		
		} else echo '<p>'.__('None Found',"wp-download_monitor").'</p>';
	}
	
	function dlm_download_top_widget() {
		global $wp_dlm_db,$wpdb,$wp_dlm_db_stats, $wp_dlm_root;			
						
		$downloads = $wpdb->get_results( "SELECT * FROM $wp_dlm_db ORDER BY hits DESC LIMIT 5;" );			
					
		?>
		<table class="download_chart" style="margin-bottom:0" summary="<?php _e('Most Downloaded',"wp-download_monitor"); ?>" cellpadding="0" cellspacing="0">
			<tbody>
				<tr>
					<th scope="col"><span class="auraltext"><?php _e('Day',"wp-download_monitor"); ?></span> </th>
					<th scope="col"><span class="auraltext"><?php _e('Number of downloads',"wp-download_monitor"); ?></span> </th>
				</tr>
				<?php
					// get stats
					$max = $wpdb->get_var( "SELECT MAX(hits) FROM $wp_dlm_db");
					$first = 'first';						
					$loop = 1;
					$size = sizeof($downloads);
					$last = "";
					if ($downloads && $max>0) {
						foreach ($downloads as $d) {
							$hits = $d->hits;
							$date = $d->date;
							$width = ($hits / $max * 100) - 10;
							if ($loop==$size) $last = 'last';
							echo '
							<tr>			
								<td class="'.$first.'" style="width:25%;">'.$d->title.'</td>
								<td class="value '.$first.' '.$last.'"><img src="'.$wp_dlm_root.'img/bar.png" alt="" height="16" width="'.$width.'%" />'.$hits.'</td>
							</tr>
							';
							$first = "";
							$loop++;
						}
					} else {
						echo '<tr><td class="first last" style="border-right:1px solid #e5e5e5" colspan="2">'.__('No stats yet',"wp-download_monitor").'</td></tr>';
					}
				?>						
		</tbody></table>
		<?php
	}
	
	// Different handling if supported (2.7 and 2.8)
	//if (function_exists('wp_add_dashboard_widget')) {
	if ($wp_db_version > 8644) {
		
		function dlm_download_stats_widget_setup() {
			wp_add_dashboard_widget( 'dlm_download_stats_widget', __( 'Download Stats' ), 'dlm_download_stats_widget' );
			wp_add_dashboard_widget( 'dlm_download_top_widget', __( 'Top 5 Downloads' ), 'dlm_download_top_widget' );
		}
		add_action('wp_dashboard_setup', 'dlm_download_stats_widget_setup');
		
	} else {
	
		// Old Method using Classes	
		class wp_dlm_dash {
		
			// Class initialization
			function wp_dlm_dash() {
				// Add to dashboard
				add_action( 'wp_dashboard_setup', array(&$this, 'register_widget') );
				add_filter( 'wp_dashboard_widgets', array(&$this, 'add_widget') );
			}
			// Register the widget for dashboard use
			function register_widget() {
				global $wp_db_version;
				$adminpage = 'admin.php';
				
				wp_register_sidebar_widget( 'download_monitor_dash', __( 'Download Stats', 'wp-download_monitor' ), array(&$this, 'widget') );
			}
			// Insert into dashboard
			function add_widget( $widgets ) {
				global $wp_registered_widgets;
				if ( !isset($wp_registered_widgets['download_monitor_dash']) ) return $widgets;
				array_splice( $widgets, 2, 0, 'download_monitor_dash' );
				return $widgets;
			}
			// Output the widget
			function widget( $args ) {
				if (is_array($args)) extract( $args, EXTR_SKIP );
				echo $before_widget;
				echo $before_title;
				echo $widget_name;
				echo $after_title;
				dlm_download_stats_widget();							
				echo $after_widget;
			}
		}
		add_action( 'plugins_loaded', create_function( '', 'global $wp_dlm_dash; $wp_dlm_dash = new wp_dlm_dash();' ) );
		
		class wp_dlm_dash2 {
		
			// Class initialization
			function wp_dlm_dash2() {
				// Add to dashboard
				add_action( 'wp_dashboard_setup', array(&$this, 'register_widget') );
				add_filter( 'wp_dashboard_widgets', array(&$this, 'add_widget') );
			}
			// Register the widget for dashboard use
			function register_widget() {
				global $wp_db_version;
				$adminpage = 'admin.php';
				wp_register_sidebar_widget( 'download_monitor_dash2', __( 'Top 5 Downloads', 'wp-download_monitor' ), array(&$this, 'widget') );
			}
			// Insert into dashboard
			function add_widget( $widgets ) {
				global $wp_registered_widgets;
				if ( !isset($wp_registered_widgets['download_monitor_dash2']) ) return $widgets;
				array_splice( $widgets, 2, 0, 'download_monitor_dash2' );
				return $widgets;
			}
			// Output the widget
			function widget( $args ) {
				if (is_array($args)) extract( $args, EXTR_SKIP );
				echo $before_widget;
				echo $before_title;
				echo $widget_name;
				echo $after_title;
				dlm_download_top_widget();								
				echo $after_widget;
			}
		}
		add_action( 'plugins_loaded', create_function( '', 'global $wp_dlm_dash2; $wp_dlm_dash2 = new wp_dlm_dash2();' ) );
	}
}

################################################################################
// Hooks
################################################################################

function wp_dlm_init_hooks() {
	global $wpdb,$wp_dlm_db,$wp_dlm_db_formats,$wp_dlm_db_cats;
			
	$wp_dlm_db_exists = false;
	
	// Check tables exist
	$tables = $wpdb->get_results("show tables;");
	foreach ( $tables as $table )
	{
		foreach ( $table as $value )
		{
		  if ( strtolower($value) ==  strtolower($wp_dlm_db) ) $wp_dlm_db_exists = true;
		}
	}
	
	if ($wp_dlm_db_exists==true) {
		add_filter('the_content', 'wp_dlm_parse_downloads',1); 
		add_filter('the_excerpt', 'wp_dlm_parse_downloads',1);
		add_filter('the_meta_key', 'wp_dlm_parse_downloads',1);
		add_filter('widget_text', 'wp_dlm_parse_downloads',1);
		add_filter('widget_title', 'wp_dlm_parse_downloads',1);
		add_filter('the_content', 'wp_dlm_parse_downloads_all',1);
		add_filter('admin_head', 'wp_dlm_ins_button');
		add_action('media_buttons', 'wp_dlm_add_media_button', 20);
		
		add_filter('the_excerpt', 'do_shortcode',11);
		add_filter('the_meta_key', 'do_shortcode',11);
		add_filter('widget_text', 'do_shortcode',11);
		add_filter('widget_title', 'do_shortcode',11);
	}
}
add_action('init','wp_dlm_init_hooks',1);

################################################################################
// Addons
################################################################################

if (!function_exists('wp_dlmp_styles')) include('page-addon/download-monitor-page-addon.php');
?>