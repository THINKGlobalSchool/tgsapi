<?php

/**
 * Retrieves item from the river
 *
 * @global stdClass $CONFIG
 * @param int $river_id river item id
 * @return stdClass
 */
function get_river_item($river_id) {

    // Get config
    global $CONFIG;

    // Construct main SQL
    $sql = "select er.*" .
                    " from {$CONFIG->dbprefix}river er" .
                    " where id = {$river_id} ";

    // Get data
    $data = get_data($sql);
    return $data[0];
}

/**
 * Get metadata of given entity
 *
 * @param int $entity_guid
 * @return array
 */
function get_object_details($entity_guid) {
    $metadata = elgg_get_metadata(array(
		'guid' => $entity_guid,
		'limit' => 0,
	));
	
	$details = array();

    foreach($metadata as $v) {
        $name = get_metastring($v->name_id);
        $name = str_replace(':', '_', $name);

        if ($name == 'tags') {
			$details[$name][] = $v->value;
        } else {
			$details[$name] = $v->value;
        }
    }
    return $details;
}

/**
 * Get details for user with given id
 *
 * @param int $user_guid
 * @param string $name name of the user in the result array key names
 * @param bool $full get full data or restricted
 * @param bool $with_latest_activity whether include or not latest user activities
 * @param int $latest_activity_limit
 * @param int $latest_activity_offset
 * @return array
 */
function get_user_details($user_guid, $name = 'author', $full = true, $with_latest_activity = false, $latest_activity_limit = 5, $latest_activity_offset = 0) {
    $user = get_user($user_guid);

    $data = array();
    if ($full) {
        $data[$name.'_id'] = (int)$user_guid;
        $data[$name] = $user->name;
        $data[$name.'_photo_url'] = $user->getIconURL('medium');
    } else {
        $data['photo_url'] = $user->getIconURL('medium');
    }

	if ($with_latest_activity) {	
		foreach (elgg_get_config('tgsapi_known_subtypes') as $subtype) {
			$subtype = sanitise_string($subtype);
			$wheres[] = "(rv.subtype = '$subtype')";
		}

		if (is_array($wheres) && count($wheres)) {
			$wheres = array(implode(' OR ', $wheres));
		}

		$wheres[0] = "({$wheres[0]})";
		
		// Get the users activity
		$options = array(
			'action_types' => array('create', 'comment'),
			'subject_guids' => array($user_guid),
			'limit' => $latest_activity_limit,
			'offset' => $latest_activity_offset,
			'wheres' => $wheres,
		);
		
		$user_activity = elgg_get_river($options);
		
		$latest = array();

		/// push activity details to the list
		foreach ($user_activity as $activity) {
			$latest[] = activity_details($activity);
		}

		$data['latest_activity'] = $latest;
	}
	
    return $data;
}

/**
 * Get type of activity
 *
 * @param stdClass $activity
 * @return string
 */
function get_activity_type($activity) {
    return (!empty($activity->subtype) ? $activity->subtype : $activity->type);
}

/**
 * Counts photos in the given album
 *
 * @global stdClass $CONFIG
 * @param int $albums_object_guid
 * @return int
 */
function get_count_photos_in_album($albums_object_guid) {
    // Get config
    global $CONFIG;

    // Construct main SQL
    $sql = "select count(e.guid) as counter" .
                    " from {$CONFIG->dbprefix}entities e" .
                    " where container_guid = {$albums_object_guid} ";

    // Get data
    $data = get_data($sql);
    return $data[0]->counter;
}

/**
 * Recursively removes tags and decodes html entities from array or string
 *
 * @param mixed $value string or array to sanitise
 * @return mixed inputed param with safe values
 */
function html_entity_decode_recursive(&$value) {
    if (is_array($value)) {
        foreach($value as &$v) {
            html_entity_decode_recursive(&$v);
        }
    } elseif (is_string($value)) {
        return $value = html_entity_decode(strip_tags($value), ENT_NOQUOTES, 'UTF-8');
    } else {
        return;
    }
}

/**
 * Sets geolocation to the given entity.
 * if empty latitude and longitude were passed, current user's geolocation will be used
 *
 * @param ElggObject $entity
 * @param string $lat
 * @param string $long
 */
function entity_set_lat_long(&$entity, $lat, $long) {   
    if (empty($lat) || empty($long)) {
        $user = get_object_details(elgg_get_logged_in_user_guid());
		if ($user['current_latitude'] && $user['current_longitude']) {
			$lat = $user['current_latitude'];
	        $long = $user['current_longitude'];
		}
    }
    $entity->setLatLong($lat, $long);
}

/**
 * Returns href url from <a> tag
 *
 * @param string $href tag
 * @param bool $without_http include or not 'http://' in result
 * @return string url or 'No url found' of fault
 */
function get_pure_url($href, $without_http = false) {
    $regexp = "<a\s[^>]*href=(\"??)(\s)?([^\" >]*?)\\1[^>]*>(.*)<\/a>";

    if (preg_match_all("/$regexp/siU", $href, $matches, PREG_SET_ORDER)) {
        return ($without_http ? str_replace('http://', '', $matches[0][3]) : $matches[0][3]);
    }

    return 'No url found';
}

/**
 * Get needed representation of geolocation data from exif info
 *
 * @param array $exifCoord geolocation array from exif
 * @param string $hemi 'W' or 'S'
 * @return int
 */
function getGps($exifCoord, $hemi) {

    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

    return $flip * ($degrees + $minutes / 60);

}

/**
 * Converts gps coordinates to float value
 *
 * @param string $coordPart
 * @return float
 */
function gps2Num($coordPart) {

    $parts = explode('/', $coordPart);

    if (count($parts) <= 0)
        return 0;

    if (count($parts) == 1)
        return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
}

/**
 * Checks whether user with given email and pass is authentificated by google
 *
 * For more info refer to google ClientLogin documentation
 *
 * @param string $username user's email
 * @param string $password
 * @return bool
 */
function is_authenticated_on_google($username, $password) {
	$source = 'tgs_elgg';
	$service = 'xapi';

	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
    $post_fields = "accountType=" . urlencode('HOSTED')
        . "&Email=" . urlencode($username)
        . "&Passwd=" . urlencode($password)
        . "&source=" . urlencode($source)
        . "&service=" . urlencode($service);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);

    $response = curl_exec($ch);
    curl_close($ch);

    if (strpos($response, '200 OK') === false) {
        return false;
    }

    preg_match("/(Auth=)([\w|-]+)/", $response, $matches);

    if (!$matches[2]) {
        return false;
    }

	return true;
}