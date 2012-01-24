<?php

require_once 'functions.php';

/**
 * Get the list of activities.
 * Outer api method.
 *
 * @param int $limit how many activities should be returned
 * @param int $offset offset of the list
 * @return array of activiti
 */
function activity_list($limit = 10, $offset = 0) {
    require_once dirname(dirname(__FILE__)) .'/config.php';
    
	foreach ($known_types as $subtype) {
		$subtype = sanitise_string($subtype);
		$wheres[] = "(rv.subtype = '$subtype')";
	}

	if (is_array($wheres) && count($wheres)) {
		$wheres = array(implode(' OR ', $wheres));
	}
	
	$wheres[0] = "({$wheres[0]})";
		
	$options = array(
		'action_types' => array('create'),
		'limit' => $limit,
		'offset' => $offset,
		'wheres' => $wheres,
	);
	
	$activities = elgg_get_river($options);
			
	$data = array();

	/// push activity details to the list
    foreach ($activities as $activity) {
        $data[] = activity_details($activity);
    }

    return $data;
}

/**
 * Get activity details.
 *
 * @param stdClass $activity
 * @return array
 */
function activity_details($activity) {

    if (!is_object($activity) ) {
        return 'NO ACTIVITY';
    }

	$access_status = access_get_show_hidden_status();
	access_show_hidden_entities(true);

	// get needed in future params
    $object_guid = (int) $activity->object_guid;
    $user_guid = (int) $activity->subject_guid;
    $entity = get_entity($object_guid);
	$type = get_activity_type($activity);
	
	access_show_hidden_entities($access_status);

	// start of pushing data
    $standard_data = array();
    $standard_data['id'] = (int) $activity->id;
    $standard_data['parent_id'] = $object_guid;
    $standard_data['type'] = $type;
    $standard_data['category_icon_url'] = get_category_icon($type);
    $standard_data['time_created'] = $entity->time_created;
    $standard_data['comments_count'] = $entity->countComments();
    $standard_data['url'] = $entity->getURL();

	// some other needed
    $user_data = get_user_details($user_guid);
    $object_details = get_object_details($object_guid);

	// this types of activities are placed in container
    $in_container = array ('groupforumtopic', 'image', 'group', 'tidypics_batch');

    // Need to go to container
    if (in_array($type, $in_container)) {
        $container_guid = $entity->container_guid;
        $container = get_entity($container_guid);
    }

	// set description and custom fields depending on activity type
	// for some activities (i.e. image) we accumulate only some of the fields and return instantly
    switch ($type) {
        case 'image':
            $data['id'] = $standard_data['id'];
            $data['parent_id'] = $standard_data['parent_id'];
            $data['type'] = $type;
            $data['title'] = $entity->title;
            $data['brief_description'] = 'added the photo '. $entity->title .' to album '.$container->title;
            $data['description'] = $entity->description;
            $data['time_created'] = $standard_data['time_created'];
            $data['image_url'] =  elgg_get_site_url() . 'pg/photos/thumbnail/' . $entity->guid . '/large';
            $data['comments_count'] = $standard_data['comments_count'];

            $data = array_merge($data, $user_data);

            html_entity_decode_recursive($data);

            return $data;
            break;

        case 'shared_doc':
			$text = 'Added a new document titled ' . $object_details['trunc_title'] ;
			$brief_desc = 'Added a new document titled ' . $object_details['trunc_title'];
			$standard_data['url'] = get_pure_url($object_details['href'], false);
            break;

        case 'site_activity':
            $activity_text = str_replace($user_data['author'].' ', '' ,$object_details['text']);
            $text = $brief_desc = $activity_text;
            $standard_data['url'] = get_pure_url($object_details['text'], false);
            break;

        case 'album':
            $cnt = get_count_photos_in_album($entity->guid);
            $text = 'Album contains ' . $cnt . ' ' . ($cnt > 1 ? 'photos' : 'photo');
            
            if ($entity->cover) {
                $album_cover = elgg_get_site_url() . 'pg/photos/thumbnail/' . $entity->cover . '/small/';
            } else {
                $album_cover = elgg_get_site_url() . 'mod/tidypics/graphics/image_error_small.png';
            }

            $standard_data['image_url'] =  $album_cover;
            $brief_desc = 'created a new photo album "'. $entity->title . '"';

            unset($object_details['new_album']);
            break;

        case 'blog':
            $text = 'wrote a new blog post "'. $entity->title . '"';
            $brief_desc = 'wrote a new blog post "'. $entity->title . '"';
//            $standard_data['excerpt'] = $object_details['excerpt'];
            break;

		case 'tidypics_batch':
			// fetch images of batch
			$images = elgg_get_entities_from_relationship(array('relationship' => 'belongs_to_batch', 'relationship_guid' => $entity->guid, 'inverse_relationship' => true));
			
			// push them to subarray
			foreach ($images as $image) {
				$data['images'][] =  array(
					'image_url' => elgg_get_site_url() . 'pg/photos/thumbnail/' . $image->guid . '/large',
					'title' => $image->title,
					'caption' => $image->description,
					'tags' => is_array($image->tags) ? implode(',', $image->tags) : (string)$image->tags
				);
			}

			$images_count = count($images);

			$data['id'] = $standard_data['id'];
			$data['parent_id'] = $standard_data['parent_id'];
			$data['type'] = $type;
			$data['description'] = 'added ' . $images_count . ' photo' . ($images_count > 1 ? 's' : '') . ' to album ' . $container->title;
			$data['brief_description'] = $data['description'];
			$data['time_created'] = $standard_data['time_created'];
			$data['comments_count'] = $standard_data['comments_count'];

			$data = array_merge($data, $user_data);

			html_entity_decode_recursive($data);

			return $data;
			break;

		case 'conversations':
			// get the excertpt of conversation topic
			$converstaion_for = trim($entity->description);
			$max_len = 40;

			if (strlen($converstaion_for) > $max_len) {
				$space_pos = strpos($converstaion_for, ' ', $max_len);
				if ($space_pos !== false) {
					$converstaion_for = substr($converstaion_for, 0, $space_pos);
					$converstaion_for .= '...';
				}
			}
			$text = 'started a conversation for '. $converstaion_for;
            $brief_desc = 'started a conversation for '. $converstaion_for;

			break;

		case 'videolist':
			$text = 'added a video titled "'. $entity->title . '"';
            $brief_desc = 'added a video titled "'. $entity->title . '"';

			break;

        case 'document':
            $text = $standard_data['url'];
            $brief_desc = 'uploaded "'. $entity->title . '"';
            break;

        case 'feedback':
            $text = 'Submitted new feedback.' . "\n" .
                    'Mood: ' . $object_details['mood'] . "\n" .
                    'About: ' . $object_details['about'] . "\n" .
                    "\n" . $object_details['txt'];

            $brief_desc = 'submitted new feedback titled "'. $entity->title . '"';
            break;

        case 'groupforumtopic':
            $text = 'Discussion topic: ' . $entity->title . "\n" . $standard_data['url'];
            $brief_desc = 'has started a new discussion topic titled "'. $entity->title .'" in the group '. $container->name;
            break;

        case 'todo':
			$access_status = access_get_show_hidden_status();
			access_show_hidden_entities(true);
            $text = 'created a To Do titled '. $entity->title;
            $brief_desc = 'created a To Do titled "'. $entity->title . '"';
			$standard_data['todo_guid'] = $object_guid;
			access_show_hidden_entities($access_status);
            break;

        case 'bookmarks':
            $text = 'Bookmarked '. $object_details['address'];
            $brief_desc = 'bookmarked '. $entity->title;
            break;

        case 'group':
            $text = 'is now a member of the group '.  $entity->name;
            $brief_desc = 'is now a member of the group '.  $entity->name;

            $object_details = array();
            unset($standard_data['parent_id']);

            break;

        case 'thewire':
            $text = $entity->description;
            $brief_desc = $entity->description;
            break;

        case 'todosubmission':
			$access_status = access_get_show_hidden_status();
			access_show_hidden_entities(true);
            $todo_entity = get_entity($object_details['todo_guid']);
            $text = 'completed a To Do titled "'.  $todo_entity->title . '"';
            $brief_desc = 'completed a To Do titled "'.  $todo_entity->title . '"';
			access_show_hidden_entities($access_status);
            break;

        case 'file':
            $text = 'uploaded file "'.  $entity->title . '"';
            $brief_desc = 'uploaded file "'.  $entity->title . '"';
            break;

        case 'user':
            $text = 'joined the site';
            $brief_desc = 'joined the site';

            $object_details = array();
            unset($standard_data['parent_id']);

            break;

        default:
            $text = 'description for '.  $type .' not ready yet';
            $brief_desc = 'description for '.  $type .' not ready yet';
            break;
    }

	// set descriptions
    $standard_data['description'] = ucfirst(trim($text));
    $standard_data['brief_description'] = ucfirst(trim($brief_desc));

	// sanitise arrays
    html_entity_decode_recursive($object_details);
    html_entity_decode_recursive($standard_data);

    return array_merge($standard_data, $object_details, $user_data);
}
