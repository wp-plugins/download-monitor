<?php
/*
Plugin Name: Wordpress Download Monitor
Plugin URI: http://wordpress.org/extend/plugins/download-monitor/
Description: Manage downloads on your site, view and show hits, and output in posts. If you are upgrading Download Monitor it is a good idea to <strong>back-up your database</strong> first just in case. You may need to re-save your permalink settings after upgrading if your downloads stop working.
Version: 3.3.3.5
Author: Mike Jolley
Author URI: http://blue-anvil.com
*/

/*  Copyright 2010	Michael Jolley  (email : jolley.small.at.googlemail.com)

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

	global $wp_db_version, $wpdb, $table_prefix, $dlm_build, $wp_dlm_root, $wp_dlm_image_url, $wp_dlm_db, $wp_dlm_db_taxonomies, $wp_dlm_db_relationships, $wp_dlm_db_formats, $wp_dlm_db_stats, $wp_dlm_db_log, $wp_dlm_db_meta, $def_format, $dlm_url, $downloadtype, $downloadurl, $wp_dlm_db_exists, $meta_data, $download_taxonomies, $download_formats, $download_formats_array, $download_formats_names_array, $download_data, $download_data_array;
	
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
	
	$dlm_build="20100207";
	$wp_dlm_root = WP_PLUGIN_URL."/download-monitor/";
	$wp_dlm_image_url 	= get_option('wp_dlm_image_url');
	
	$wp_dlm_db = $table_prefix."download_monitor_files";
	$wp_dlm_db_taxonomies = $table_prefix."download_monitor_taxonomies";
	$wp_dlm_db_relationships = $table_prefix."download_monitor_relationships";
	$wp_dlm_db_formats = $table_prefix."download_monitor_formats";
	$wp_dlm_db_stats = $table_prefix."download_monitor_stats";
	$wp_dlm_db_log = $table_prefix."download_monitor_log";
	$wp_dlm_db_meta = $table_prefix."download_monitor_file_meta";
	
	$def_format = get_option('wp_dlm_default_format');
	$dlm_url = get_option('wp_dlm_url');
	$downloadtype = get_option('wp_dlm_type');
	if (empty($dlm_url)) 
		$downloadurl = $wp_dlm_root.'download.php?id=';
	else
		$downloadurl = get_bloginfo('url').'/'.$dlm_url;
		
	load_plugin_textdomain('wp-download_monitor', WP_PLUGIN_URL.'/download-monitor/languages/', 'download-monitor/languages/');
	
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
	
	$meta_data = '';
	$download_taxonomies = '';
	$download_formats = '';
	$download_formats_array = '';
	$download_formats_names_array = '';
	$download_data = '';
	$download_data_array = '';

################################################################################
// Includes
################################################################################

	include_once(WP_PLUGIN_DIR.'/download-monitor/functions.inc.php');				/* Various functions used throughout */
	include_once(WP_PLUGIN_DIR.'/download-monitor/init.php');						/* Inits the DB/Handles updates */
	include_once(WP_PLUGIN_DIR.'/download-monitor/legacy_shortcodes.php');			/* Old Style shortcodes */
	include_once(WP_PLUGIN_DIR.'/download-monitor/shortcodes.php');					/* New Style shortcodes */
	include_once(WP_PLUGIN_DIR.'/download-monitor/admin/admin.php');					/* Admin Interface */
	include_once(WP_PLUGIN_DIR.'/download-monitor/classes/downloadable_file.class.php');		/* Download Class */
	include_once(WP_PLUGIN_DIR.'/download-monitor/classes/download_taxonomies.class.php');		/* Taxonomy Class */ 
																					
################################################################################
// Set up menus within the wordpress admin sections
################################################################################

function wp_dlm_menu() { 
	global $wp_dlm_root;		
    add_menu_page(__('Downloads','wp-download_monitor'), __('Downloads','wp-download_monitor'), 'user_can_edit_downloads', __FILE__ , 'wp_dlm_admin', $wp_dlm_root.'/img/menu_icon.png');
	add_submenu_page(__FILE__, __('Edit','wp-download_monitor'),  __('Edit','wp-download_monitor') , 'user_can_edit_downloads', __FILE__ , 'wp_dlm_admin');
	add_submenu_page(__FILE__, __('Add New','wp-download_monitor') , __('Add New','wp-download_monitor') , 'user_can_add_new_download', 'dlm_addnew', 'dlm_addnew');
	add_submenu_page(__FILE__, __('Add Directory','wp-download_monitor') , __('Add Directory','wp-download_monitor') , 'user_can_add_exist_download', 'dlm_adddir', 'dlm_adddir');
	
	add_submenu_page(__FILE__, __('Categories','wp-download_monitor') , __('Categories','wp-download_monitor') , 'user_can_config_downloads', 'dlm_categories', 'wp_dlm_categories');
    
    add_submenu_page(__FILE__, __('Configuration','wp-download_monitor') , __('Configuration','wp-download_monitor') , 'user_can_config_downloads', 'dlm_config', 'wp_dlm_config');
    if (get_option('wp_dlm_log_downloads')=='yes') add_submenu_page(__FILE__, __('Log','wp-download_monitor') , __('Log','wp-download_monitor') , 'user_can_view_downloads_log', 'dlm_log', 'wp_dlm_log');
}
add_action('admin_menu', 'wp_dlm_menu');

################################################################################
// mod_rewrite rules
################################################################################

function wp_dlm_rewrite($rewrite) {
	global $dlm_url;
	$blog = get_bloginfo('wpurl');
	$base_url = get_bloginfo('url');

	$offset = '';

	// Options +FollowSymLinks
	
	// Borrowed from wp-super-cache
	$home_root = parse_url(get_bloginfo('url'));
	$home_root = isset( $home_root['path'] ) ? trailingslashit( $home_root['path'] ) : '/';
	
$rule = ('
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteBase '.$home_root.'
RewriteRule ^'.$offset.$dlm_url.'([^/]+)$ '.WP_PLUGIN_URL.'/download-monitor/download.php?id=$1 [L]
</IfModule>
');
	return $rule.$rewrite;	
}

################################################################################
// Hooks
################################################################################

if (!empty($dlm_url)) add_filter('mod_rewrite_rules', 'wp_dlm_rewrite');
	
function wp_dlm_init_hooks() {

	global $wp_db_version, $wpdb, $table_prefix, $dlm_build, $wp_dlm_root, $wp_dlm_image_url, $wp_dlm_db, $wp_dlm_db_taxonomies, $wp_dlm_db_relationships, $wp_dlm_db_formats, $wp_dlm_db_stats, $wp_dlm_db_log, $wp_dlm_db_meta, $def_format, $dlm_url, $downloadtype, $downloadurl, $wp_dlm_db_exists, $meta_data, $download_taxonomies, $download_formats, $download_formats_array, $download_formats_names_array, $download_data, $download_data_array;
	
	$wp_dlm_build = get_option('wp_dlm_build');
	
	if (is_admin()) :
		if (((isset($_GET['activate']) && $_GET['activate']==true)) || ($dlm_build != $wp_dlm_build)) {
			wp_dlm_init_or_upgrade();
		}
	endif;
	
	if (is_admin()) wp_enqueue_script('jquery-ui-sortable');
	
	if ($wp_dlm_db_exists==true) {

		################################################################################
		// Pre-fetch data before its needed to lessen queries later
		################################################################################
	
		$meta_data 				= $wpdb->get_results( "SELECT * FROM $wp_dlm_db_meta;" );
		$download_taxonomies	= new download_taxonomies();
		$download_formats 		= $wpdb->get_results( "SELECT * FROM $wp_dlm_db_formats;" );
		$download_formats_array = array();
		$download_formats_names_array = array();
		if ($download_formats) foreach ($download_formats as $format) {
			$download_formats_array[$format->id] = $format;
			$download_formats_names_array[] = $format->name;
		}
		$download_data 			= $wpdb->get_results( "SELECT * FROM $wp_dlm_db;" );
		$download_data_array = array();
		if ($download_data) foreach ($download_data as $download) {
			$download_data_array[$download->id] = $download;
		}
	
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
add_action('init','wp_dlm_init_hooks');

function wp_dlm_activate() {
	wp_dlm_init_or_upgrade();		
}
register_activation_hook( __FILE__, 'wp_dlm_activate' );

################################################################################
// Addons
################################################################################

if (!function_exists('wp_dlmp_styles')) include(WP_PLUGIN_DIR.'/download-monitor/page-addon/download-monitor-page-addon.php');
?>