<?php
	/** Site stats function **/
	function get_site_stats($photo_limit = 20) {
		// Need to ignore access
		$ia = elgg_get_ignore_access();
		elgg_set_ignore_access(true);

		// Count entities
		$blog_count 	= elgg_get_entities(array('type' => 'object', 'subtype' => 'blog', 'count' => true));
		$photo_count 	= elgg_get_entities(array('type' => 'object', 'subtype' => 'image', 'count' => true));
		$bookmark_count = elgg_get_entities(array('type' => 'object', 'subtype' => 'bookmarks', 'count' => true));
		$rubric_count 	= elgg_get_entities(array('type' => 'object', 'subtype' => 'rubric', 'count' => true));
		$group_count 	= elgg_get_entities(array('type' => 'group', 'count' => true));
		$todo_count 	= elgg_get_entities(array('type' => 'object', 'subtype' => 'todosubmission', 'count' => true)); 
	
		elgg_set_ignore_access($ia);
		
		// Latest photos
		$photos = elgg_get_entities(array('type' => 'object', 'subtype' => 'image', 'limit' => $photo_limit));

		foreach ($photos as $photo) {
			$stats['latest_photos'][] = elgg_get_site_url() . "pg/photos/thumbnail/{$photo->getGUID()}/small/";
		}
		
		$stats['blogs'] = $blog_count;
		$stats['photos'] = $photo_count;
		$stats['bookmarks'] = $bookmark_count;
		$stats['rubrics'] = $rubric_count;
		$stats['group'] = $group_count;
		$stats['todo'] = $todo_count;
		$stats['db']['size'] = get_db_size();
		$stats['db']['size_formatted'] = format_api_filesize($stats['db']['size'] = get_db_size());
		$stats['data_folder']['size'] = get_elgg_data_size();
		$stats['data_folder']['size_formatted'] = format_api_filesize($stats['data_folder']['size']);
	
		return $stats;
	}
	
	/** Get latest public photos **/
	function get_latest_public_photos ($photo_size = 'large') {
		
	}
	
	/** Query the Elgg DB and get its size (formatted) **/
	function get_db_size() {
		global $CONFIG;
		
		$query = "SELECT table_schema 'db', sum( data_length + index_length ) 'size'  
				FROM information_schema.TABLES 
				WHERE table_schema = '$CONFIG->dbname' GROUP BY table_schema;";
		
		$result = get_data($query);

		$size = $result['0']->size;

		return $size;
	}
	
	/** Get the the size of the elgg_data folder **/
	function get_elgg_data_size() {
		global $CONFIG;
		//return recursive_directory_size($CONFIG->dataroot, true);
		$size = get_dir_size($CONFIG->dataroot);
		return $size;
	}

	/** Format size to something more human readable **/
	function format_api_filesize($size) {
		// Convert to MB
		$size = $size / 1024 / 1024;
		// If the size is larger than a gig, display it as GB
		if ($size > 1024) {
			$size = sprintf("%s GB", number_format($size / 1024, 2));
		} else {
			$size = sprintf("%s MB", number_format($size, 2));
		}
		return $size;
	}

?>
