<?php
/**
 * Elgg TGS REST API Plugin
 *
 * @package ElggTGSAPI
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright Think Global School 2009-2010
 * @link http://www.thinkglobalschool.com
 */

// require models
require_once 'models/auth.php';
require_once 'models/activity.php';
require_once 'models/comment.php';
require_once 'models/photo.php';
require_once 'models/album.php';
require_once 'models/profile.php';
require_once 'models/wire.php';
require_once 'models/video.php';
require_once 'models/location.php';
require_once 'models/stats.php';
require_once 'models/todo.php';

// Include functions
require_once 'models/functions.php';

// Register event
elgg_register_event_handler('init', 'system', 'tgsapi_init');

/**
 * Initialize api to register api functions.
 * To register new function add expose_function definition
 *
 */
function tgsapi_init() {		
	// Get the authentification token
	expose_function("auth.get_infinity_token", "auth_get_infinity_token", array(
			'username' => array ('type' => 'string'),
			'password' => array ('type' => 'string'),
		), elgg_echo('auth.gettoken'),	'POST', false, false);

	// Get the authentification token from google
	expose_function("auth.get_google_token", "auth_get_google_token", array(
			'username' => array ('type' => 'string'),
			'password' => array ('type' => 'string'),
		), elgg_echo('auth.gettoken'),	'POST', false, false);

	// Get activity list
	expose_function("activity.list", "activity_list", array (
            "limit" => array (
                "type" => 'int',
                "required" => false
            ),
            "offset" => array (
                "type" => 'int',
                "required" => false
            )
		), 'List all activities', 'GET', false, true);

	// Fetch ToDo list
	expose_function("todo.list", "todo_list", array (
			"status" => array (
					"type" => 'string',
					"required" => false
				),
			"limit" => array (
                "type" => 'int',
                "required" => false
            ),
            "offset" => array (
                "type" => 'int',
                "required" => false
			),
            "user_role" => array (
                "type" => 'string',
                "required" => false
			)
		), 'Fetch todos', 'GET', false, true);

	//Accept todo
	expose_function("todo.accept", "todo_accept", array (
			"todo_id" => array (
                "type" => 'int',
                "required" => true
            )
		), 'Accept todo', 'POST', false, true);

	//Copmlete todo
	expose_function("todo.complete", "todo_complete", array (
			"todo_id" => array (
                "type" => 'int',
                "required" => true
            )
		), 'Complete todo', 'POST', false, true);

	// Post comment
	expose_function("comment.add", "comment_post", array (
            "activity_id" => array (
                "type" => 'int',
                "required" => true
            ),
            "text" => array (
                "type" => 'string',
                "required" => true
            )
		), 'Post comment', 'POST', false, true);

	// Get user profile
	expose_function("profile.show", "profile_details", array (
            "user_id" => array (
                "type" => 'int',
                "required" => true
            ),
			"limit" => array (
                "type" => 'int',
                "required" => false
            ),
            "offset" => array (
                "type" => 'int',
                "required" => false
			)
		), 'View user profile', 'GET', false, true);

	// Post photo
	expose_function("photo.add", "photo_add", array (
            "title" => array (
                "type" => 'string',
                "required" => false
            ),
            "caption" => array (
                "type" => 'string',
                "required" => false
            ),
            "tags" => array (
                "type" => 'string',
                "required" => false
            ),
            "album_id" => array (
                "type" => 'integer',
                "required" => false
            ),
            "lat" => array(
                'type' => 'string',
                "required" => false
            ),
            "long" => array(
                'type' => 'string',
                "required" => false
            )
		), 'Add photo', 'POST', false, true);

	// Post video
	expose_function("video.add", "video_add", array (
            "title" => array (
                "type" => 'string',
                "required" => false
            ),
            "caption" => array (
                "type" => 'string',
                "required" => false
            ),
            "tags" => array (
                "type" => 'string',
                "required" => false
            ),
            "lat" => array(
                'type' => 'string',
                "required" => false
            ),
            "long" => array(
                'type' => 'string',
                "required" => false
            )
		), 'Add video', 'POST', false, true);

	// Get comments list for given object
	expose_function("comments.list", "comments_list", array (
            "object_id" => array (
                "type" => 'int',
                "required" => true
            ),
            "limit" => array (
                "type" => 'int',
                "required" => false
            ),
            "offset" => array (
                "type" => 'int',
                "required" => false
            )
		), 'Get comments for object', 'GET', false, true);

	// Post text message to the wire
	expose_function ("thewire.post", "api_post_to_wire", array (
            "text" => array(
                'type' => 'string'
            ),
            "lat" => array(
                'type' => 'string',
                "required" => false
            ),
            "long" => array(
                'type' => 'string',
                "required" => false
            )
        ), 'Post to the wire', 'POST', false, true);
        
	// Get photos list in album
	expose_function("photo.list", "get_photos_list_in_album", array (
			"album_guid" => array (
					"type" => 'int',
					"required" => true
			)
		), 'Get photos list in album', 'GET', false, true);

	// Get albums list
	expose_function("albums.list", "get_albums_list", array (
			"user_id" => array (
					"type" => 'int',
					"required" => false
			)
		), 'Get photos list in album', 'GET', false, true);

	// Track iphone location
	expose_function ("location.track", "track_location", array (
            "lat" => array(
                'type' => 'string',
                "required" => true
            ),
            "long" => array(
                'type' => 'string',
                "required" => true
            )
		), 'Track location', 'POST', false, true);
	
	// Expose public site stats function
	expose_function("site.stats", "get_site_stats", array(
			'photo_limit' => array(
					'type' => 'string', 
					'required' => true
			)
		), "Get Site Stats", 'GET', false, false);

	// Add method, that should return an amount of {status} todos assigned to me
	expose_function("todos.count", "get_todos_count", array(
			'status' => array (
					'type' => 'string',
					'required' => false
				),
			'user_role' => array (
					'type' => 'string',
					'required' => false
				)
		), "Get todos count", 'GET', false, true);

	// Get certain todo details
	expose_function("todo.show", "todo_show", array(
			"todo_id" => array (
					"type" => 'int',
					"required" => true
			)
		), "Get todo detail", 'GET', false, true);

	elgg_register_action("tgsapi/delete_token", elgg_get_plugins_path() . "tgsapi/actions/delete_token.php", 'admin');

	// Admin menu
	elgg_register_event_handler('pagesetup','system','tgsapi_adminmenu');
}

/**
 * Sets up API admin menu. Triggered on pagesetup.
 */
function tgsapi_adminmenu() {
	if (elgg_in_context('admin')) {
		elgg_register_admin_menu_item('administer', 'tokens', 'tgsapi');
	}
}
