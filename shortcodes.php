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
	
	$id = trim($id);
	
	if ($id>0 && is_numeric($id)) {
	
		$cached_code = wp_cache_get('download_'.$id.'_'.$format);

		if($cached_code == false) {
		
			global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;

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
			$d = $wpdb->get_row( "SELECT * FROM $wp_dlm_db WHERE id = ".$wpdb->escape($id).";" );
			if (isset($d) && !empty($d)) {
				
				$this_download = new downloadable_file($d, $format);
				
				$fpatts = $this_download->patts;
			
				$fsubs	= $this_download->subs;
		
			} 
			
			if ($fpatts && $fsubs) {
				$output = str_replace( $fpatts , $fsubs , $format );
			} else $output = '[Download not found]';

			wp_cache_set('download_'.$id.'_'.$format, $output);
		
		} else {
			$output = $cached_code;
		}
		
		if ($autop && $autop !== "false") return wpautop(do_shortcode($output));
	
	} else $output = '[Download id not defined]';
	
	return do_shortcode($output);

}
add_shortcode('download', 'wp_dlm_shortcode_download');

################################################################################
// SINGLE SHORTCODE that takes a format inside
################################################################################

function wp_dlm_shortcode_download_data( $atts, $content ) {

	extract(shortcode_atts(array(
		'id' => '0',
		'autop' => false
	), $atts));
	
	$output = '';
	
	$id = trim($id);
	
	if ($id>0 && is_numeric($id)) {
		
		global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;

		// Handle Format
		$format = html_entity_decode($content);
		
		// Untexturize content - adapted from wpuntexturize by Scott Reilly
		$codes = array('&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8242;', '&#8243;');
		$replacements = array("'", "'", '"', '"', "'", '"');
		
		$format = str_replace($codes, $replacements, $format);	
		
		// Get download info			
		$d = $wpdb->get_row( "SELECT * FROM $wp_dlm_db WHERE id = ".$wpdb->escape($id).";" );
		if (isset($d) && !empty($d)) {
			
			$this_download = new downloadable_file($d, $format);
			
			$fpatts = $this_download->patts;
		
			$fsubs	= $this_download->subs;
	
		} 
		
		if ($fpatts && $fsubs) {
			$output = str_replace( $fpatts , $fsubs , $format );
		} else $output = '[Download not found]';
		
		if ($autop && $autop !== "false") return wpautop(do_shortcode($output));
	
	} else $output = '[Download id not defined]';
	
	return do_shortcode(wptexturize($output));

}
add_shortcode('download_data', 'wp_dlm_shortcode_download_data');

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
			
			$d->prep_download_data($format);
			
			$fpatts = $d->patts;
				
			$fsubs	= $d->subs;
	
			$output .= html_entity_decode($before).str_replace( $fpatts , $fsubs , $format ).html_entity_decode($after);
			
   		} 
	
	} else $output = '['.__("No Downloads found","wp-download_monitor").']';	
	
	if ($wrap=='ul') {
		$output = '<ul class="dlm_download_list">'.$output.'</ul>';
	}
	
	if ($autop) return wpautop(do_shortcode($output));
	return do_shortcode($output);

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
		'exclude' => '',
		'include' => '',
		'author' => ''
	);
	
	$args = str_replace('&amp;','&',$args);

	$r = wp_parse_args( $args, $defaults );
	
	global $wpdb,$wp_dlm_root, $wp_dlm_db, $wp_dlm_db_taxonomies, $wp_dlm_db_relationships, $wp_dlm_db_meta, $dlm_url, $downloadurl, $downloadtype, $download_taxonomies, $download2taxonomy_array;
	
	$where = array();
	$in_ids = array();
	$not_in_ids = array();
	$join = '';
	$select = '';
	$limitandoffset = '';
	$filtering_ids = false;
	
	// Handle $exclude
	$exclude_array = array();
	if ( $r['exclude'] ) {
		$exclude_unclean = array_map('intval', explode(',',$r['exclude']));		
		$not_in_ids = array_merge($not_in_ids, $exclude_unclean);
	}
	
	// Handle $include
	$include_array = array();
	if ( $r['include'] ) {
		$include_unclean = array_map('intval', explode(',',$r['include']));
		$in_ids = array_merge($in_ids, $include_unclean);
		$filtering_ids = true;
	}
		
	if ( empty( $r['limit'] ) || !is_numeric($r['limit']) ) $r['limit'] = '';
		
	if ( !empty( $r['limit'] ) && (empty($r['offset']) || !is_numeric($r['offset'])) ) $r['offset'] = 0;
	elseif ( empty( $r['limit'] )) $r['offset'] = '';
	
	if ( !empty( $r['limit'] ) ) $limitandoffset = ' LIMIT '.$r['offset'].', '.$r['limit'].' ';
	
	if ( ! empty($r['category']) && $r['category']!=='none' ) {
		$filtering_ids = true;
		$categories = explode(',',$r['category']);
		$the_cats = array();
		// Traverse through categories to get sub-cats
		foreach ($categories as $cat) {
			if (isset($download_taxonomies->categories[$cat])) {	
				if ($r['digforcats']) $the_cats = array_merge($the_cats, $download_taxonomies->categories[$cat]->get_decendents());
				$the_cats[] = $cat;
			}
		}
		
		foreach ($download2taxonomy_array as $tid=>$tax_array) {
			if (sizeof(array_intersect($tax_array, $the_cats))>0) $in_ids[] = $tid;
		}		
	} elseif ($r['category']=='none') {
		
		$filtering_ids = true;
		
		$the_cats = array_keys($download_taxonomies->categories);
		
		foreach ($download2taxonomy_array as $tid=>$tax_array) {
			if (sizeof(array_intersect($tax_array, $the_cats))==0) $in_ids[] = $tid;
		}		
		
	} else $category = '';
	
	if ( ! empty($r['tags']) ) {
	
		$filtering_ids = true;
		
		$tags = explode(',', $r['tags']);
		
		$tag_ids = array();
		
		if ($download_taxonomies->tags && sizeof($download_taxonomies->tags) >0) foreach ($download_taxonomies->tags as $tag) {
			$tag->name;
			if (in_array($tag->name, $tags)) {
				// Include
				$tag_ids[] = $tag->id;
			}
		} 
		
		if (sizeof($tag_ids)>0) {
			foreach ($download2taxonomy_array as $tid=>$tax_array) {
				if (sizeof(array_intersect($tax_array, $tag_ids))>0) $in_ids[] = $tid;
			}
		}		
	} else $tags = '';
	
	// Handle Author
	if ( ! empty($r['author']) ) {
		$where[] = ' user = "'.$wpdb->escape($r['author']).'" ';
	}
	
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
				$orderby = "$wp_dlm_db_meta.meta_value";
				$join = " LEFT JOIN $wp_dlm_db_meta ON $wp_dlm_db.id = $wp_dlm_db_meta.download_id ";
				$select = "";
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
	
	if (strtolower($r['order'])!=='desc' && strtolower($r['order'])!=='asc') $r['order']='desc';
	
	if (sizeof($in_ids) > 0) {
		$in_ids = array_unique($in_ids);
		if (sizeof($not_in_ids) > 0) {
			$in_ids = array_diff($in_ids, $not_in_ids);
		}
		if (sizeof($in_ids) > 0) {
			$where[] = ' '.$wp_dlm_db.'.id IN ('.implode(',',$in_ids).') ';
		}
	} else {
		if ($filtering_ids==true) {
			// We are filtering ids and there are none set so return no results.
			$where[] = ' '.$wp_dlm_db.'.id IN (0) ';
		}
	}
	
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