<?php
require_once 'functions.php';

/**
 * Posts given message to the wire.
 * Also sets geolocation to the post.
 * Outer api method.
 *
 * @param string $text text to be posted
 * @param float $lat current latitude
 * @param float $long current longitude
 * @return bool operation success
 */
function api_post_to_wire($text, $lat = NULL, $long = NULL) {
	// access level
	$access = ACCESS_LOGGED_IN;
	
	// Elgg 1.8 requires the user_id now aswell
	$user_guid = elgg_get_logged_in_user_guid();

	// on production we use function tgswire_save_post()
	// locally thewire_save_post() function is used
    //$ret_val = tgswire_save_post($text, $access, 0, "tgsapi");
    $ret_val = thewire_save_post($text, $user_guid, $access, 0, "tgsapi");

    // get the just created entity and set geolocation
    $entity = get_last_user_entities('thewire');

    if ($entity) {
        entity_set_lat_long($entity, $lat, $long);
    }

    return $ret_val;
}
