<?php
/**
 * Post comment to given activity, represented by it's id.
 * Outer api method.
 *
 * @param int $activity_id
 * @param string $text
 * @return bool
 */
function comment_post($activity_id, $text) {

	// Get the activity item
    $activity = elgg_get_river(array('id' => $activity_id));

	// If we have no activity, get outta here
	if (!count($activity)) {
		return FALSE;
	}
	
	$activity = $activity[0];

	// Get object related to activity
    $object_guid = $activity->object_guid;

	// Get object
	$object = get_entity($object_guid);

	// Get type of activity
    $type = $object->getSubtype() ? $object->getSubtype() : $object->getType();

    $user = elgg_get_logged_in_user_entity();
	
	// Set generic_comment input for plugins that hook into 'annotate' 'object'
	set_input('generic_comment', $text);
	
	// Create the comment
    $res = create_annotation($object_guid, 'generic_comment', $text, '', $user->guid, ACCESS_LOGGED_IN);

    if ($res) {
        return TRUE;
    } else {
        return FALSE;
    }
}

/**
 * Get the list of comments to given object represented by it's guid
 *
 * @param int $object_guid
 * @param int $limit
 * @param int $offset
 * @return array|int array of comments or 0 if there is no one
 */
function comments_list($object_guid, $limit = 10, $offset = 0) {
	// fetch comments
    $comments = elgg_get_annotations(array(
		'guid' => $object_guid, 
		'annotation_name' => 'generic_comment', 
		'limit' => $limit, 
		'offset' => $offset, 
		'order_by' => 'n_table.time_created desc'
	));

	// push comments
    $i = 0;
    $data = array();
    foreach ($comments as $comment) {
        $data[$i]['id'] = (int)$comment->id;
        $data[$i]['owner_id'] = (int)$comment->owner_guid;
        $user_details = get_user_details($comment->owner_guid);
        $data[$i] = array_merge($data[$i], $user_details);
        $data[$i]['text'] = strip_tags($comment->value);
        $data[$i]['time_created'] = $comment->time_created;
        $i ++;
    }

    if (count($data)) {
        return $data;
    } else {
        return 0;
    }
}
