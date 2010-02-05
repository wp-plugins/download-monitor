<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - download_taxonomies CLASS
*/

class download_taxonomies {

	var $categories;
	var $tags;
	var $used_tags;
	var $download2taxonomy_ids;
	
	function download_taxonomies() {
		global $wpdb, $wp_dlm_db_relationships, $wp_dlm_db_taxonomies;
		
		$download2taxonomy_data 	= $wpdb->get_results( "SELECT * FROM $wp_dlm_db_relationships;" );
			
		$download2taxonomy_ids = array();
		
		if ($download2taxonomy_data) {
			foreach ($download2taxonomy_data as $d2t) {
				$download2taxonomy_ids[$d2t->download_id][] = $d2t->taxonomy_id;
			}
		}
		
		$this->download2taxonomy_ids = $download2taxonomy_ids;
		
		$taxonomy_data = $wpdb->get_results( "SELECT $wp_dlm_db_taxonomies.*, COUNT($wp_dlm_db_relationships.taxonomy_id) as count 
		FROM $wp_dlm_db_taxonomies 
		LEFT JOIN $wp_dlm_db_relationships ON $wp_dlm_db_taxonomies.id = $wp_dlm_db_relationships.taxonomy_id
		GROUP BY $wp_dlm_db_taxonomies.id 
		ORDER BY $wp_dlm_db_taxonomies.`parent`,$wp_dlm_db_taxonomies.`order`, $wp_dlm_db_taxonomies.`id`;" );
		
		$this->categories = array();
		$this->tags = array();
		$this->used_tags = array();
		
		foreach ($taxonomy_data as $taxonomy) {
			if ($taxonomy->taxonomy=='tag') {
				$this->tags[$taxonomy->id] = new download_tag($taxonomy->id, $taxonomy->name, $taxonomy->count);
			} 
			if ($taxonomy->taxonomy=='category') {
				$this->categories[$taxonomy->id] = new download_category($taxonomy->id, $taxonomy->name, $taxonomy->parent, $taxonomy->count);
			}
		}
		
		$this->find_category_family();
		$this->filter_unused_tags();
		
	}	
	
	function find_category_family() {
		foreach ($this->categories as $cat) {
			//if ($cat->parent==0) {				
				// Starting at top level cats
				$cat->decendents = $this->find_decendents($cat->id);
			//}
			$cat->direct_decendents = $this->find_direct_decendents($cat->id);
		}
	}
	
	function find_decendents($id = 0, $decendents = array()) {
		if ($id>0) {
			foreach ($this->categories as $cat) {
				if ($cat->parent==$id) {					
					$subdecendents = $this->find_decendents($cat->id);
					$decendents = array_merge($subdecendents, $decendents);
					$decendents[] = $cat->id;
					//$cat->decendents = $decendents;
				}
			}		
		}		
		return $decendents;
	}
	
	function find_direct_decendents($id = 0) {
		$decendents = array();
		if ($id>0) {
			foreach ($this->categories as $cat) {
				if ($cat->parent==$id) {					
					$decendents[] = $cat->id;
				}
			}		
		}		
		return $decendents;
	}
	
	function filter_unused_tags() {
		
		$used_ids = array();
		
		foreach ($this->download2taxonomy_ids as $downloads) {
			foreach ($downloads as $key=>$value) {
				$used_ids[] = $value;
			}
		}

		if ($this->tags) {
			foreach ($this->tags as $tag) {
				if (in_array($tag->id, $used_ids)) {					
					$this->used_tags[] = $tag;
				}
			}		
		}		
	}
	
	function get_parent_cats() {
		$cats = array();
		foreach ($this->categories as $cat) {
			if ($cat->parent==0) {	
				$cats[] = $cat;
			}
		}
		return $cats;
	}
	
	function do_something_to_cat_children($cat, $function, $function_none = '', $functionarg = '') {
		// Poor Kittens
		$retval = '';
		if($this->categories[$cat]->decendents) {
			foreach ($this->categories[$cat]->decendents as $child) {
				if ($this->categories[$child]->parent==$cat) {	
					if ($functionarg) {
						$retval = call_user_func($function, $this->categories[$child], $functionarg);
					} else {
						$retval = call_user_func($function, $this->categories[$child]);
					}
				}
			}
		} else {
			if ($function_none) {
				if ($functionarg) {
					$retval = call_user_func($function_none, $functionarg);
				} else {
					$retval = call_user_func($function_none);
				}
			}
		}
		return $retval;
	}
	
	function do_something_to_cat_parents($cat, $function, $function_none = '', $functionarg = '') {
		// Revenge
		$retval = '';
		if($parent = $this->categories[$cat]->parent) {
			if ($functionarg) {
				$retval = call_user_func($function, $this->categories[$parent], $functionarg);
			} else {
				$retval = call_user_func($function, $this->categories[$parent]);
			}
		} else {
			if ($function_none) {
				if ($functionarg) {
					$retval = call_user_func($function_none, $functionarg);
				} else {
					$retval = call_user_func($function_none);
				}
			}
		}
		return $retval;
	}

}

class download_category {
	var $id;
	var $name;
	var $parent;
	var $decendents;
	var $direct_decendents;
	var $size;
	
	function download_category($id, $name, $parent, $size) {
		$this->id = $id;
		$this->name = $name;
		$this->parent = $parent;
		$cat->decendents = array();
		$this->size = $size;
	}
	
	function get_decendents() {
		if (is_array($this->decendents)) return $this->decendents;
		return array();
	}
}

class download_tag {
	var $id;
	var $name;
	var $size;
	var $parent;
	
	function download_tag($id, $name, $size) {
		$this->id = $id;
		$this->name = $name;
		$this->size = $size; 
	}
}
	
?>