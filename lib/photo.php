<?php
/**
 * Get guid of mobile uploads album.
 * If it does not exist, create it.
 *
 * @return int
 */
function find_or_create_mobile_album() {
    // Get user's albums
    $albums = elgg_get_entities(array(
		'types' => 'object', 
		'subtypes' => 'album', 
		'container_guids' => elgg_get_logged_in_user_guid(), 
		'limit' => 0
	));

    $found = false;
    $album_guid = null;

	// search for mobile uploads album
    define(MOBILE_ALBUM, 'Mobile uploads');
    foreach ($albums as $album) {
		if ($album->title == MOBILE_ALBUM) {
			$found = true;
			$album_guid = $album->guid;
			break;
		}
    }

    if ($found) {
		return $album_guid;
    } else {
		// need to create an album
		// Initialise a new ElggObject
		$album = new ElggObject();
		// Tell the system it's an album
		$album->subtype = "album";

		// Set its owner to the current user
		$album->container_guid = elgg_get_logged_in_user_guid();
		$album->owner_guid = elgg_get_logged_in_user_guid();
		$album->access_id = ACCESS_LOGGED_IN;
		// Set its title and description appropriately
		$album->title = MOBILE_ALBUM;
		$album->description = '';

		// we catch the adding images to new albums in the upload action and throw a river new album event
		$album->new_album = TP_NEW_ALBUM;

		// Before we can set metadata, we need to save the album
		$album_guid = $album->save();
		elgg_trigger_event('add', 'tp_album', $album);
		return $album_guid;
    }
}

/**
 * Post photo to the site.
 * Outer api method.
 *
 * Geotagging:
 * If the photo has geotags in exif, they will be used as photo's entity location.
 * If it has no, passed location will be used.
 * If there are no exif geotag, nor passed location, current user's location will be used.
 *
 * Not required params:
 * There were some issues with using not required params in elgg expose_function, so we'll get them
 * directly from input (using get_input() function)
 *
 * @param string $title photo's title
 * @param string $caption photo's caption
 * @param string $tags tags (comma separated)
 * @param int $album_guid album guid. If empty, it'll be uploaded into the album 'mobile uploads'
 * @return true|string true on success and string (message) on error
 */
function photo_add($title, $caption = '', $tags = '', $album_guid = null) {
	elgg_load_library('tidypics:upload');

	// Check to make sure something was uploaded, if not probably POST limit exceeded
	if (empty($_FILES)) {
		trigger_error('Tidypics warning: user exceeded post limit on image upload', E_USER_WARNING);
		return elgg_echo('tidypics:exceedpostlimit');
	}

	// Get photo file
	$file = $_FILES['photo'];

	// get geotag
	$exif = exif_read_data($file['tmp_name']);

	// if have exif values, use them, else use passed
	if (isset($exif["GPSLongitude"])) {
		$lon = getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']            );
		$lat = getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
	} else {
		$lat = (string) get_input('lat');
		$lon = (string) get_input('long');
	}

	// if there is no album, use album mobile uploads (it'll be created if it doesn't exist)
	if (empty($album_guid)) {
		$album_guid = find_or_create_mobile_album();
	}

	// Get the album entity
	$album = get_entity($album_guid);


	if ($tags) {
		$tags = string_to_tag_array($tags);
		$tags = array_unique(array_merge($tags, $album->tags));
	} else {
		$tags = $album->tags;
	}

	// Create image entity
	$mime = tp_upload_get_mimetype($file['name']);
	if ($mime == 'unknown') {
		return elgg_echo('tidypics:not_image');
	}

	$image = new TidypicsImage();
	$image->container_guid = $album->guid;
	$image->title = $title;
	$image->description = $caption;
	$image->setMimeType($mime);
	$image->access_id = $album->access_id;
	$image->tags = $tags;

	try {
		$image->save($file);
		$album->prependImageList(array($image->guid));

		// Skip batching
		add_to_river('river/object/image/create', 'create', $image->getOwnerGUID(), $image->getGUID());
		

		entity_set_lat_long($image, $lat, $lon);

		// All good!
		return true;
	} catch (Exception $e) {
		// Not good, return exception
		return $e->getMessage();
	}
}

/**
 * Get the list of photos in given album represented by it's guid
 *
 * @param int $album_guid
 * @return array
 */
function get_photos_list_in_album($album_guid) {
	// fetch images
	// album is their container
	$images = elgg_get_entities(array('types' => 'object', 'subtypes' => 'image', 'container_guids' => $album_guid, 'limit' => 999));

	$data = array();

	// if there are any images
	if (is_array($images)) {
		// push them into result array
		foreach ($images as $image) {
			$tmp_data['id'] = $image->guid;
			$tmp_data['parent_id'] = $image->container_guid;
			$tmp_data['title'] = $image->title;
			$tmp_data['description'] = $image->description;
			$tmp_data['time_created'] = $image->time_created;
			$tmp_data['image_url'] =  elgg_get_site_url().'pg/photos/thumbnail/'.(int)$image->guid.'/large';

			$data[] = $tmp_data;
		}
	} else {
		// if there are no photos in the album
		return null;
	}

	return $data;
}
