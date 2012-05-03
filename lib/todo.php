<?php
/**
 * Get the list of todos.
 * Outer api method.
 *
 * @param string $status todo status: ('complete', 'incomplete')
 * @param int $limit how many activities should be returned
 * @param int $offset offset of the list
 * @param string @user_role  ('all', 'assigner', 'assignee')
 * @return array
 */
function todo_list($status = 'incomplete', $limit = 10, $offset = 0, $user_role = 'all') {
	// get user guid
	$user_guid = elgg_get_logged_in_user_guid();
	
	// Using TODO lib method :D:D:D
	$options = array(
		'container_guid' => $user_guid,
		'status' => $status,
		'context' => $user_role,
		'sort_order' => 'DESC',
		'list' => FALSE,
	);

	$todos = get_todos($options);

	// push todo details to the list
	$data = array();
    foreach ($todos as $todo) {
		$data[] = get_todo_details($todo);
    }
	
    return $data;
}

/**
 * Get todo details.
 *
 * @param ElggObject $todo
 * @return array
 */
function get_todo_details($todo) {
	$data = array();
	$user_guid = elgg_get_logged_in_user_guid();
	// todo assigner
	$owner = $todo->getOwnerEntity();

	// get status of todo
	$status = 'Incompleted';
	if (have_assignees_completed_todo($todo->guid)) {
		$status = 'Completed';
	}

	// is me an assigner. Necessery?
	$is_me_an_assigner = false;
	if ($owner->guid == $user_guid) {
		$is_me_an_assigner = true;
	}

	// some defaults
	$is_me_in_assignees = false;
	$accetted_by_me = false;
	$completed_by_me = false;

	// start of return accumulating
	$data['id'] = (int) $todo->guid;

	// Get the todo's river data (not sure why we do it this way..)
	$options = array(
		'subtypes' => array('todo'),
		'object_guids' => array($todo->guid),
		'limit' => 1,
		'offset' => 0,
	);

	$activity = elgg_get_river($options);
	
	$data['activity_id'] = $activity[0]->id;
	$data['status'] = $status;
	$data['title'] = $todo->title;
	$data['description'] = $todo->description;
	$data['created_at'] = $todo->time_created;
	$data['due_date'] = $todo->due_date;
	$data['assigned_by'] = $owner->name;
	$data['is_me_an_assigner'] = (bool) $is_me_an_assigner;
	$data['url'] = $todo->getURL();

	// assignees array
	$data['assignees'] = array();
	$assignees = get_todo_assignees($todo->guid);
	foreach ($assignees as $user) {
		$user_data = array();
		$user_data['assignee_id'] = (int) $user->guid;
		$user_data['assignee_name'] = $user->name;

		// is todo completed by assignee
		$completed = false;
		if (has_user_submitted($user->guid, $todo->guid)) {
			$completed = true;

			// if it was submited by him, get submission data
			if ($submission = get_user_submission($user->guid, $todo->guid)) {
				$data['date_submitted'] = $submission->time_created;
				$data['submission_url'] = $submission->getURL();
			}
		}

		// set completed and accepted status
		$user_data['completed'] = $completed;
		$user_data['accepted'] = (bool) has_user_accepted_todo($user->guid, $todo->guid);
		
		// push data
		$data['assignees'][] = $user_data;

		// for future purposes
		// if current assignee is todo assigner, get some info about self accept and completness
		if ($user->guid == $user_guid) {
			$is_me_in_assignees = true;
			$accetted_by_me = $user_data['accepted'];
			$completed_by_me = $user_data['completed'];
		}
	}

	// get metadata
	$todo_meta = get_object_details($todo->guid);

	// If we've got tags
	if ($todo_meta['tags']) {
		$data['tags'] = @$todo_meta['tags'];
	}
	
	$data['return_required'] = isset($todo_meta['return_required']) && $todo_meta['return_required'] ? true : false;

	// not to have tags without closures
	// iphone team asked about it
	if ($is_me_an_assigner && !$is_me_in_assignees) {
		$accetted_by_me = '';
		$completed_by_me = '';
	}

	$data['accepted_by_me'] = $accetted_by_me;
	$data['completed_by_me'] = $completed_by_me;
	$data['comments_count'] = $todo->countComments();

	// sanitise
	html_entity_decode_recursive($data);
	$data['description_html'] = html_entity_decode($todo->description, ENT_NOQUOTES, 'UTF-8');

	return $data;
}

/**
 * Accepts the todo with give id
 *
 * @param int $todo_guid
 * @return bool operation success
 */
function todo_accept($todo_guid) {

	$todo = get_entity($todo_guid);

	// could not find todo item
	if (!$todo) {
		return false;
	}

	$user_guid = elgg_get_logged_in_user_guid();
	$accepted = user_accept_todo($user_guid, $todo->guid);

	return ($accepted ? true : false);
}



/**
 * Get the count of todos.
 * Outer api method.
 *
 * @param string $status todo status: ('unaccepted', 'accepted')
 * @param string @user_role  ('all', 'assigner', 'assignee')
 * @return int
 */
function get_todos_count($status = 'unaccepted', $user_role = 'assignee') {
	// get user guid
	$user_guid = elgg_get_logged_in_user_guid();

	return count_unaccepted_todos($user_guid);
}


/**
 * Get todo details.
 * Outer api method.
 *
 * @param int $todo_guid
 * @return array
 */
function todo_show($todo_guid) {
	$todo = get_entity($todo_guid);
	
	// could not find todo item
	if (!$todo || $todo->getSubtype() != 'todo') {		
		return false;
	}

	return get_todo_details($todo);
}



/**
 * Set 'completed' for a user on given todo
 *
 * @param int $user_guid
 * @param int $todo_guid
 * @return bool
 */
function todo_complete($todo_guid) {
	

	$todo = get_entity($todo_guid);
	$user_guid = elgg_get_logged_in_user_guid();

	if ($todo && $todo->getSubtype() == "todo") {

		$submission = new ElggObject();
		$submission->title = sprintf(elgg_echo('todo:label:submissiontitleprefix'), $todo->title);
		$submission->subtype = "todosubmission";
		$submission->owner_id = $user_guid;
		$submission->todo_guid = $todo_guid;
		// NOTE: Access ID and ACL's handled by an event listener

		// Save
		if (!$submission->save()) {			
			return false;
		}

		add_entity_relationship($submission->getGUID(), SUBMISSION_RELATIONSHIP, $todo_guid);
		
		// Add a relationship stating that the user has completed the todo
		add_entity_relationship($user_guid, COMPLETED_RELATIONSHIP, $todo_guid);

		// Accept the todo when completing (if not already accepted)
		user_accept_todo($user_guid, $todo_guid);

		// River
		add_to_river('river/object/todosubmission/create', 'create', $user_guid, $submission->getGUID());

		$user = get_user($user_guid);
		notify_user($todo->owner_guid,
			elgg_get_site_entity()->guid,
			elgg_echo('todo:email:subjectsubmission', array($user->name, $todo->title)),
			elgg_echo('todo:email:bodysubmission', array($user->name, $todo->title, $todo->getURL()))
		);
		return true;
	}
	return false;
}
