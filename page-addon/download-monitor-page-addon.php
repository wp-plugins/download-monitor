<?php
/*  
	Wordpress Download Monitor Add-on: Download Page
	
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
// Vars and version
################################################################################

global $table_prefix;
$wp_dlmp_root = get_bloginfo('wpurl')."/wp-content/plugins/download-monitor/page-addon";
$wp_dlm_db = $table_prefix."DLM_DOWNLOADS";
$wp_dlm_db_cats = $table_prefix."DLM_CATS";
$wp_dlm_db_formats = $table_prefix."DLM_FORMATS";
$wp_dlm_db_stats = $table_prefix."DLM_STATS";
$wp_dlm_db_log = $table_prefix."DLM_LOG";
$wp_dlm_db_meta = $table_prefix."DLM_META";
$dlm_url = get_option('wp_dlm_url');

load_plugin_textdomain('wp-download_monitor', 'wp-content/plugins/download-monitor/', 'download-monitor/');
		
################################################################################
// Styles and Javascript
################################################################################

function wp_dlmp_styles() {
	if (function_exists('wp_register_style') && function_exists('wp_enqueue_style') ) {
		global $wp_dlmp_root;
	    $myStyleFile = $wp_dlmp_root.'/styles.css';
	    wp_register_style('wp_dlmp_styles', $myStyleFile);
	    wp_enqueue_style( 'wp_dlmp_styles');
    }
}
add_action('wp_print_styles', 'wp_dlmp_styles');

   																					
################################################################################
// DOWNLOAD PAGE OUTPUT FUNCTION
################################################################################

function wp_dlmp_output( $base_heading_level = '3', $pop_count = 4, $pop_cat_count = 4, $show_uncategorized = true, $per_page = 20, $format = '', $exclude = '' )
{
if (function_exists('get_downloads')) {

	// DEFINE STRINGS (translate these if needed)
	$popular_text 			= __('Popular Downloads','wp-download_monitor');
	$uncategorized 			= __('Other','wp-download_monitor');
	$search_text 			= __('Search Downloads: ','wp-download_monitor');
	$search_submit_text 	= __('Go','wp-download_monitor');
	$search_results_text 	= __('Results found for ','wp-download_monitor');
	$nonefound 				= __('No downloads were found.','wp-download_monitor');
	$notfound 				= __('No download found matching given ID.','wp-download_monitor');
	$main_page_back_text	= __('Downloads','wp-download_monitor');
	$desc_heading 			= __('Description','wp-download_monitor');
	$version_text 			= __('Version','wp-download_monitor');
	$category_text 			= __('Categories','wp-download_monitor');
	$tags_text 				= __('Tags','wp-download_monitor');
	$hits_text 				= __('Downloaded','wp-download_monitor');
	$hits_text2 			= __(' time','wp-download_monitor');
	$hits_text2_p 			= __(' times','wp-download_monitor');
	$posted_text 			= __('Date posted','wp-download_monitor');
	$posted_text2 			= __('F j, Y','wp-download_monitor');
	$readmore_text 			= __('Read More','wp-download_monitor');
	$subcat_text 			= __('Sub-Categories:','wp-download_monitor');
	$sort_text 				= __('Sort by:','wp-download_monitor');
	$tags_text 				= __('Downloads tagged:','wp-download_monitor');
	// END DEFINE STRINGS

	global $wpdb, $wp_dlm_db, $wp_dlm_db_cats, $post, $wp_dlmp_root, $wp_dlm_db_meta;

	// Handle $exclude
	$exclude_array = array();
	if ($exclude) {
		$exclude_unclean = explode(',',$exclude);		
		foreach ($exclude_unclean as $e) {
			$e = trim($e);
			if (is_numeric($e)) $exclude_array[] = $e;
		}
	}
	if (sizeof($exclude_array) > 0) $exclude_query = ' AND '.$wp_dlm_db.'.id NOT IN ('.implode(',',$exclude_array).')';
	else $exclude_query="";
	
	// Handle Formats
	if (!$format && $def_format>0) {
		$format = wp_dlm_get_custom_format($def_format);
	} elseif ($format>0 && is_numeric($format) ) {
		$format = wp_dlm_get_custom_format($format);
	} else {
		$format = html_entity_decode($format);
	}	
	// Default is none set/no defaults
	if (empty($format) || $format=='0') {
		$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").'", ""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits})</a>';	
	}	
	
	wp_dlm_magic();
	
	// DOWNLOAD PAGE DATA FUNCTIONS
	if (!function_exists('wp_dlmp_append_url')) {
	function wp_dlmp_append_url( $append ) {
		global $post;
		$querystring = explode('?', get_permalink( $post->ID ));
		if ($querystring[1]) {
			$add = '&'.$append;
		} else {
			$add = '?'.$append;
		}
		return $querystring[0].$querystring[1].$add;
	}
	}
	if (!function_exists('get_parent_cats')) {
	function get_parent_cats($current_id, $show_arrow = false) {
		global $wp_dlm_db_cats,$wpdb;
		$names_array = array();
		$sql = sprintf("SELECT * FROM %s WHERE id=%s ORDER BY id LIMIT 1;",
			$wpdb->escape( $wp_dlm_db_cats ),
			$wpdb->escape( $current_id ));	
		$cat = $wpdb->get_row($sql);
		if (!empty($cat) ) {
		
			if ($show_arrow) $arrow = '&laquo;&nbsp;'; else $arrow="";
			
			$names_array[] = '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat->name))).'">'.$arrow.$cat->name.'</a>';
			
			if ( $cat->parent > 0 ) $return_array = get_parent_cats($cat->parent, $show_arrow);
		}
		if (is_array($return_array))	
			return array_merge($names_array,$return_array);
		else
			return $names_array;
	}
	}
	if (!function_exists('get_children_cats_ids')) {
	function get_children_cats_ids($current_id, $godeep = true) {
		global $wp_dlm_db_cats,$wpdb;
		
		$ids_array = array();
		$return_array = array();
		
		if ($current_id>0) {
		
			$sql = sprintf("SELECT * FROM %s WHERE parent=%s ORDER BY id;",
				$wpdb->escape( $wp_dlm_db_cats ),
				$wpdb->escape( $current_id ));	
			$cats = $wpdb->get_results($sql);
			
			if (!empty($cats) ) {
				foreach ($cats as $cat) {			
					$ids_array[] = $cat->id;			
					if ($godeep) $return_array = $return_array + get_children_cats_ids($cat->id, true);
				}
			}
			
		}
		if (sizeof($return_array)>0)	
			return array_merge($ids_array,$return_array);
		else
			return $ids_array;
	}
	}
	
	// Load cats and put into array
	$category_array = array();
	$category_array_name = array();
	$category_res = $wpdb->get_results( "
		SELECT $wp_dlm_db_cats.*, COUNT($wp_dlm_db.ID) as count
		FROM $wp_dlm_db_cats, $wp_dlm_db
		WHERE $wp_dlm_db.category_id = $wp_dlm_db_cats.id 
		$exclude_query 
		GROUP BY $wp_dlm_db_cats.id 
	" );
	if ($category_res) foreach($category_res as $c) {
		$thiscat = array();
		$thiscat['count'] = $c->count;
		$thiscat['id'] = $c->id;
		$thiscat['name'] = strtolower($c->name);
		$thiscat['parent'] = $c->parent;
		$thiscat['direct_children'] = get_children_cats_ids($thiscat['id'], false);
		$thiscat['children'] = get_children_cats_ids($thiscat['id']);
		$category_array[$c->id] = $thiscat;
		$category_array_name[strtolower($c->name)] = $c->id;
	}
	// Repeat for empty cats
	$category_res = $wpdb->get_results( "
		SELECT $wp_dlm_db_cats.* 
		FROM $wp_dlm_db_cats
		WHERE $wp_dlm_db_cats.id NOT IN ( SELECT category_id FROM $wp_dlm_db )
	" );
	if ($category_res) foreach($category_res as $c) {
		$thiscat = array();
		$thiscat['count'] = 0;
		$thiscat['id'] = $c->id;
		$thiscat['name'] = strtolower($c->name);
		$thiscat['parent'] = $c->parent;
		$thiscat['direct_children'] = get_children_cats_ids($thiscat['id'], false);
		$thiscat['children'] = get_children_cats_ids($thiscat['id']);
		$category_array[$c->id] = $thiscat;
		$category_array_name[strtolower($c->name)] = $c->id;
	}
	// End load cats
	
	// START PAGE OUTPUT
	$page = '';
	
	$page .= '<div id="download-page">
		<form id="download-page-search" action="" method="get">
			<p><label for="dlsearch">'.$search_text.'</label><input type="text" name="dlsearch" id="dlsearch" value="'.$_GET['dlsearch'].'" /> <input type="submit" value="'.$search_submit_text.'" /></p>
		</form>';
		
	if ($_GET['dlsearch']) {
		// Search View
		$page .= '<h'.$base_heading_level.'>'.$search_results_text.'<em>"'.$_GET['dlsearch'].'"</em> <small><a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></h'.$base_heading_level.'>';

		$orderby = '';
		// Sorting Options
			switch (trim(strtolower($_GET['sortby']))) {
				case 'hits' :
					$sort_hits = 'class="active"';
					$orderby = 'ORDER BY '.$wp_dlm_db.'.hits DESC';
				break;
				case 'date' :
					$sort_date = 'class="active"';
					$orderby = 'ORDER BY '.$wp_dlm_db.'.postDate DESC';
				break;
				default :
					$sort_title = 'class="active"';
					$orderby = 'ORDER BY '.$wp_dlm_db.'.title ASC';
				break;
			}					
			$sort_options = array('<a href="'.wp_dlmp_append_url('dlsearch='.urlencode($_GET['dlsearch']).'&sortby=title').'" '.$sort_title.'>Title</a>', '<a href="'.wp_dlmp_append_url('dlsearch='.urlencode($_GET['dlsearch']).'&sortby=hits').'" '.$sort_hits.'>Hits</a>', '<a href="'.wp_dlmp_append_url('dlsearch='.urlencode($_GET['dlsearch']).'&sortby=date').'" '.$sort_date.'>Date</a>');
			$page .= '<p class="sorting"><strong>'.$sort_text.'</strong> ';
			$page .= implode(' | ', $sort_options).'</p>';
		// End Sorting Options
		
		// Pagination Calc
			$paged_query = "";
			if(!isset($_GET['dlpage'])) $dlpage = 1; else $dlpage = $_GET['dlpage']; 
			$from = (($dlpage * $per_page) - $per_page); 
			$paged_query = 'LIMIT '.$from.','.$per_page.'';
			$total = $wpdb->get_var("SELECT COUNT($wp_dlm_db.id) 
				FROM $wp_dlm_db  
				LEFT JOIN $wp_dlm_db_cats ON $wp_dlm_db.category_id = $wp_dlm_db_cats.id 
				WHERE (title LIKE '%".$wpdb->escape($_REQUEST['dlsearch'])."%' OR filename LIKE '%".$wpdb->escape($_REQUEST['dlsearch'])."%') 
				$exclude_query
			;");
			$total_pages = ceil($total / $per_page);
		// End Pagination Calc

		$downloads = $wpdb->get_results( "SELECT $wp_dlm_db.* 
				FROM $wp_dlm_db  
				LEFT JOIN $wp_dlm_db_cats ON $wp_dlm_db.category_id = $wp_dlm_db_cats.id 
				WHERE (title LIKE '%".$wpdb->escape($_REQUEST['dlsearch'])."%' OR filename LIKE '%".$wpdb->escape($_REQUEST['dlsearch'])."%') 
				$exclude_query $orderby $paged_query;" );			
			
		if (!empty($downloads)) {
		    $page .= '<ul>';
		    foreach($downloads as $d) {
		        $page .= '<li>'.do_shortcode('[download id="'.$d->id.'" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]').'</li>';
		    }
		   $page .= '</ul>';
		   
			// Show Pagination				       
				if ($total_pages>1)  {
					$page .= '<ul class="pagination">';
				
					if($dlpage > 1){ 
						$prev = ($dlpage - 1); 
						$page .= "<li><a href=\"".wp_dlmp_append_url('dlsearch='.urlencode($_GET['dlsearch']).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$prev.'')."\">&laquo; ".__('Previous',"wp-download_monitor")."</a></li>"; 
					} else $page .= "<li><span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span></li>";
	
					for($i = 1; $i <= $total_pages; $i++){ 
						if(($dlpage) == $i){ 
							$page .= "<li><span class='page-numbers current'>$i</span></li>"; 
						} else { 
							$page .= "<li><a href=\"".wp_dlmp_append_url('dlsearch='.urlencode($_GET['dlsearch']).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$i.'')."\">$i</a></li>"; 
						} 
					} 
	
					if($dlpage < $total_pages){ 
						$next = ($dlpage + 1); 
						$page .= "<li><a href=\"".wp_dlmp_append_url('dlsearch='.urlencode($_GET['dlsearch']).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$next.'')."\">".__('Next',"wp-download_monitor")." &raquo;</a></li>"; 
					} else $page .= "<li><span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span></li>";
					
					$page .= '</ul>';
				}
			// End show pagination		   
		   
		} else $page .= '<p>'.$nonefound.'</p>';
		
	}
	elseif (isset($_GET['category'])) {
	
		// Single Category view
		$category = $wpdb->escape(trim(urldecode(strtolower($_GET['category']))));
		$downloads = "";	        
		$total_pages = "";
		$dlpage = "";
		
		if ($category==strtolower($uncategorized) && $show_uncategorized) {

			$count = $wpdb->get_var( "SELECT COUNT(id) FROM $wp_dlm_db WHERE category_id = 0 $exclude_query" );
							
			$page .= '<h'.$base_heading_level.'>'.ucwords($uncategorized).' ('.$count.') <small><a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></h'.$base_heading_level.'>';

			$orderby = '';
			// Sorting Options
				switch (trim(strtolower($_GET['sortby']))) {
					case 'hits' :
						$sort_hits = 'class="active"';
						$orderby = 'hits';
						$order = 'desc';
					break;
					case 'date' :
						$sort_date = 'class="active"';
						$orderby = 'postdate';
						$order = 'desc';
					break;
					default :
						$sort_title = 'class="active"';
						$orderby = 'title';
						$order = 'asc';
					break;
				}					
				$sort_options = array('<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'&sortby=title').'" '.$sort_title.'>Title</a>', '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'&sortby=hits').'" '.$sort_hits.'>Hits</a>', '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'&sortby=date').'" '.$sort_date.'>Date</a>');
				$page .= '<p class="sorting"><strong>'.$sort_text.'</strong> ';
				$page .= implode(' | ', $sort_options).'</p>';
			// End Sorting Options
			
			// Pagination Calc
				$paged_query = "";
				if(!isset($_GET['dlpage'])) $dlpage = 1; else $dlpage = $_GET['dlpage']; 
				$from = (($dlpage * $per_page) - $per_page); 
				$paged_query = 'LIMIT '.$from.','.$per_page.'';
				$total = $wpdb->get_var("SELECT COUNT($wp_dlm_db.id) 
					FROM $wp_dlm_db 
					LEFT JOIN $wp_dlm_db_cats ON $wp_dlm_db.category_id = $wp_dlm_db_cats.id 
					WHERE $wp_dlm_db.category_id = 0 $exclude_query
				;");
				$total_pages = ceil($total / $per_page);
			// End Pagination Calc
			
			$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$per_page.'&orderby='.$orderby.'&order='.$order.'&offset='.$from.'&category=none" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');
			
		} else {
			
			$cat = $category_array[$category_array_name[$category]];
			
			if ($cat['id']>0) {
			
				$cat_breadcrumb = implode(' ', array_slice(get_parent_cats($cat['id'], true), 1) );
				
				// Count = children too
				$count = $cat['count'];
				foreach ($cat['children'] as $child) {
					$count += $category_array[$child]['count'];
				}			
				
				$page .= '<h'.$base_heading_level.'>'.ucwords($cat['name']).' ('.$count.') <small>'.$cat_breadcrumb.' <a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></h'.$base_heading_level.'>';
				
				$query_in = "";
				if (sizeof($cat['children']) > 0) {
					$query_in = $cat['id'].','.implode(',',$cat['children']);
					$page .= '<p class="subcats"><strong>'.$subcat_text.'</strong> ';
					$subcats = array();
					foreach ($cat['direct_children'] as $child_cat) {
						if ($category_array[$child_cat]['name']) {
							$scount = $category_array[$child_cat]['count'];
							foreach ($category_array[$child_cat]['children'] as $child) {
								$scount += $category_array[$child]['count'];
							}	
							$subcats[] = '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($category_array[$child_cat]['name']))).'">'.ucwords($category_array[$child_cat]['name']).' ('.$scount.')</a>';
						}					
					}
					$page .= implode(' | ', $subcats).'</p>';
				} else {
					$query_in = $cat['id'];
				}
				
				$orderby = '';
				// Sorting Options
					switch (trim(strtolower($_GET['sortby']))) {
						case 'hits' :
							$sort_hits = 'class="active"';
							$orderby = 'hits';
							$order = 'desc';
						break;
						case 'date' :
							$sort_date = 'class="active"';
							$orderby = 'postdate';
							$order = 'desc';
						break;
						default :
							$sort_title = 'class="active"';
							$orderby = 'title';
							$order = 'asc';
						break;
					}					
					$sort_options = array('<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat['name'])).'&sortby=title').'" '.$sort_title.'>Title</a>', '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat['name'])).'&sortby=hits').'" '.$sort_hits.'>Hits</a>', '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat['name'])).'&sortby=date').'" '.$sort_date.'>Date</a>');
					$page .= '<p class="sorting"><strong>'.$sort_text.'</strong> ';
					$page .= implode(' | ', $sort_options).'</p>';
				// End Sorting Options
				
				// Pagination Calc
					$paged_query = "";
					if(!isset($_GET['dlpage'])) $dlpage = 1; else $dlpage = $_GET['dlpage']; 
					$from = (($dlpage * $per_page) - $per_page); 
					$paged_query = 'LIMIT '.$from.','.$per_page.'';
					$total = $wpdb->get_var("SELECT COUNT($wp_dlm_db.id) 
						FROM $wp_dlm_db 
						LEFT JOIN $wp_dlm_db_cats ON $wp_dlm_db.category_id = $wp_dlm_db_cats.id 
						WHERE $wp_dlm_db.category_id IN (".$query_in.") $exclude_query
					;");
					$total_pages = ceil($total / $per_page);
				// End Pagination Calc

				$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$per_page.'&orderby='.$orderby.'&order='.$order.'&offset='.$from.'&category='.$cat['id'].'" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');			
			}
		}
		
		// Show Pagination				       
			if ($total_pages>1)  {
				$page .= '<ul class="pagination">';
			
				if($dlpage > 1){ 
					$prev = ($dlpage - 1); 
					$page .= "<li><a href=\"".wp_dlmp_append_url('category='.urlencode(strtolower($cat['name'])).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$prev.'')."\">&laquo; ".__('Previous',"wp-download_monitor")."</a></li>"; 
				} else $page .= "<li><span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span></li>";

				for($i = 1; $i <= $total_pages; $i++){ 
					if(($dlpage) == $i){ 
						$page .= "<li><span class='page-numbers current'>$i</span></li>"; 
					} else { 
						$page .= "<li><a href=\"".wp_dlmp_append_url('category='.urlencode(strtolower($cat['name'])).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$i.'')."\">$i</a></li>"; 
					} 
				} 

				if($dlpage < $total_pages){ 
					$next = ($dlpage + 1); 
					$page .= "<li><a href=\"".wp_dlmp_append_url('category='.urlencode(strtolower($cat['name'])).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$next.'')."\">".__('Next',"wp-download_monitor")." &raquo;</a></li>"; 
				} else $page .= "<li><span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span></li>";
				
				$page .= '</ul>';
			}
		// End show pagination
	
	}
	elseif (isset($_GET['dltag'])) {
		
		// Tag View
		$tag = urldecode(strtolower(trim($_GET['dltag'])));
			
		if ($tag) {
				
			$page .= '<h'.$base_heading_level.'>'.$tags_text.' '.$tag.' <small><a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></h'.$base_heading_level.'>';
								
			$orderby = '';
			// Sorting Options
				switch (trim(strtolower($_GET['sortby']))) {
					case 'hits' :
						$sort_hits = 'class="active"';
						$orderby = 'hits';
						$order = 'desc';
					break;
					case 'date' :
						$sort_date = 'class="active"';
						$orderby = 'postdate';
						$order = 'desc';
					break;
					default :
						$sort_title = 'class="active"';
						$orderby = 'title';
						$order = 'asc';
					break;
				}					
				$sort_options = array('<a href="'.wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby=title').'" '.$sort_title.'>Title</a>', '<a href="'.wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby=hits').'" '.$sort_hits.'>Hits</a>', '<a href="'.wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby=date').'" '.$sort_date.'>Date</a>');
				$page .= '<p class="sorting"><strong>'.$sort_text.'</strong> ';
				$page .= implode(' | ', $sort_options).'</p>';
			// End Sorting Options
				
			// Pagination Calc
				$paged_query = "";
				if(!isset($_GET['dlpage'])) $dlpage = 1; else $dlpage = $_GET['dlpage']; 
				$from = (($dlpage * $per_page) - $per_page); 
				$paged_query = 'LIMIT '.$from.','.$per_page.'';
								
				$tagged = $wpdb->get_results( "SELECT * FROM $wp_dlm_db INNER JOIN $wp_dlm_db_meta ON $wp_dlm_db.id = $wp_dlm_db_meta.download_id WHERE $wp_dlm_db_meta.meta_name = 'tags' $exclude_query;");
				$postIDS = array();
				foreach ($tagged as $t) {
					$my_tags = explode(',', $t->meta_value );
					$my_clean_tags = array();
					foreach ($my_tags as $mtag) {
						$my_clean_tags[] = trim(strtolower($mtag));
					}
					if (in_array(trim(strtolower($tag)), $my_clean_tags)) $postIDS[] = $t->download_id;
				}
				$tagswhere = ' '.$wp_dlm_db.'.id IN ('.implode(',',$postIDS).') ';
				
				$total = $wpdb->get_var("SELECT COUNT($wp_dlm_db.id) 
					FROM $wp_dlm_db 
					WHERE $tagswhere
				;");				
				$total_pages = ceil($total / $per_page);
			// End Pagination Calc

			$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$per_page.'&orderby='.$orderby.'&order='.$order.'&offset='.$from.'&tags='.$tag.'" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');			

			// Show Pagination				       
				if ($total_pages>1)  {
					$page .= '<ul class="pagination">';
				
					if($dlpage > 1){ 
						$prev = ($dlpage - 1); 
						$page .= "<li><a href=\"".wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$prev.'')."\">&laquo; ".__('Previous',"wp-download_monitor")."</a></li>"; 
					} else $page .= "<li><span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span></li>";
	
					for($i = 1; $i <= $total_pages; $i++){ 
						if(($dlpage) == $i){ 
							$page .= "<li><span class='page-numbers current'>$i</span></li>"; 
						} else { 
							$page .= "<li><a href=\"".wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$i.'')."\">$i</a></li>"; 
						} 
					} 
	
					if($dlpage < $total_pages){ 
						$next = ($dlpage + 1); 
						$page .= "<li><a href=\"".wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$next.'')."\">".__('Next',"wp-download_monitor")." &raquo;</a></li>"; 
					} else $page .= "<li><span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span></li>";
					
					$page .= '</ul>';
				}
			// End show pagination
			
		}		
	}
	elseif ($_GET['did'] && is_numeric($_GET['did']) && $_GET['did']>0) {
		
		// Single Download View
		$download = $wpdb->get_row( "SELECT $wp_dlm_db.*, $wp_dlm_db_cats.name as cat_name, $wp_dlm_db_cats.parent as cat_parent
			FROM $wp_dlm_db  
			LEFT JOIN $wp_dlm_db_cats ON $wp_dlm_db.category_id = $wp_dlm_db_cats.id 
			WHERE $wp_dlm_db.id = ".$wpdb->escape($_GET['did'])." $exclude_query LIMIT 1;" );
			
		if (!empty($download)) {
		
			// Load meta and put into array
			$meta = array();
			$meta_res = $wpdb->get_results( $wpdb->prepare( "SELECT meta_name, meta_value FROM $wp_dlm_db_meta WHERE download_id = %s" , $download->id ) );
			if ($meta_res) foreach($meta_res as $m) {
				$meta[$m->meta_name] = stripslashes($m->meta_value);
			}
			// End load meta
			
			if ($download->cat_name) $catname = ucwords($download->cat_name); 
				else $catname = ucwords($uncategorized);		
	        $date = date("jS M Y", strtotime($download->date));
	        if ($download->dlversion) $version = __('Version',"wp-download_monitor").' '.$download->dlversion; 
	        	else $version = '';
	        if ($download->file_description) $desc = do_shortcode(wptexturize(wpautop($download->file_description))); 
	        	else $desc = "";
	        if (!$thumbnail_url = $meta['thumbnail']) $thumbnail_url = $wp_dlmp_root.'/thumbnail.gif';
	        
	        // Gen category breadcrumb
	        $cat_breadcrumb = implode(' ', get_parent_cats($download->category_id, true));
	        if (empty($cat_breadcrumb)) $cat_breadcrumb = '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'').'">&laquo;&nbsp;'.ucwords($uncategorized).'</a>';
	        
	        $page .= '<div class="download-info single '.$alttext.'">	        		
	        		
		           	<div class="side-section">
		        		<p><img src="'.$thumbnail_url.'" class="download-image" alt="'.strip_tags($download->title).'" title="'.strip_tags($download->title).'" width="112" /></p>';
		        		
		    if (!$meta['hide_download_button']) $page .= '<p><a href="'.do_shortcode('[download id="'.$download->id.'" format="{url}"]').'" class="download-button"><img src="'.$wp_dlmp_root.'/downloadbutton.gif" alt="'.strip_tags($download->title).'" title="'.strip_tags($download->title).'" /></a></p>';
		       
		    if ($meta['post_id'] && is_numeric($meta['post_id']))		      		
		    	$page .= '<p><a href="'.get_permalink($meta['post_id']).'" class="more-button"><img src="'.$wp_dlmp_root.'/morebutton.gif" alt="'.$readmore_text.'" title="'.$readmore_text.'" /></a></p>';
		        		
		        		
		        		// Special additional content meta field
		        		$extra = $meta['side_content'];
		        		if ($extra) $page .= '<div class="extra">'.stripslashes($extra).'</div>';
		  
		    $page .= '
		        	</div>
		        	<div class="main-section">
		        		<h'.$base_heading_level.' class="download-info-heading">'.$download->title.' <small>'.$cat_breadcrumb.' <small><a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></small></h'.$base_heading_level.'>
	        	';
	        
	        // Show Meta Fields + download data
	        $custom_field_data = array();	        
	        
	        if ($download->dlversion) {
	        	$custom_field_data[] = array($version_text, $download->dlversion);
	        }
	        
	        $custom_field_data[] = array($posted_text, date($posted_text2, strtotime($download->postDate)));
	        
	        if (!$meta['hide_hits']) {
		        if ($download->hits==1) 
	 				$custom_field_data[] = array($hits_text, $download->hits.$hits_text2);
	 			else
	 				$custom_field_data[] = array($hits_text, $download->hits.$hits_text2_p);
			}
			
	        if ($download->cat_name) {
	        	$cats = array();
	        	$cats = get_parent_cats($download->category_id);
	        	$custom_field_data[] = array($category_text, implode(' &mdash; ', $cats));
	        } 	        
	        
	        $show_custom_fields = $meta['include_fields'];
	        if ($show_custom_fields) $show_custom_fields = explode(',',$show_custom_fields);	        
	        if (sizeof($show_custom_fields)>0) {
	        	// Get each custom field's value ready to output
	        	foreach ($show_custom_fields as $field) {
	        		$value = $meta[$field];
	        		if (!empty($value)) {
	        			// Special handling for tags
	        			if ($field=='tags') {
	        				$tags = explode(',', $value);
	        				$tags_after = array();
	        				foreach ($tags as $tag) {
	        					$tag = trim($tag);
	        					$tags_after[] = '<a href="'.wp_dlmp_append_url('dltag='.urlencode(strtolower($tag))).'">'.$tag.'</a>';
	        				}
	        				$custom_field_data[] = array(ucfirst(str_replace('-',' ',$field)), implode(', ',$tags_after));
	        			} else $custom_field_data[] = array(ucfirst(str_replace('-',' ',$field)), $value);
	        		}
	        	}
	        }
	            
	        if (sizeof($custom_field_data)>0) {
	        	// Output
	        	 $page .= '<table class="download-meta" cellspacing="0" style="width:100%"><thead><tr><th scope="col">Attribute</th><th style="text-align:right" scope="col">Value</th></tr></thead><tbody>';
	        	 	foreach($custom_field_data as $field) {
	        	 		 $page .= '<tr><th scope="row">'.$field[0].'</td><td style="text-align:right">'.$field[1].'</td></tr>';
	        	 	}
	        	 $page .= '</table>';
	        }
	        
	        // Show Description
	        if ($desc) {	
		        $page .= '<div class="info">
		        			<h'.($base_heading_level+1).' class="download-desc-heading">'.$desc_heading.'</h'.($base_heading_level+1).'>
		        			'.$desc.'
		        		</div>';
	        }
	        	
	        $page .= '</div>'; /* Close main-section */
	        
	        $page .= '</div>'; /* Close download-info */
			
		} else $page .= '<p>'.$notfound.'</p>';
		
	}
	else {
	
		// Front view
		$page .= '<div id="download-page-featured">
				<h'.$base_heading_level.'>'.$popular_text.'</h'.$base_heading_level.'><ul>';
				
				// Get top downloads
				$downloads = get_downloads('limit='.$pop_count.'&orderby=hits&order=desc&exclude='.implode(',',$exclude_array).'');
				if (!empty($downloads)) {
					$alt = -1;
				    foreach($downloads as $d) {
				    	if ($alt==1) $alttext = 'alternate'; else $alttext = '';
				        $date = date("jS M Y", strtotime($d->date));
				        if ($d->version) $version = __('Version',"wp-download_monitor").' '.$d->version; else $version = '';
				        if ($d->desc) $desc = do_shortcode(wptexturize(wpautop(current(explode('<!--more-->', $d->desc))))); else $desc = "";
				        $thumbnail_url = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wp_dlm_db_meta WHERE download_id = %s AND meta_name='thumbnail' LIMIT 1" , $d->id ) );
				        if (!$thumbnail_url) $thumbnail_url = $wp_dlmp_root.'/thumbnail.gif';
				        
				        $page .= '<li class="'.$alttext.'"><a href="'.wp_dlmp_append_url('did='.$d->id).'" title="'.$version.' '.__('Downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" ><img src="'.$thumbnail_url.'" class="download-thumbnail" alt="'.strip_tags($d->title).'" title="'.strip_tags($d->title).'"  /> <span>'.$d->title.'</span></a></li>';
				        
				        $alt = $alt*-1;
				    }
				}
		$page .= '</ul></div>';
		// End top
		
		// Begin cats
		$page .= '<div id="download-page-categories">';
			
		// Show categories
		if (sizeof($category_array)>0) {
			$alt = -1;
			foreach ($category_array as $cat) {
			
				if ($cat['parent']>0) continue;
			
				// Count = children too
				$count = $cat['count'];
				foreach ($cat['children'] as $child) {
					$count += $category_array[$child]['count'];
				}
			
				if ($alt==1) $alttext = 'alternate'; else $alttext = '';
				$page .= '<div class="category '.$alttext.'"><div class="inner">';
				$page .= '<h'.$base_heading_level.'><a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat['name']))).'">'.ucwords($cat['name']).' ('.$count.') &raquo;</a></h'.$base_heading_level.'>';
				
				$page .= '<ol>';
				$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$pop_cat_count.'&orderby=hits&order=desc&category='.$cat['id'].'" wrap="" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');
				$page .= '</ol>';
				 
				$page .= '</div></div>';
				$alt = $alt*-1;
			}
			// $show_uncategorized
			if ($show_uncategorized) {
			
				if ($alt==1) $alttext = 'alternate'; else $alttext = '';
				
				$count = $wpdb->get_var( "SELECT COUNT(id) FROM $wp_dlm_db WHERE category_id = 0 $exclude_query" );
				
				if ($count>0) {
					
					$page .= '<div class="category '.$alttext.'"><div class="inner">';
					$page .= '<h'.$base_heading_level.'><a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'').'">'.ucwords($uncategorized).' ('.$count.') &raquo;</a></h'.$base_heading_level.'>';
					
					$page .= '<ol>';
					$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$pop_cat_count.'&orderby=hits&order=desc&category=none" wrap="" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');
					$page .= '</ol>';
		
					$page .= '</div></div>';
					$alt = $alt*-1;
				
				}
			}
		}
			
		$page .= '</div>';
		// End cats
	}
	
	$page .= '</div>';
	
	return $page;
}
}

################################################################################
// SHORTCODE
################################################################################

function wp_dlmp_shortcode_download_page( $atts ) {

	extract(shortcode_atts(array(
		'base_heading_level' => '3',
		'pop_count' => '4',
		'pop_cat_count' => '4',
		'show_uncategorized' => '1',
		'per_page' => '20',
		'format' => '',
		'exclude' => ''
	), $atts));
	
	$output = wp_dlmp_output($base_heading_level, $pop_count, $pop_cat_count, $show_uncategorized, $per_page, $format, $exclude);
	return $output;

}
add_shortcode('download_page', 'wp_dlmp_shortcode_download_page');
?>