<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - SHORTCODES
	
	Copyright 2010  Michael Jolley  (email : jolley.small.at.googlemail.com)

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
// MAIN SINGLE SHORTCODE
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
	
		global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta, $download_data, $download_data_array;
	
		if ($id>0) {
		
			// Handle Formats
			global $download_formats_names_array;
			$format = trim($format);
			if (!$format && $def_format>0) {
				$format = wp_dlm_get_custom_format($def_format);
			} elseif ($format>0 && is_numeric($format) ) {
				$format = wp_dlm_get_custom_format($format);
			} else {
				if (isset($download_formats_names_array) && is_array($download_formats_names_array) && in_array($format,$download_formats_names_array)) {
					$format = wp_dlm_get_custom_format_by_name($format);
				} else {
					$format = html_entity_decode($format);
				}
			}	
			if (empty($format) || $format=='0') {
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").'", ""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits})</a>';	
				
			}
			
			$format = str_replace('\\"',"'",$format);
			
			// Get download info
			if (isset($download_data_array[$id])) $d = $download_data_array[$id]; else $d = '';	

			if ($d) {	
				
				$this_download = new downloadable_file($d, $format);
				
				$fpatts = $this_download->patts;
				
				$fsubs	= $this_download->subs;
	            					
				$output = str_replace( $fpatts , $fsubs , $format );
	   			
	   		} else $output = '[Download not found]';
		
		} else $output = '[Download id not defined]';
		
		wp_cache_set('download_'.$id.'_'.$format, $output);
	
	} else {
		$output = $cached_code;
	}
	
	if ($autop && $autop != "false") return wpautop(do_shortcode($output));
	
	return do_shortcode($output);

}
add_shortcode('download', 'wp_dlm_shortcode_download');

################################################################################
// SHORTCODE FOR MULTIPLE DOWNLOADS
################################################################################		
		
function wp_dlm_shortcode_downloads( $atts ) {
	
	extract(shortcode_atts(array(
		'query' => 'limit=5&orderby=rand',
		'format' => '0',
		'autop' => false,
		'wrap' => 'ul',
		'before' => '<li>',
		'after' => '</li>'
	), $atts));
	
	$query = str_replace('&#038;','&', $query);
	
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $wp_dlm_db_meta;

	$dl = get_downloads($query);
	
	$output = '';

	if (!empty($dl)) {		
		// Handle Formats
		global $download_formats_names_array;
		$format = trim($format);
		if (!$format && $def_format>0) {
			$format = wp_dlm_get_custom_format($def_format);
		} elseif ($format>0 && is_numeric($format) ) {
			$format = wp_dlm_get_custom_format($format);
		} else {
			if (isset($download_formats_names_array) && is_array($download_formats_names_array) && in_array($format,$download_formats_names_array)) {
				$format = wp_dlm_get_custom_format_by_name($format);
			} else {
				$format = html_entity_decode($format);
			}
		}	
		if (empty($format) || $format=='0') {
			$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").'", ""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits})</a>';		
			
		}
		
		$format = str_replace('\\"',"'",$format);

		foreach ($dl as $d) {
			
			$this_download = new downloadable_file($d, $format);
				
			$fpatts = $this_download->patts;
				
			$fsubs	= $this_download->subs;
						
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
// Main template tag to get multiple downloads
################################################################################

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
	
	global $wpdb,$wp_dlm_root, $wp_dlm_db, $wp_dlm_db_taxonomies, $wp_dlm_db_relationships, $wp_dlm_db_meta, $dlm_url, $downloadurl, $downloadtype, $download_taxonomies;
	
	$where = array();
	$join = '';
	$select = '';
	$limitandoffset = '';
	
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
			if ($r['digforcats']) $the_cats = array_merge($the_cats, $download_taxonomies->categories[$cat]->get_decendents());
			$the_cats[] = $cat;
		}
		$categories = implode(',',$the_cats);	

		$where[] = ' '.$wp_dlm_db.'.id IN ( SELECT download_id FROM '.$wp_dlm_db_relationships.' WHERE taxonomy_id IN ('.$categories.') ) ';
		
	} elseif ($r['category']=='none') {
		
		$where[] = ' '.$wp_dlm_db.'.id NOT IN ( 
				SELECT download_id FROM '.$wp_dlm_db_relationships.'
				LEFT JOIN '.$wp_dlm_db_taxonomies.' ON '.$wp_dlm_db_relationships.'.taxonomy_id = '.$wp_dlm_db_taxonomies.'.id
				WHERE '.$wp_dlm_db_taxonomies.'.taxonomy = "category"
			) ';
	
	} else $category = '';
	
	if ( ! empty($r['tags']) ) {
		$tags = explode(',', $r['tags']);
		$tags = array_map('wrap_tags', $tags);	
		
		$where[] = ' '.$wp_dlm_db.'.id IN ( 		
			SELECT download_id FROM '.$wp_dlm_db_relationships.' 
			LEFT JOIN '.$wp_dlm_db_taxonomies.' ON '.$wp_dlm_db_relationships.'.taxonomy_id = '.$wp_dlm_db_taxonomies.'.id
			WHERE '.$wp_dlm_db_taxonomies.'.name IN ('.implode(',',$tags).') 
		) ';
		
	} else $tags = '';
	
	if ( isset($vip) && $vip==1 ) {
	
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
		
	$downloads = $wpdb->get_results( "SELECT $wp_dlm_db.*  
		".$select."
		FROM $wp_dlm_db  
		".$join."
		".$where."
		ORDER BY $orderby ".$r['order']."
		".$limitandoffset.";" );
		
	$return_downloads = array();

	// Process download variables
	foreach ($downloads as $dl) {
		
		$d = new downloadable_file($dl);
		
		$return_downloads[] = $d;
	}
	
	return $return_downloads;
		
}

?>