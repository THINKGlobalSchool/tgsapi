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
 * @param int $user_id
 * @param string $name name of the user in the result array key names
 * @param bool $full get full data or restricted
 * @param bool $with_latest_activity whether include or not latest user activities
 * @param int $latest_activity_limit
 * @param int $latest_activity_offset
 * @return array
 */
function get_user_details($user_id, $name = 'author', $full = true, $with_latest_activity = false, $latest_activity_limit = 5, $latest_activity_offset = 0) {
	require_once dirname(dirname(__FILE__)) .'/config.php';

    $user = get_user($user_id);

    $data = array();
    if ($full) {
        $data[$name.'_id'] = (int)$user_id;
        $data[$name] = $user->name;
        $data[$name.'_photo_url'] = $user->getIconURL('medium');
    } else {
        $data['photo_url'] = $user->getIconURL('medium');
    }

	if ($with_latest_activity) {	
		foreach ($known_types as $subtype) {
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
			'subject_guids' => array($user_id),
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
 * Get the path for icon of given activity type
 *
 * @param string $subtype
 * @return string
 */
function get_category_icon($subtype) {
    $path = elgg_get_site_url() . 'category_icons/';
    return $path . $subtype . '.png';
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
 * Get last user's related entity
 *
 * @param string $subtype subtype of needed entity
 * @return ElggObject|false
 */
function get_last_user_entities($subtype) {
	// fetch 1 user's entity of given subtype (last modified will be at the top)
	$e = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => $subtype,
		'owner_guid' => elgg_get_logged_in_user_guid(),
		'limit' => 1
	));

	// if have needed, return, else return false
    if (is_array($e) && (count($e) == 1)) {
        return $e[0];
    } else {
        return false;
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
 * Rotates image based on exif info
 * - Not in use?
 *
 * @param string $filename
 */
function rotate_image_if_need($filename) {
    $exif = exif_read_data($filename);
    $ort = $exif['Orientation'];

    $image = new Image($filename);

    switch($ort)
    {
        case 1: // nothing
        break;

        case 2: // horizontal flip
            $image->flip(MIRROR_HORIZONTAL);
        break;

        case 3: // 180 rotate left
			$image->rotate();
            $image->rotate();
        break;

        case 4: // vertical flip
            $image->flip(MIRROR_VERTICAL);
        break;

        case 5: // vertical flip + 90 rotate right
            $image->flip(MIRROR_VERTICAL);
			$image->rotate();
        break;

        case 6: // 90 rotate right
            $image->rotate();
        break;

        case 7: // horizontal flip + 90 rotate right
            $image->flip(MIRROR_HORIZONTAL);
            $image->rotate();
        break;

        case 8: // 90 rotate left
            $image->rotate();
			$image->rotate();
			$image->rotate();
        break;
    }    


}

/**
 * Image class used for working with rotation of photo
 * - Only used by above function.. (so nowhere)
 */
class Image {
	/**
	 * @var string img filename
	 */
    private $src;   

	/**
	 * Initialize class
	 *
	 * @param string $src
	 */
    function __construct($src) {
        $this->src = $src;

        define("MIRROR_HORIZONTAL", 1);
        define("MIRROR_VERTICAL", 2);
        define("MIRROR_BOTH", 3);
    }

	/**
	 * Flips (mirrors) an image located in src
	 *
	 * Possible type values:
	 * 1 - mirror horizontal
	 * 2 - mirror vertical
	 * 3 - mirror both horizontal and vertical
	 *
	 * @param int $type
	 */
    function flip($type) {
		$source = imagecreatefromjpeg($this->src);
		$width = imagesx($source);
		$height = imagesy($source);
		$imgdest = imagecreatetruecolor($width, $height);

		for ($x=0 ; $x<$width ; $x++) {
			for ($y=0 ; $y<$height ; $y++) {
				switch ($type) {
					case MIRROR_HORIZONTAL:
						imagecopy($imgdest, $source, $width-$x-1, $y, $x, $y, 1, 1);
						break;
					case MIRROR_VERTICAL:
						imagecopy($imgdest, $source, $x, $height-$y-1, $x, $y, 1, 1);
						break;
					case MIRROR_BOTH:
						imagecopy($imgdest, $source, $width-$x-1, $height-$y-1, $x, $y, 1, 1);
						break;
				}
			}
		}

		imagejpeg($imgdest, $this->src);

		imagedestroy($source);
		imagedestroy($imgdest);
    }

	/**
	 * Rotates an image located in src 90CW
	 *
	 * @todo rewrite this method to support degrees.
	 *
	 * @param int $degrees
	 */
    function rotate($degrees) {
		$source = imagecreatefromjpeg($this->src);
		$width = imagesx($source);
		$height = imagesy($source);

        $result = @imagecreatetruecolor($height, $width);
        if($result)
        {
        for ($i = 0; $i < $width; $i++)
            for ($j = 0; $j < $height; $j++)
            {
                $ref = imagecolorat($source, $i, $j);
                imagesetpixel($result, ($height - 1) - $j, $i, $ref);
            }
        }
        imagejpeg($result, $this->src);

        imagedestroy($source);
        imagedestroy($result);
    }
}
/**
 * Checks is user with given guid activated
 *
 * @global stdClass $CONFIG
 * @param int $guid
 * @return bool
 *
 * NOTE:  This function has been replaced by elgg_get_user_validation_status
 *
 */
function is_user_activated($guid) {
    global $CONFIG;

	// active status is stored in metadata 'validated'
    $q = 'SELECT count(md.id) as cnt
            FROM elgg_metadata md
                JOIN elgg_metastrings ms1 on md.name_id = ms1.id
                JOIN elgg_metastrings ms2 on md.value_id = ms2.id
            WHERE
                ms1.string = "validated" AND
                ms2.string = "1" AND
                md.enabled = "yes" AND
                md.entity_guid = ' . (int) $guid;

    $data = get_data($q);

    return  ((bool) $data[0]->cnt);
}

/**
 * Get the guid for user with provided username and pass
 *
 * @global stdClass $CONFIG
 * @param string $username
 * @param string $password
 * @return int|false user guid or false if there is no such user entity
 */
function get_user_guid_if_exists($username, $password) {
    global $CONFIG;

	// password stored in db is a md5 hash of pure pass and salt, so we fetch salt by username first
    $username = mysql_real_escape_string($username);
    $q = "SELECT u.salt, u.guid FROM {$CONFIG->dbprefix}users_entity u WHERE u.username = '" . $username . "'";
    $data = get_data($q);

    if ($data) {
        $password = md5($password . $data[0]->salt);
        $q = "SELECT u.guid as guid FROM {$CONFIG->dbprefix}users_entity u WHERE u.guid = " . $data[0]->guid . " AND u.password = '" . $password . "'";
        $guid_data = get_data($q);

        if ($guid_data) {
            return (int) $guid_data[0]->guid;
        }
    }

    return false;
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