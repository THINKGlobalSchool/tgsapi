<?php

/**
 * Post user's current location for tracking.
 *
 * @param float $lat
 * @param float $long
 * @return true
 */
function track_location($lat, $long) {
	// users geotags stored in entity metadata current_latitude and current_longitude

	// get logged in user
	$user_id = get_loggedin_userid();

	// prepare data
	$metadata = array(
		'current_latitude' => $lat,
		'current_longitude' => $long
	);

	// find each metadata
	// if there is no, create it
	foreach($metadata as $name => $value) {
		$entry = get_metadata_byname($user_id, $name);

		if (!$entry) {
			create_metadata($user_id, $name, $value, '', 0);
		} else {
			$entry->value = $value;
			$entry->save();
		}
	}
	
    return true;
}
