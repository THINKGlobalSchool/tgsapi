<?php
require_once 'functions.php';

/**
 * Get user profile.
 * Outer api method.
 *
 * @global stdClass $CONFIG
 * @param int $user_id
 * @return array
 */
function profile_details($user_id, $limit = 5, $offset = 0) {
    // user exists?
	$user = get_user($user_id);
    if (!$user) {
        return 'No user';
    }

	// fetch details of user obj
	$details = get_object_details($user_id);
    $real_details = array();

	// get details
    global $CONFIG;
    foreach($CONFIG->profile_fields as $shortname => $valtype) {
        if (!empty($details[$shortname])) {
			$real_details[$shortname] = $details[$shortname];
        }
    }

	// add some details
    $real_details['name'] = $user['name'];
    $real_details['email'] = $user['email'];

	// if we have full description, briefdescription not needed
    if (!empty($real_details['description'])) {
        unset($real_details['briefdescription']);
    }

	// fetch additional details
    $user_details = get_user_details($user_id, 'author' ,false, true, $limit, $offset);

	// merge all details and decode it safe
    $profile =  array_merge($real_details, $user_details);
    html_entity_decode_recursive($profile);
    
    return $profile;
}
