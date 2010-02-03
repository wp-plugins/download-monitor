<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - INIT
	
	Copyright 2006  Michael Jolley  (email : jolley.small.at.googlemail.com)

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
// NOTICES + UPGRADES/INSTALL
################################################################################
function dlm_activation_notice_upgrade(){
	
	// Borrowed notice code from all in one seo pack

	if(function_exists('admin_url')){
		echo '<div class="error fade"><p>'.__('Download Monitor 3.3 (and above) use a different database structure than in previous versions - this was to support multiple categories. You must update your database in order for this version to work - <strong>backup your database first</strong> then', "wp-download_monitor").' <a href="' . admin_url( 'admin.php?page=dlm_config&action=upgrade' ) . '">'.__('click here', "wp-download_monitor").'</a> '.__('to run the update script', "wp-download_monitor").'.</strong></p></div>';
	} else {
		echo '<div class="error fade"><p>'.__('Download Monitor 3.3 (and above) use a different database structure than in previous versions - this was to support multiple categories. You must update your database in order for this version to work - <strong>backup your database first</strong> then', "wp-download_monitor").' <a href="' . get_option('siteurl') . 'admin.php?page=dlm_config&action=upgrade' . '">'.__('click here', "wp-download_monitor").'</a> '.__('to run the update script', "wp-download_monitor").'.</strong></p></div>';
	}
	
}
global $dlm_build;
$wp_dlm_build = get_option('wp_dlm_build');
if ( !empty($wp_dlm_build) && $wp_dlm_build!=$dlm_build && ($wp_dlm_build<20100204 || !is_numeric($wp_dlm_build)) ) {
	
	if (!isset($_GET['action'])) {
		add_action( 'admin_notices', 'dlm_activation_notice_upgrade');
	}
	
}

// INIT ON ACTIVATE FOR FRESH INSTALLS/UPGRADES POST 3.3
function wp_dlm_activate() {

	global $wp_roles;
	$wp_roles->add_cap( 'administrator', 'user_can_config_downloads' );
	$wp_roles->add_cap( 'administrator', 'user_can_edit_downloads' );
	$wp_roles->add_cap( 'administrator', 'user_can_add_new_download' );
	$wp_roles->add_cap( 'administrator', 'user_can_add_exist_download' );
	$wp_roles->add_cap( 'administrator', 'user_can_view_downloads_log' );
	
	global $dlm_build;
	$wp_dlm_build = get_option('wp_dlm_build');
	if ( !empty($wp_dlm_build) && $wp_dlm_build!=$dlm_build && ($wp_dlm_build<20100204 || !is_numeric($wp_dlm_build)) ) {
		// THESE VERSIONS NEED A MANUAL BACKUP + UPGRADE
	} else {
		wp_dlm_update();
		wp_dlm_init();
	}
	
}
register_activation_hook( __FILE__, 'wp_dlm_activate' );

################################################################################
// HANDLE UPDATES
################################################################################

function wp_dlm_update() {

	global $dlm_build;

	add_option('wp_dlm_build', $dlm_build, 'Version of DLM plugin', 'no');

	if ( get_option('wp_dlm_build') != $dlm_build ) {
			
		// Update the build
		update_option('wp_dlm_build', $dlm_build);
		
	}
	
}

################################################################################
// Set up database
################################################################################
function wp_dlm_init() {
	
	global $wp_roles;

	add_option('wp_dlm_url', '', 'URL for download', 'no');	
	add_option('wp_dlm_type', 'ID', 'wp_dlm_type', 'no');
	add_option('wp_dlm_default_format', '0', 'wp_dlm_default_format', 'no');
	add_option('wp_dlm_does_not_exist','','no');
	add_option('wp_dlm_image_url',WP_PLUGIN_URL."/download-monitor/img/download.gif",'no');
	add_option('wp_dlm_log_downloads', 'yes', '', 'no');
	add_option('wp_dlm_file_browser_root', ABSPATH, 'no');
	
	$wp_roles->add_cap( 'administrator', 'user_can_config_downloads' );
	$wp_roles->add_cap( 'administrator', 'user_can_edit_downloads' );
	$wp_roles->add_cap( 'administrator', 'user_can_add_new_download' );
	$wp_roles->add_cap( 'administrator', 'user_can_add_exist_download' );
	$wp_roles->add_cap( 'administrator', 'user_can_view_downloads_log' );
	
 	global $table_prefix,$wp_dlm_db,$wp_dlm_db_taxonomies,$wp_dlm_db_formats,$wpdb,$wp_dlm_db_stats,$wp_dlm_db_log,$wp_dlm_db_meta,$wp_dlm_db_relationships;
 	
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
			`members` INT (1) NULL,
			`mirrors` LONGTEXT NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_taxonomies." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT, 
			`name`   	LONGTEXT  NOT NULL ,
			`parent`  	INT (12) UNSIGNED NOT NULL,
			`taxonomy`  VARCHAR (250)  NOT NULL ,
			`order`   	INT (12) UNSIGNED NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_relationships." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,   
			`taxonomy_id`   	INT UNSIGNED NOT NULL,
			`download_id`  	INT UNSIGNED NOT NULL,
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
// Upgrade/Clean functions
################################################################################

function wp_dlm_upgrade() {

	global $wpdb, $table_prefix,$wp_dlm_db,$wp_dlm_db_taxonomies,$wp_dlm_db_formats,$wpdb,$wp_dlm_db_stats,$wp_dlm_db_log,$wp_dlm_db_meta,$wp_dlm_db_relationships;
 	
 	$wpdb->hide_errors();

 	// Get Collation
	$collate = "";
	if($wpdb->supports_collation()) {
		if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
	} 	
	
	// old tables 
	$wp_dlm_db_old = $table_prefix."DLM_DOWNLOADS";
	$wp_dlm_db_taxonomies_old = $table_prefix."DLM_CATS";
	$wp_dlm_db_formats_old = $table_prefix."DLM_FORMATS";
	$wp_dlm_db_stats_old = $table_prefix."DLM_STATS";
	$wp_dlm_db_log_old = $table_prefix."DLM_LOG";
	$wp_dlm_db_meta_old = $table_prefix."DLM_META";
	
	// Rename old tables
	$wpdb->query("ALTER TABLE $wp_dlm_db_old RENAME $wp_dlm_db;");
	$wpdb->query("ALTER TABLE $wp_dlm_db_formats_old RENAME $wp_dlm_db_formats;");
	$wpdb->query("ALTER TABLE $wp_dlm_db_stats_old RENAME $wp_dlm_db_stats;");
	$wpdb->query("ALTER TABLE $wp_dlm_db_log_old RENAME $wp_dlm_db_log;");
	$wpdb->query("ALTER TABLE $wp_dlm_db_meta_old RENAME $wp_dlm_db_meta;");

	// Create new taxonomy table
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_taxonomies." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT, 
			`name`   	LONGTEXT  NOT NULL ,
			`parent`  	INT (12) UNSIGNED NOT NULL,
			`taxonomy`  VARCHAR (250)  NOT NULL ,
			`order`   	INT (12) UNSIGNED NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	// Create new taxonomy relationship
	$sql = "CREATE TABLE IF NOT EXISTS ".$wp_dlm_db_relationships." (				
			`id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,   
			`taxonomy_id`   	INT UNSIGNED NOT NULL,
			`download_id`  	INT UNSIGNED NOT NULL,
			PRIMARY KEY ( `id` )) $collate;";
	$result = $wpdb->query($sql);
	
	// GET OLD DATA	
	$values="";
	$values2="";
	$values3="";
	
	// Get cats from Downloads
	$query = sprintf("SELECT * from %s;",
		$wpdb->escape( $wp_dlm_db ));
	$result_d = $wpdb->get_results($query);
	
	if($result_d && $wpdb->num_rows>0) {
		
		foreach($result_d as $d) {
			$id=$d->id;
			$category_id=$d->category_id;						

			if ($category_id) {
				$values.='("'.$category_id.'", "'.$id.'"),';
			}
		}
		$values = substr_replace($values,"",-1);
	}
	
	// Cats -> Taxonomies
	$query = sprintf("SELECT * from %s;",
		$wpdb->escape( $wp_dlm_db_taxonomies_old ));
	$result_d = $wpdb->get_results($query);
	
	if($result_d && $wpdb->num_rows>0) {
		
		foreach($result_d as $d) {
			$id=$d->id;
			$name=$d->name;
			$parent=$d->parent;			
			$values2.='("'.$id.'", "'.$name.'", "'.$parent.'", "category"),';
		}
		$values2 = substr_replace($values2,"",-1);
	}
		
	// ADD DATA
	if (!empty($values)) {
		$query_ins = sprintf("INSERT INTO %s (taxonomy_id, download_id) VALUES %s;",
			$wpdb->escape( $wp_dlm_db_relationships ),
			$values);
		$wpdb->query($query_ins);
	}
	if (!empty($values2)) {
		$query_ins = sprintf("INSERT INTO %s (id, name, parent, taxonomy) VALUES %s;",
			$wpdb->escape( $wp_dlm_db_taxonomies ),
			$values2);
		$wpdb->query($query_ins);
	}	
	
	// Tags (in meta) -> Taxonomies
	$query = sprintf("SELECT * from %s;",
		$wpdb->escape( $wp_dlm_db_meta ));
	$result_d = $wpdb->get_results($query);
	
	if($result_d && $wpdb->num_rows>0) {
		
		foreach($result_d as $d) {
			$id=$d->id;
			$meta_name=$d->meta_name;
			$meta_value=$d->meta_value;	
			$download_id=$d->download_id;
			
			if ($meta_name=='tags') {
				$meta_values = explode(',',$meta_value);
				$meta_values = array_map('trim', $meta_values);
				foreach ($meta_values as $meta) {
					if ($meta) {
						// Insert
						$tag_id = $wpdb->get_var("SELECT id FROM $wp_dlm_db_taxonomies WHERE name='".$wpdb->escape($meta)."' AND taxonomy='tag';");
						if (!$tag_id) {
							$wpdb->query("INSERT INTO $wp_dlm_db_taxonomies (name, parent, taxonomy) VALUES ('".$wpdb->escape($meta)."', '0', 'tag');");
							$tag_id = $wpdb->insert_id;
						}						
						$values3.='("'.$tag_id.'", "'.$download_id.'"),';						
					}
				}				
			}		
		}
		$values3 = substr_replace($values3,"",-1);
	}
	
	if (!empty($values3)) {
		$query_ins = sprintf("INSERT INTO %s (taxonomy_id, download_id) VALUES %s;",
			$wpdb->escape( $wp_dlm_db_relationships ),
			$values3);
		$wpdb->query($query_ins);
	}
	
	wp_dlm_update();
	wp_dlm_init();

}

function wp_dlm_cleanup() {
	global $wpdb, $table_prefix;
	
	// old tables 
	$wp_dlm_db_old = $table_prefix."DLM_DOWNLOADS";
	$wp_dlm_db_taxonomies_old = $table_prefix."DLM_CATS";
	$wp_dlm_db_formats_old = $table_prefix."DLM_FORMATS";
	$wp_dlm_db_stats_old = $table_prefix."DLM_STATS";
	$wp_dlm_db_log_old = $table_prefix."DLM_LOG";
	$wp_dlm_db_meta_old = $table_prefix."DLM_META";
	
	// Drop
	$wpdb->query("DROP TABLE IF EXISTS $wp_dlm_db_old;");
	$wpdb->query("DROP TABLE IF EXISTS $wp_dlm_db_taxonomies_old;");
	$wpdb->query("DROP TABLE IF EXISTS $wp_dlm_db_formats_old;");
	$wpdb->query("DROP TABLE IF EXISTS $wp_dlm_db_stats_old;");
	$wpdb->query("DROP TABLE IF EXISTS $wp_dlm_db_log_old;");
	$wpdb->query("DROP TABLE IF EXISTS $wp_dlm_db_meta_old;");
}
?>