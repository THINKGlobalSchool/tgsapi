<?php

/**
 * Get the list of user's albums.
 * Outer api function
 *
 * @param int $user_id
 * @return array
 */
function get_albums_list($user_id = null) {

	// by default use logged in user
	if(empty($user_id)) {
		$user = get_loggedin_user();
		$user_id = $user->guid;

	}

	// fetch albums
	$owner_albums = elgg_get_entities(array('types' => 'object', 'subtypes' => 'album', 'container_guids' => $user_id, 'limit' => 999));

	// push data to returned array
	$data = array();
	foreach ($owner_albums as $album) {
		$tmp_data['title'] = $album['title'];
		$tmp_data['id'] = $album['guid'];
		$data[] = $tmp_data;
	}

	return $data;

}

?>
