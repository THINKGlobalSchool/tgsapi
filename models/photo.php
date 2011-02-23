<?php

require_once 'functions.php';

/**
 * Get guid of mobile uploads album.
 * If it does not exist, create it.
 *
 * @return int
 */
function find_or_create_mobile_album() {
    // Get user's albums
    $albums = elgg_get_entities(array('types' => 'object', 'subtypes' => 'album', 'container_guids' => get_loggedin_userid(), 'limit' => $number));

    $found = false;
    $album_id = null;

	// search for mobile uploads album
    define(MOBILE_ALBUM, 'Mobile uploads');
    foreach ($albums as $album) {
        if ($album->title == MOBILE_ALBUM) {
            $found = true;
            $album_id = $album->guid;
            break;
        }
    }

    if ($found) {
        return $album_id;
    } else {
		// need to create an album
        // Initialise a new ElggObject
        $album = new ElggObject();
        // Tell the system it's an album
        $album->subtype = "album";

        // Set its owner to the current user
        $album->container_guid = get_loggedin_userid();
        $album->owner_guid = get_loggedin_userid();
        $album->access_id = ACCESS_LOGGED_IN;
        // Set its title and description appropriately
        $album->title = MOBILE_ALBUM;
        $album->description = '';

        // we catch the adding images to new albums in the upload action and throw a river new album event
        $album->new_album = TP_NEW_ALBUM;

        // Before we can set metadata, we need to save the album
        $album_id = $album->save();
        trigger_elgg_event('add', 'tp_album', $album);
        return $album_id;
    }
}

/**
 * Post photo to the site.
 * Outer api method.
 *
 * Geotagging:
 * If the photo has geotags in exif, they will be used as photo's entity location.
 * If it has no, passed location will be used.
 * If there are no neather exif geotag, nor passed location, current user's location will be used.
 *
 * Not required params:
 * There were some issues with using not required params in elgg expose_function, so we'll get them
 * directly from input (using get_input() function)
 *
 * @global stdClass $CONFIG
 * @param string $title photo's title
 * @param string $caption photo's caption
 * @param string $tags tags (comma separated)
 * @param int $album_id album guid where the photo should be placed. If empty, it'll be uploaded into the album mobile uploads
 * @param float $lat latitude
 * @param float $long longitude
 * @return true|string true on success and string (message) on error
 */
function photo_add($title, $caption = '', $tags = '', $album_id, $lat, $long) {
    global $CONFIG;

	// if there are no files, return error message
    if (count($_FILES) == 0) {
        return 'no files';
    }

    // get geotag
    foreach($_FILES as $file) {   
        $exif = exif_read_data($file['tmp_name']);

		// if have exif values, use them, else use passed
        if (isset($exif["GPSLongitude"])) {
            $lon = getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
            $lat = getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
        } else {
            $lat = (string) get_input('lat');
            $lon = (string) get_input('long');
        }
    } 

	// get album id
    $album_id = get_input('album_id');
	// if tere is no, use album mobile uploads (it'll be created if do not exists)
    if (empty($album_id)) {
        $album_id = find_or_create_mobile_album();
    }

	// we'll use tidypics uploader, so we need to set REQUEST as when photo is uploaded from site
	$_REQUEST['album_guid'] = $album_id;
    $_REQUEST['container_guid'] = $album_id;
    $_REQUEST['access_id'] = ACCESS_LOGGED_IN;

	// tidypics should not forward to the site
	// if it redirects to the edit details screen, it means that our patch for tidypics was not applyed
    $not_forward = true;

	// make upload
    require_once ($CONFIG->pluginspath . "tidypics/actions/upload.php");

	// now we should emulate edit details screen
	// set up another REQUEST params
    $title = strlen(get_input('title')) != 0 ? get_input('title') : '';

    $_REQUEST['title'] = array($title);
    $_REQUEST['caption'] = array(get_input('caption'));
    $_REQUEST['tags'] = array(get_input('tags'));
    $_REQUEST['image_guid'] = $uploaded_images;
    $_REQUEST['container_guid'] = $album_id;

	// set params to the posted photo
    require_once ($CONFIG->pluginspath . "tidypics/actions/edit_multi.php");

    // set geotag
    $entity = get_last_user_entities('image');
    if ($entity) {
        entity_set_lat_long($entity, $lat, $lon);
    }

    return true;
}

/**
 * Get the list of photos in given album represented by it's guid
 *
 * @global stdClass $CONFIG
 * @param int $album_guid
 * @return array
 */
function get_photos_list_in_album($album_guid) {
    global $CONFIG;

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
            $tmp_data['image_url'] =  $CONFIG->wwwroot.'pg/photos/thumbnail/'.(int)$image->guid.'/large';

            $data[] = $tmp_data;
        }
    } else {
        // if there are no photos in the album
        return null;
    }

    return $data;
}
?>
