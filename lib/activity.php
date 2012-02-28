<?php
/**
 * Get Activity
 * Outer api method.
 *
 * @param int $limit how many activities should be returned
 * @param int $offset offset of the list
 * @return array of activiti
 */
function activity_list($limit = 10, $offset = 0, $subject_guids = NULL) {
	// Unregister mentions rewrite handler.. it causes all kinds of hell with the JSON response
	elgg_unregister_plugin_hook_handler('output', 'page', 'mentions_rewrite');
	
	$options = array(
		'action_types' => array('create'),
		'limit' => $limit,
		'offset' => $offset,
		'subject_guids' => $subject_guids,
		'subtypes' => elgg_get_config('tgsapi_known_subtypes'),
	);
	
	$items = elgg_get_river($options);

	foreach ($items as $item) {
		$data[] = activity_details($item);
	}
	
	return $data;
}

function activity_details($item) {
	$activity_item = array();
	
	// Grab entities
	$object = get_entity($item->object_guid);
	$subject = get_entity($item->subject_guid);

	// Grab types
	$type = $object->getType();
	$subtype = $object->getSubtype();
	
	// Set a 'generic' type for the app
	$generic_type = $subtype ? $subtype : $type;
	
	// Generic title
	$title = $object->title ? $object->title : $object->name;

	/** Standard Fields (these will exist consistently between different objects) **/
	$activity_item['id'] = $item->id;
	$activity_item['object_guid'] = $item->object_guid;
	$activity_item['subject_guid'] = $item->subject_guid;
    $activity_item['comments_count'] = $object->countComments();
	$activity_item['type'] = $generic_type;
	$activity_item['subject_name'] = $subject->name;
	$activity_item['subject_photo_url'] = $subject->getIconURL('medium');	
	$activity_item['url'] = $object->getURL();
	$activity_item['time_created'] = $object->time_created;
	$activity_item['can_comment'] = tgsapi_can_comment($generic_type);
	$activity_item['type_string'] = elgg_echo("tgsapi:{$generic_type}");
	$activity_item['show_more'] = tgsapi_show_more($generic_type);
	/** End of standard fields **/
	
	// Set brief description, this can vary by object so try to set a default
	$brief_text = trim(elgg_view('river/elements/summary', array('item' => $item), FALSE, FALSE, 'default'));

	if (!empty($brief_text)) {
		$brief_text = ucfirst($brief_text . " \"{$title}\"");
	} else {
		// Should be something here!
		$brief_text = "n/a";
	}

	// Set brief description
	$activity_item['brief_description'] = $brief_text;

	// Check to see if we have an icon
	$icon_location = "tgsapi/graphics/typeicons/icon-{$generic_type}.png";
	if (file_exists(elgg_get_plugins_path() . $icon_location)) {
		$type_icon = elgg_get_site_url() . 'mod/' . $icon_location;
	} else {
		$type_icon = elgg_get_site_url() . 'mod/tgsapi/graphics/typeicons/icon-question.png';
	}
	
	// Set icon
	$activity_item['type_icon_url'] = $type_icon;
	
	// If we have a title, set it
	if ($object->title) {
		$activity_item['title'] = $title;
	}
	
	// If we have an excerpt, set it
	if ($object->excerpt) {
		$activity_item['excerpt'] = $object->excerpt;
	}
	
	// If we have a description, set it
	if ($object->description) {
		$activity_item['description'] = $object->description;
	}

	// Trigger a hook for custom activity output
	$params = array('item' => $item, 'object' => $object);
	$activity_item = elgg_trigger_plugin_hook('tgsapi:activity_content', $generic_type, $params, $activity_item);

	html_entity_decode_recursive($activity_item);
	
	return $activity_item;
}

/** Activity Content Handlers **/

/**
 * Modify the activity item output for album entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_album_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	if ($object->cover) {
        $album_cover = elgg_get_site_url() . 'photos/thumbnail/' . $object->cover . '/small/';
    } else {
        $album_cover = elgg_get_site_url() . 'mod/tidypics/graphics/image_error_small.png';
    }
	$return['image_url'] = $album_cover;
	$return['description'] = elgg_echo('tgsapi:description:album', array($object->title, $object->description));
	return $return;
}

/**
 * Modify the activity item output for blog entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_blog_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$return['description'] = $object->title . "\n\n" . $object->excerpt;
	return $return;
}

/**
 * Modify the activity item output for bookmarks entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_bookmarks_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$return['description'] = $object->address . "\n\n" . $object->description;
	return $return;
}

/**
 * Modify the activity item output for image entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_image_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$return['image_url'] = elgg_get_site_url() . 'photos/thumbnail/' . $object->guid . '/large';
	return $return;
}

/**
 * Modify the activity item output for feedback entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_feedback_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$return['description'] = $object->txt;
	return $return;
}

/**
 * Modify the activity item output for file entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_file_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$return['description'] = $return['brief_description'];
	return $return;
}

/**
 * Modify the activity item output for thewire entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_thewire_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$return['brief_description'] = "\"{$object->description}\""; 
	return $return;
}

/**
 * Modify the activity item output for shared_doc entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_shared_doc_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$text = elgg_echo('river:create:object:shared_doc', array('', "\"{$object->trunc_title}\""));
	$return['brief_description'] = ucfirst(trim($text));
	$return['description'] = $return['brief_description'];
	$return['url'] = $object->href;
	return $return;
}

/**
 * Modify the activity item output for site_activity entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_site_activity_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$text = str_replace($return['subject_name'], '', strip_tags($object->text));
    $return['brief_description'] = ucfirst(trim($text));
	$return['description'] = $return['brief_description'];
	$return['url'] = get_pure_url($object->text, false);
	return $return;
}

/**
 * Modify the activity item output for tidypics_batch entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_tidypics_batch_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$container = $object->getContainerEntity();
	
	// fetch images of batch
	$options = array(
		'relationship' => 'belongs_to_batch', 
		'relationship_guid' => $object->guid, 
		'inverse_relationship' => TRUE,
		'limit' => 30, // Shouldn't be more than 30
	);

	$images = elgg_get_entities_from_relationship($options);

	$count = count($images);

	// push them to subarray
	foreach ($images as $image) {
		$return['images'][] =  array(
			'image_url' => elgg_get_site_url() . 'photos/thumbnail/' . $image->guid . '/large',
			'title' => $image->title,
			'caption' => $image->description,
			'tags' => is_array($image->tags) ? implode(',', $image->tags) : (string)$image->tags
		);
	}
	$text = 'added ' . $count . ' photo' . ($count > 1 ? 's' : '') . ' to album "' . $container->title .'"';
	$return['description'] = $return['brief_description'] = $text;

	return $return;
}

/**
 * Modify the activity item output for todo entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_todo_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$return['related_guid'] = $object->guid;
	$return['description'] = $return['brief_description'];
	return $return;
}

/**
 * Modify the activity item output for todosubmission entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_todosubmission_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	
	// Todo might be disabled..
	$access_status = access_get_show_hidden_status();
	access_show_hidden_entities(true);

	$todo = get_entity($object->todo_guid);
	
	// If we have a todo, display text
	if ($todo){
		$text = elgg_echo("river:create:object:todosubmission", array('', "\"{$todo->title}\""));
	} else {
		$text = elgg_echo("river:create:object:todosubmission:deleted", array(''));
	}

	access_show_hidden_entities($access_status);
	$return['brief_description'] =  ucfirst(trim($text));
	$return['description'] = $return['brief_description'];

	$return['related_guid'] = $object->todo_guid;
	return $return;
}

/**
 * Modify the activity item output for forum_topic entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_forum_topic_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$forum = $object->getContainerEntity();
	$text = elgg_echo('river:create:object:forum_topic', array('', "\"{$object->title}\"", "\"{$forum->title}\""));
	$return['brief_description'] = ucfirst(trim($text));
	$return['description'] = $return['brief_description'];
	return $return;
}

/**
 * Modify the activity item output for forum_reply entities
 *
 * @param string $hook
 * @param string $type
 * @param Array $return
 * @param Array $params
 * @return unknown
 */
function tgsapi_forum_reply_activity_handler($hook, $type, $return, $params) {
	$object = $params['object'];
	$topic = get_entity($object->topic_guid);
	$forum = $object->getContainerEntity();
	$text = elgg_echo('river:create:object:forum_reply', array('', "\"{$topic->title}\"", "\"{$forum->title}\""));
	$return['brief_description'] = ucfirst(trim($text));
	$return['description'] = $return['brief_description'] . "\n\n" . $object->description;
	return $return;
}