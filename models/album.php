<?php

/**
 * Get the list of user's albums.
 * Outer api function
 *
 * @param int $user_guid
 * @return array
 */
function get_albums_list($user_guid = null) {

	// by default use logged in user
	if(empty($user_guid)) {
		$user = elgg_get_logged_in_user_entity();
		$user_guid = $user->guid;

	}

	// fetch albums
	$owner_albums = elgg_get_entities(array('types' => 'object', 'subtypes' => 'album', 'container_guids' => $user_guid, 'limit' => 0));

	// push data to returned array
	$data = array();
	foreach ($owner_albums as $album) {
		$tmp_data['title'] = $album['title'];
		$tmp_data['id'] = $album['guid'];
		$data[] = $tmp_data;
	}

	return $data;
}
