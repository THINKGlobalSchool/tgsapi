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

	// we do not use elgg methods to get activities because they do not support exclusion of unwanted activity types
	// so the limit-offset feature could not be implemented or it will cause serious performance problems
	// (if we'll fetch all activities and exclude unwanted in a cycle)
    $activities = get_activities($known_types, 'comment', $limit, $offset);
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

	// get needed in future params
    $object_id = (int) $activity->object_guid;
    $user_id = (int) $activity->subject_guid;
    $entity = get_entity($object_id);
	$type = get_activity_type($activity);

	// start of pushing data
    $standart_data = array();
    $standart_data['id'] = (int) $activity->id;
    $standart_data['parent_id'] = $object_id;
    $standart_data['type'] = $type;
    $standart_data['category_icon_url'] = get_category_icon($type);
    $standart_data['time_created'] = $entity->time_created;
    $standart_data['comments_count'] = get_comments_count($object_id);
    $standart_data['url'] = $entity->getURL();

	// some other needed
    $user_data = get_user_details($user_id);
    $object_details = get_object_details($object_id);

	// this types of activities are placed in container
    $in_container = array ('groupforumtopic', 'image', 'group', 'tidypics_batch');

    // Need to go to container
    if (in_array($type, $in_container)) {
        $container_id = $entity->container_guid;
        $container = get_entity($container_id);
    }

	// set description and custom fields depending on activity type
	// for some activities (i.e. image) we accumulate only some of the fields and return instantly
    switch ($type) {
        case 'image':
            $data['id'] = $standart_data['id'];
            $data['parent_id'] = $standart_data['parent_id'];
            $data['type'] = $type;
            $data['title'] = $entity->title;
            $data['brief_description'] = 'added the photo '. $entity->title .' to album '.$container->title;
            $data['description'] = $entity->description;
            $data['time_created'] = $standart_data['time_created'];
            $data['image_url'] =  elgg_get_site_url() . 'pg/photos/thumbnail/' . $entity->guid . '/large';
            $data['comments_count'] = $standart_data['comments_count'];

            $data = array_merge($data, $user_data);

            html_entity_decode_recursive($data);

            return $data;
            break;

        case 'doc_activity':
			$text = 'Added a new document';
			$brief_desc = 'Added a new document';
			$standart_data['url'] = get_pure_url($object_details['text'], false);
            break;

        case 'site_activity':
            $activity_text = str_replace($user_data['author'].' ', '' ,$object_details['text']);
            $text = $brief_desc = $activity_text;
            $standart_data['url'] = get_pure_url($object_details['text'], false);
            break;

        case 'album':
            $cnt = get_count_photos_in_album($entity->guid);
            $text = 'Album contains ' . $cnt . ' ' . ($cnt > 1 ? 'photos' : 'photo');
            
            if ($entity->cover) {
                $album_cover = elgg_get_site_url() . 'pg/photos/thumbnail/' . $entity->cover . '/small/';
            } else {
                $album_cover = elgg_get_site_url() . 'mod/tidypics/graphics/image_error_small.png';
            }

            $standart_data['image_url'] =  $album_cover;
            $brief_desc = 'created a new photo album "'. $entity->title . '"';

            unset($object_details['new_album']);
            break;

        case 'blog':
            $text = 'wrote a new blog post "'. $entity->title . '"';
            $brief_desc = 'wrote a new blog post "'. $entity->title . '"';
//            $standart_data['excerpt'] = $object_details['excerpt'];
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

			$data['id'] = $standart_data['id'];
			$data['parent_id'] = $standart_data['parent_id'];
			$data['type'] = $type;
			$data['description'] = 'added ' . $images_count . ' photo' . ($images_count > 1 ? 's' : '') . ' to album ' . $container->title;
			$data['brief_description'] = $data['description'];
			$data['time_created'] = $standart_data['time_created'];
			$data['comments_count'] = $standart_data['comments_count'];

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
            $text = $standart_data['url'];
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
            $text = 'Discussion topic: ' . $entity->title . "\n" . $standart_data['url'];
            $brief_desc = 'has started a new discussion topic titled "'. $entity->title .'" in the group '. $container->name;
            break;

        case 'todo':
            $text = 'created a To Do titled '. $entity->title;
            $brief_desc = 'created a To Do titled "'. $entity->title . '"';
			$standart_data['todo_guid'] = $object_id;
            break;

        case 'bookmarks':
            $text = 'Bookmarked '. $object_details['address'];
            $brief_desc = 'bookmarked '. $entity->title;
            break;

        case 'group':
            $text = 'is now a member of the group '.  $entity->name;
            $brief_desc = 'is now a member of the group '.  $entity->name;

            $object_details = array();
            unset($standart_data['parent_id']);

            break;

        case 'thewire':
            $text = $entity->description;
            $brief_desc = $entity->description;
            break;

        case 'todosubmission':
            $todo_entity = get_entity($object_details['todo_guid']);
            $text = 'completed a To Do titled "'.  $todo_entity->title . '"';
            $brief_desc = 'completed a To Do titled "'.  $todo_entity->title . '"';
            break;

        case 'file':
            $text = 'uploaded file "'.  $entity->name . '"';
            $brief_desc = 'uploaded file "'.  $entity->name . '"';
            break;

        case 'user':
            $text = 'joined the site';
            $brief_desc = 'joined the site';

            $object_details = array();
            unset($standart_data['parent_id']);

            break;

        default:
            $text = 'description for '.  $type .' not ready yet';
            $brief_desc = 'description for '.  $type .' not ready yet';
            break;
    }

	// set descriptions
    $standart_data['description'] = ucfirst(trim($text));
    $standart_data['brief_description'] = ucfirst(trim($brief_desc));

	// sanitise arrays
    html_entity_decode_recursive($object_details);
    html_entity_decode_recursive($standart_data);

    return array_merge($standart_data, $object_details, $user_data);
}
