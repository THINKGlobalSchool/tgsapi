<?php

require_once 'functions.php';

/**
 * Post comment to given activity, represented by it's id.
 * Outer api method.
 *
 * @param int $activity_id
 * @param string $text
 * @return bool
 */
function comment_post($activity_id, $text) {
	// get an activity
    $activity = get_river_item($activity_id);
	// get object related to activity
    $object_id = $activity->object_guid;

	// get type of activity
    $type = get_activity_type($activity);

	// throw error on non-commentable activities
    require_once dirname(dirname(__FILE__)) .'/config.php';
    if (in_array($type, $black_list)) {
        return 'THIS TYPE OF ACTIVITY IS NOT COMMENTABLE';
    }

	// create comment
    $user = get_loggedin_user();
    $res = create_annotation($object_id, 'generic_comment', $text, '', $user->guid, ACCESS_LOGGED_IN);

    if ($res) {
        return TRUE;
    } else {
        return FALSE;
    }
}

/**
 * Get the list of comments to given object represented by it's guid
 *
 * @param int $object_id
 * @param int $limit
 * @param int $offset
 * @return array|int array of comments or 0 if there is no one
 */
function comments_list($object_id, $limit = 10, $offset = 0) {
	// fetch comments
    $comments = get_annotations($object_id, "", "", 'generic_comment', "", "", $limit, $offset, 'time_created desc');

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

?>
