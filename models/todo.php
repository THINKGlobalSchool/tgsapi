<?php

require_once 'functions.php';

/**
 * Get the list of todos.
 * Outer api method.
 *
 * @param string $status todo status: ('completed', 'incompleted')
 * @param int $limit how many activities should be returned
 * @param int $offset offset of the list
 * @param string @user_role  ('all', 'assigner', 'assignee')
 * @return array
 */
function todo_list($status = 'incompleted', $limit = 10, $offset = 0, $user_role = 'all') {
	// get user id
	$current_user_id = get_loggedin_userid();
	// cast completement status to bool
	$completed = ($status == 'completed' ? true : false);

	// we do not use elgg methods to get todo entities because they do not support filtering by wanted todo status
	// so the limit-offset feature could not be implemented or it will cause serious performance problems
	$todos = get_todo_entities_ordered_by_date_due($current_user_id, $completed, $limit, $offset, $user_role);

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
	$current_user_id = get_loggedin_userid();
	// todo assigner
	$owner = $todo->getOwnerEntity();

	// get status of todo
	$status = 'Incompleted';
	if (have_assignees_completed_todo($todo->guid)) {
		$status = 'Completed';
	}

	// is me an assigner. Necessery?
	$is_me_an_assigner = false;
	if ($owner->guid == $current_user_id) {
		$is_me_an_assigner = true;
	}

	// some defaults
	$is_me_in_assignees = false;
	$accetted_by_me = false;
	$completed_by_me = false;

	// start of return accumulating
	$data['id'] = (int) $todo->guid;

	$activity = get_activities('todo', '', 1, 0, '', (int)$todo->guid);	
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
		if ($user->guid == $current_user_id) {
			$is_me_in_assignees = true;
			$accetted_by_me = $user_data['accepted'];
			$completed_by_me = $user_data['completed'];
		}
	}

	// get metadata
	$todo_meta = get_object_details($todo->guid);

	
	$data['tags'] = @$todo_meta['tags'];
	$data['return_required'] = isset($todo_meta['return_required']) && $todo_meta['return_required'] ? true : false;

	// not to have tags without closures
	// iphone team asked about it
	if ($is_me_an_assigner && !$is_me_in_assignees) {
		$accetted_by_me = '';
		$completed_by_me = '';
	}

	$data['accepted_by_me'] = $accetted_by_me;
	$data['completed_by_me'] = $completed_by_me;
	$data['comments_count'] = (int) get_comments_count($todo->guid);

	// sanitise
	html_entity_decode_recursive($data);
	$data['description_html'] = html_entity_decode($todo->description, ENT_NOQUOTES, 'UTF-8');

	return $data;
}

/**
 * Accepts the todo with give id
 *
 * @param int $todo_id
 * @return bool operation success
 */
function todo_accept($todo_id) {

	$todo = get_entity($todo_id);

	// could not find todo item
	if (!$todo) {
		return false;
	}

	$current_user_id = get_loggedin_userid();
	$accepted = user_accept_todo($current_user_id, $todo->guid);

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
	// get user id
	$current_user_id = get_loggedin_userid();

	$count = get_todo_entities_ordered_by_date_due($current_user_id, '',  '', '', $user_role, true, $status);

	return $count;
	exit;
}


/**
 * Get todo details.
 * Outer api method.
 *
 * @param int $todo_id
 * @return array
 */
function todo_show($todo_id) {
	$todo = get_entity($todo_id);
	
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
	$current_user_id = get_loggedin_userid();

	if ($todo && $todo->getSubtype() == "todo") {

		$submission = new ElggObject();
		$submission->title = sprintf(elgg_echo('todo:label:submissiontitleprefix'), $todo->title);
		$submission->subtype = "todosubmission";
		$submission->owner_id = $current_user_id;
		$submission->todo_guid = $todo_guid;
		// NOTE: Access ID and ACL's handled by an event listener

		// Save
		if (!$submission->save()) {			
			return false;
		}

		add_entity_relationship($submission->getGUID(), SUBMISSION_RELATIONSHIP, $todo_guid);
		
		// Add a relationship stating that the user has completed the todo
		add_entity_relationship($current_user_id, COMPLETED_RELATIONSHIP, $todo_guid);

		// Accept the todo when completing (if not already accepted)
		user_accept_todo($current_user_id, $todo_guid);

		// River
		add_to_river('river/object/todosubmission/create', 'create', $current_user_id, $submission->getGUID());

		$user = get_user($current_user_id);
		notify_user($todo->owner_guid,
			$CONFIG->site->guid,
			elgg_echo('todo:email:subjectsubmission'),
			sprintf(elgg_echo('todo:email:bodysubmission'),
			$user->name,
			$todo->title,
			$todo->getURL())
		);


		return true;
	}

	return false;
}

/**
 * Set 'completed' for a user on given todo
 *
 * @param int $user_guid
 * @param int $todo_guid
 * @return bool
 */
function todo_complete_all($todo_guid) {

	$todo = get_entity($todo_guid);

	if ($todo && $todo->getSubtype() == "todo") {
		$todo->manual_complete = true;
		if ($todo->save()) {
			// Grab the todo's assignees and mark each as having accepted the todo
			$assignees = get_todo_assignees($todo_guid);
			foreach ($assignees as $assignee) {
				user_accept_todo($assignee->getGUID(), $todo_guid);
			}

			// Success message
			return true;
		}
	}

	return false;
}

?>
