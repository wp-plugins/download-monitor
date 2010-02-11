<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - downloadable_file CLASS
*/

class downloadable_file {
	var $id;
	var $filename;
	var $title;
	var $user;
	var $version;
	var $dlversion;
	var $hits;
	var $file_description;
	var $desc;
	var $mirrors;
	var $postDate;
	var $date;
	var $members;
	var $memberonly;
	var $url;
	var $size;		
	var $tags;
	var $thumbnail;
	var $meta;
	var $image;
	
	var $categories;
	var $category;
	var $category_id;
	
	var $patts;
	var $subs;
	
	function downloadable_file($d = '', $format = '') {
		$this->init_file($d);
		if ($d && $format) {			
			$this->prep_download_data($format);
		}
	}
	
	function init_file($d) {
		if ($d) {
			global $downloadurl, $downloadtype, $wp_dlm_image_url;
				
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
			$this->url =  $downloadurl.$downloadlink;
			$this->id = $d->id;
			$this->filename = $d->filename;
			$this->title = $d->title;
			$this->user = $d->user;
			$this->version = $d->dlversion;
			$this->dlversion = $d->dlversion;
			$this->hits = $d->hits;
			$this->file_description = $d->file_description;
			$this->desc = $d->file_description;
			$this->mirrors = $d->mirrors;
			$this->postDate = $d->postDate;
			$this->date = $d->postDate;
			$this->members = $d->members;
			$this->memberonly = $d->members;
			$this->get_size();
			$this->get_taxonomy();
			$this->get_meta();
			$this->image = $wp_dlm_image_url;
		}	
	}
	
	function get_taxonomy() {
		global $wp_dlm_db_relationships,$wpdb,$download_taxonomies;
		
		$download_cats = array();
		$download_tags = array();
		
		$download2taxonomy_data = $wpdb->get_col( "SELECT taxonomy_id FROM $wp_dlm_db_relationships WHERE download_id = ".$wpdb->escape($this->id).";" );

		if (sizeof($download_taxonomies->categories)>0) :
		
			foreach($download_taxonomies->categories as $tax) {
			
				if (isset($download2taxonomy_data) && is_array($download2taxonomy_data) && in_array($tax->id, $download2taxonomy_data)) {
					$download_cats[] = array(
						'name' => $tax->name,
						'id' => $tax->id,
						'parent' => $tax->parent,
					);				
				}
			}
		
		endif;
		if (sizeof($download_taxonomies->tags)>0) :
	
			foreach($download_taxonomies->tags as $tax) {
				if (isset($download2taxonomy_data) && is_array($download2taxonomy_data) && in_array($tax->id, $download2taxonomy_data)) {
					$download_tags[] = array(
						'name' => $tax->name,
						'id' => $tax->id,
						'parent' => $tax->parent,
					);					
				}
			}
		
		endif;
		$this->tags = $download_tags;
		$this->categories = $download_cats;
		$firstcat = current($download_cats);
		$this->category = $firstcat['name'];
		$this->category_id = $firstcat['id'];
	}
	
	function get_meta() {
		global $wp_dlm_root, $wpdb, $wp_dlm_db_meta;
		
		$tags = '';
		$this_meta = array();
		$thumbnail = '';
		
		$meta_data = $wpdb->get_results( "SELECT meta_name,meta_value FROM $wp_dlm_db_meta WHERE download_id = ".$wpdb->escape($this->id).";" );
		
		if ($meta_data) :
	
			foreach($meta_data as $meta) {
				if ($meta->meta_name == 'thumbnail') {
					$thumbnail = stripslashes($meta->meta_value);
				} else {
					$this_meta[$meta->meta_name] = stripslashes($meta->meta_value);
				}
			}
		
		endif;
		
		if (!$thumbnail) $thumbnail = $wp_dlm_root.'page-addon/thumbnail.gif';	
		
		$this->thumbnail = $thumbnail;
		$this->meta = $this_meta;
	}
	
	function get_size() {
		$thefile = $this->filename;
		$urlparsed = parse_url($thefile);
		$isURI = array_key_exists('scheme', $urlparsed);
		$localURI = (bool) strstr($thefile, get_bloginfo('url')); /* Local TO WORDPRESS!! */
						
		if( $localURI ) {
			// the URI is local, replace the WordPress url OR blog url with WordPress's absolute path.
			$patterns = array( '|^'. get_bloginfo('wpurl') . '/' . '|', '|^'. get_bloginfo('url') . '/' . '|');
			$path = preg_replace( $patterns, '', $thefile );
			// this is joining the ABSPATH constant, changing any slashes to local filesystem slashes, and then finally getting the real path.
			$thefile = str_replace( '/', DIRECTORY_SEPARATOR, path_join( ABSPATH, $path ) );							
		// Local File System path
		} else if( !path_is_absolute( $thefile ) ) { 
			//$thefile = path_join( ABSPATH, $thefile );
			// Get the absolute path
			if ( ! isset($_SERVER['DOCUMENT_ROOT'] ) ) $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF']) ) );
			$dir_path = $_SERVER['DOCUMENT_ROOT'];
			// Now substitute the domain for the absolute path in the file url
			$thefile = str_replace( '/', DIRECTORY_SEPARATOR, path_join($dir_path, $thefile ));
		} else {
			$thefile = str_replace(get_bloginfo('wpurl'), ABSPATH, $thefile);
		}
							
		if (@file_exists($thefile)) {
			$size = filesize($thefile);
			if ($size) {
			$bytes = array('bytes','KB','MB','GB','TB');
			  foreach($bytes as $val) {
			   if($size > 1024){
				$size = $size / 1024;
			   }else{
				break;
			   }
			  }
			  $this->size = round($size, 2)." ".$val;
			}
		}
	}
	
	function prep_download_data($format) {
		
		global $wp_dlm_image_url, $wp_dlm_db_meta, $download_taxonomies;
			
		$fpatts = array(
			'{url}', 
			'{id}', 
			'{user}', 
			'{version}', 
			'{title}', 
			'{size}', 
			'{hits}', 
			'{image_url}', 
			'{description}', 
			'{description-autop}', 
			'{category}', 
			'{category_other}'
		);
		
		$fsubs = array( 
			$this->url, 
			$this->id,
			$this->user, 
			$this->version, 
			$this->title, 
			$this->size, 
			$this->hits , 
			$wp_dlm_image_url, 
			$this->file_description , 
			wpautop($this->file_description) 
		);
		
		// Category (single cat uses first - this is for compatibility with the old system)
		if ($this->category_id>0) {			
			$fsubs[]  = $this->category; /* category */
			$fsubs[]  = $this->category; /* category_other */
			preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				$fsubs[]  = $match[1].$this->category.$match[2];
			}
			preg_match("/{category_other,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				$fsubs[]  = $match[1].$this->category.$match[2];
			}
			$fpatts[] = '{category_ID}';
			$fsubs[] = $this->category_id;
		} else {
			$fsubs[]  = "";
			$fsubs[]  = __('Other','wp-download_monitor');
			preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				$fsubs[]  = "";
			}
			preg_match("/{category_other,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				$fsubs[]  = $match[1].__('Other','wp-download_monitor').$match[2];
			}
			$fpatts[] = '{category_ID}';
			$fsubs[] = "";
		}
		
		// Categories (multiple)
		$fpatts[] = "{categories}";
		$cats = array();
		if (!$this->categories) $cats[] = __('Uncategorized',"wp-download_monitor");
		else {
			foreach ($this->categories as $cat) {
				$cats[] = $cat['name'];
			}
		}
		$fsubs[] = implode(', ', $cats);
		
		// Categories (linked)
		preg_match("/{categories,\s*\"([^\"]*?)\"}/", $format, $match);
		if ($match) {
			$fpatts[] = $match[0];
			$cats = array();
			if (!$this->categories) $cats[] = '<a href="'.str_replace('%',0,str_replace('%2',urlencode(strtolower(__('Other',"wp-download_monitor"))),$match[1])).'" class="cat-link">'.__('Other',"wp-download_monitor").'</a>';
			else {
				foreach ($this->categories as $cat) {
					$cats[] = '<a href="'.str_replace('%',$cat['id'],str_replace('%2',urlencode(strtolower($cat['name'])),$match[1])).'" class="cat-link">'.$cat['name'].'</a>';
				}
			}
			$fsubs[] = implode(', ', $cats);
		}
		
		// Filetype
		$fpatts[] = "{filetype}";
		$filetype = basename($this->filename);
		$filetype = trim(strtolower(substr(strrchr($filetype,"."),1)));	
		if ($filetype) 
			$fsubs[] = $filetype;	
		else {
			$fsubs[] = __('N/A',"wp-download_monitor");
			$filetype = __('File',"wp-download_monitor");
		}
		
		global $wp_dlm_root;
		
		// Filetype Icons
		$fpatts[] = "{filetype_icon}";
		$icon = '<img alt="'.$filetype.'" title="'.$filetype.'" class="download-icon" src="'.$wp_dlm_root.'img/filetype_icons/';
		switch ($filetype) :
			case "pdf" :
				$icon .= 'document-pdf';
			break;
			case "m4r":
			case "au":
			case "snd":
			case "mid":
			case "midi":
			case "kar":
			case "mpga":
			case "mp2":
			case "mp3":
			case "aif":
			case "aiff":
			case "aifc":
			case "m3u":
			case "ram":
			case "rm":
			case "rpm":
			case "ra":
			case "wav":
				$icon .= 'document-music';
			break;
			case "mpeg": 
			case "mpg":
			case "mpe":
			case "qt":
			case "mov":
			case "mxu":
			case "avi":
			case "movie":			
				$icon .= 'document-film';
			break;
			case "zip":
			case "gz":
			case "rar":
			case "sit":
			case "tar":
				$icon .= 'document-zipper';
			break;
			case "xls":
			case "tsv":	
			case "csv":	
				$icon .= 'document-excel';
			break;
			case "doc":
				$icon .= 'document-word-text';
			break;
			case "ai":
				$icon .= 'document-illustrator';
			break;
			case "swf":
				$icon .= 'document-flash-movie';
			break;			
			case "eps":
			case "ps":
			case "bmp":
			case "gif":	
			case "ief":
			case "jpeg":
			case "jpg":
			case "jpe":
			case "png":
			case "tiff":
			case "tif":
			case "djv":	
			case "wbmp":
			case "ras":
			case "pnm":
			case "pbm":
			case "pgm":
			case "ppm":
			case "rgb":
			case "xbm":
			case "xpm":
			case "xwd":
				$icon .= 'document-image';
			break;
			case "psd" :
				$icon .= 'document-photoshop';
			break;
			case "ppt" :
				$icon .= 'document-powerpoint';
			break;
			case "js":
			case "css":
			case "as":
			case "htm":
			case "htaccess":
			case "sql":
			case "html":			
			case "php":
			case "xml":
			case "xsl":
				$icon .= 'document-code';
			break;
			case "rtx": 
			case "rtf":
				$icon .= 'document-text-image';
			break;
			case "txt":	
				$icon .= 'document-text';
			break;
			default :
				$icon .= 'document';
			break;
		endswitch;
		$icon .= '.png" />';
		$fsubs[] = $icon;
			
		// Hits (special) {hits, none, one, many)
		preg_match("/{hits,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
		if ($match) {
			$fpatts[] = $match[0];
			if ( $this->hits == 1 ) 
			{
				$text = str_replace('%',$this->hits,$match[2]);
				$fsubs[]  = $text; 
			}
			elseif ( $this->hits > 1 ) 
			{
				$text = str_replace('%',$this->hits,$match[3]);
				$fsubs[]  = $text; 
			}
			else 
			{
				$text = str_replace('%',$this->hits,$match[1]);
				$fsubs[]  = $text; 
			}
		}				
		
		// Version
		preg_match("/{version,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
		if ($match) {
			$fpatts[] = $match[0];
			if ($this->version) $fsubs[]  = $match[1].$this->version.$match[2]; else $fsubs[]  = "";
		}
		
		// Date
		preg_match("/{date,\s*\"([^\"]*?)\"}/", $format, $match);
		if ($match) {
			$fpatts[] = $match[0];
			if ($this->postDate) $fsubs[] = date_i18n($match[1],strtotime($this->postDate)); else $fsubs[]  = "";
		}				
		
		// Other
		preg_match("/{description,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
		if ($match) {
			$fpatts[] = $match[0];
			if ($this->file_description) $fsubs[]  = $match[1].$this->file_description.$match[2]; else $fsubs[]  = "";
		}
		
		preg_match("/{description-autop,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
		if ($match) {
			$fpatts[] = $match[0];
			if ($this->file_description) $fsubs[]  = $match[1].wpautop($this->file_description).$match[2]; else $fsubs[]  = "";
		}
						
		// tags
		$fpatts[] = "{tags}";
		$tags = array();
		if (!$this->tags) $tags[] = 'Untagged';
		else {
			foreach ($this->tags as $tag) {
				$tags[] = $tag['name'];
			}
		}
		$fsubs[] = implode(', ', $tags);
		
		// Tags (linked)
		preg_match("/{tags,\s*\"([^\"]*?)\"}/", $format, $match);
		if ($match) {
			$fpatts[] = $match[0];
			$tags = array();
			if (!$this->tags) $tags[] = 'Untagged';
			else {
				foreach ($this->tags as $tag) {
					$tags[] = '<a href="'.str_replace('%',$tag['id'],str_replace('%2',urlencode(strtolower($tag['name'])),$match[1])).'" class="tag-link">'.$tag['name'].'</a>';
				}
			}
			$fsubs[] = implode(', ', $tags);
		}
		
		// Thumbnail
		$fpatts[] = "{thumbnail}";
		$fsubs[] = $this->thumbnail;
		
		// meta
		if (preg_match("/{meta-([^,]*?)}/", $format, $match)) {					
			$meta_names = array();
			$meta_names[] = "''";
			foreach($this->meta as $meta_name=>$meta_value) {
				//if ($meta->download_id==$d->id) {
					$fpatts[] = "{meta-".$meta_name."}";
					$fsubs[] = stripslashes($meta_value);
					$fpatts[] = "{meta-autop-".$meta_name."}";
					$fsubs[] = wpautop(stripslashes($meta_value));
					$meta_names[] = $meta_name;
				//}
			}
			// Blank Meta
			//$meta_blank = $wpdb->get_results( "SELECT meta_name FROM $wp_dlm_db_meta WHERE meta_name NOT IN ( ".	implode(',',$meta_names)	." );" );
			global $meta_blank;
			foreach($meta_blank as $meta_name) {
				$fpatts[] = "{meta-".$meta_name."}";
				$fsubs[] = '';
				$fpatts[] = "{meta-autop-".$meta_name."}";
				$fsubs[] = '';
			}
		}
	
		$this->patts = $fpatts;				
		$this->subs = $fsubs;
	}	
	
}
	
?>