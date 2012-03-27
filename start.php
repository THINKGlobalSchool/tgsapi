<?php
/**
 * Elgg TGS REST API Plugin
 *
 * @package ElggTGSAPI
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright Think Global School 2009-2010
 * @link http://www.thinkglobalschool.com
 *
 */

// Only register in REST context :D
if (elgg_get_context() == 'rest') {
	// Register init events
	elgg_register_event_handler('init', 'system', 'tgsapi_init');
	elgg_register_event_handler('init', 'system', 'tgsapi_expose_functions', 501);
}

// Global Init
elgg_register_event_handler('init', 'system', 'tgsapi_global_init');

// DEBUG
//date_default_timezone_set('America/New_York');

/**
 *  Init Plugin
 */
function tgsapi_init() {
	// Register and load libraries (Some are unused currently and are commented out)
	$lib_path = elgg_get_plugins_path() . 'tgsapi/lib/';
	elgg_register_library('tgsapi:activity', $lib_path . 'activity.php');
	elgg_register_library('tgsapi:album', $lib_path . 'album.php');
	elgg_register_library('tgsapi:auth', $lib_path . 'auth.php');
	elgg_register_library('tgsapi:comment', $lib_path . 'comment.php');
	elgg_register_library('tgsapi:helpers', $lib_path . 'helpers.php');
	//elgg_register_library('tgsapi:location', $lib_path . 'location.php');
	elgg_register_library('tgsapi:photo', $lib_path . 'photo.php');
	elgg_register_library('tgsapi:profile', $lib_path . 'profile.php');
	elgg_register_library('tgsapi:roles', $lib_path . 'roles.php');
	elgg_register_library('tgsapi:stats', $lib_path . 'stats.php');
	elgg_register_library('tgsapi:todo', $lib_path . 'todo.php');
	//elgg_register_library('tgsapi:video', $lib_path . 'video.php');
	elgg_register_library('tgsapi:wire', $lib_path . 'wire.php');

	elgg_load_library('tgsapi:helpers'); // Load first
	elgg_load_library('tgsapi:activity');
	elgg_load_library('tgsapi:album');
	elgg_load_library('tgsapi:auth');
	elgg_load_library('tgsapi:comment');
	//elgg_load_library('tgsapi:location');
	elgg_load_library('tgsapi:photo');
	elgg_load_library('tgsapi:profile');
	elgg_load_library('tgsapi:stats');
	elgg_load_library('tgsapi:roles');
	elgg_load_library('tgsapi:todo');
	//elgg_load_library('tgsapi:video');
	elgg_load_library('tgsapi:wire');

	// Override API init
	elgg_register_plugin_hook_handler('rest', 'init', 'tgsapi_init_handler');	

	// Custom activity handlers
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'album', 'tgsapi_album_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'blog', 'tgsapi_blog_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'bookmarks', 'tgsapi_bookmarks_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'image', 'tgsapi_image_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'feedback', 'tgsapi_feedback_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'file', 'tgsapi_file_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'thewire', 'tgsapi_thewire_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'shared_doc', 'tgsapi_shared_doc_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'site_activity', 'tgsapi_site_activity_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'tidypics_batch', 'tgsapi_tidypics_batch_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'todo', 'tgsapi_todo_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'todosubmission', 'tgsapi_todosubmission_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'forum_topic', 'tgsapi_forum_topic_activity_handler');
	elgg_register_plugin_hook_handler('tgsapi:activity_content', 'forum_reply', 'tgsapi_forum_reply_activity_handler');

	// Set tgsapi version
	elgg_set_config('tgsapi_version', 2);

	// Set up known subtypes variables
	$known_subtypes = array(
		'image',
		'shared_doc',
		'site_activity',
		'album',
		'blog',
		'tidypics_batch',
		'feedback',
		'todo',
		'bookmarks',
		'thewire',
		'todosubmission',
		'file',
		'forum_topic',
		'forum_reply',
	);

	// Set known subtypes for API use
	elgg_set_config('tgsapi_known_subtypes', $known_subtypes);
	
	// Set up comment blacklist
	$comment_blacklist = array(
		'shared_doc',
		'site_activity',
		'messages',
		'pages_welcome',
		'plugin',
		'resourcerequest',
		'resourcerequesttype',
		'shared_access',
		'site',
		'widget',
		'googleapps',
		'forum_topic',
		'forum_reply',
	);

	// Set up comment blacklist for API
	elgg_set_config('tgsapi_comment_blacklist', $comment_blacklist);
	
	// Set up entities avaiable for 'show more' in the app
	$show_more = array(
		'todo',
		'todosubmission',
		'site_activity',
		'shared_doc',
		'blog',
		'bookmarks',
	);
	
	// Set entities for show more in the API
	elgg_set_config('tgsapi_show_more', $show_more);
	
	// Set up known video extensions
	$known_extensions = array('mpg', 'mpeg','avi','mp4', 'wmv', 'mov');
	elgg_set_config('tgsapi_known_video_extensions', $known_subtypes);
}

/**
 * Expose API functions
 */
function tgsapi_expose_functions() {
	// Get the authentification token
	expose_function("auth.get_infinity_token", "auth_get_infinity_token", array(
			'username' => array('type' => 'string'),
			'password' => array('type' => 'string'),
	), elgg_echo('auth.gettoken'),	'POST', FALSE, FALSE);

	// Get the authentification token from google
	expose_function("auth.get_google_token", "auth_get_google_token", array(
			'username' => array('type' => 'string'),
			'password' => array('type' => 'string'),
	), elgg_echo('auth.gettoken'),	'POST', FALSE, FALSE);

	// Get activity list
	expose_function('activity.list', 'activity_list', array(
            'limit' => array(
                'type' => 'int',
                'required' => FALSE
            ),
            'offset' => array(
                'type' => 'int',
                'required' => FALSE
            ),
			'subtype' => array(
				'type' => 'string',
				'required' => FALSE
			),
			'role' => array(
				'type' => 'string',
				'required' => FALSE
			),
			'subject_guid' => array(
				'type' => 'int',
				'required' => FALSE
			),
	), 'List all activities', 'GET', FALSE, TRUE);

	// Fetch ToDo list
	expose_function('todo.list', 'todo_list', array(
			'status' => array(
					'type' => 'string',
					'required' => FALSE
				),
			'limit' => array(
                'type' => 'int',
                'required' => FALSE
            ),
            'offset' => array(
                'type' => 'int',
                'required' => FALSE
			),
            'user_role' => array(
                'type' => 'string',
                'required' => FALSE
			)
	), 'Fetch todos', 'GET', FALSE, TRUE);

	//Accept todo
	expose_function('todo.accept', 'todo_accept', array(
			'todo_id' => array(
                'type' => 'int',
                'required' => TRUE
            )
	), 'Accept todo', 'POST', FALSE, TRUE);

	//Copmlete todo
	expose_function('todo.complete', 'todo_complete', array(
			'todo_id' => array(
                'type' => 'int',
                'required' => TRUE
            )
	), 'Complete todo', 'POST', FALSE, TRUE);

	// Post comment
	expose_function('comment.add', 'comment_post', array(
            'activity_id' => array(
                'type' => 'int',
                'required' => TRUE
            ),
            'text' => array(
                'type' => 'string',
                'required' => TRUE
            )
	), 'Post comment', 'POST', FALSE, TRUE);

	// Get user profile
	expose_function('profile.show', 'profile_details', array(
            'user_id' => array(
                'type' => 'int',
                'required' => TRUE
            ),
			'limit' => array(
                'type' => 'int',
                'required' => FALSE
            ),
            'offset' => array(
                'type' => 'int',
                'required' => FALSE
			)
	), 'View user profile', 'GET', FALSE, TRUE);

	// Post photo
	expose_function('photo.add', 'photo_add', array(
            'title' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'caption' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'tags' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'album_id' => array(
                'type' => 'integer',
                'required' => FALSE
            ),
            'lat' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'long' => array(
                'type' => 'string',
                'required' => FALSE
            )
	), 'Add photo', 'POST', FALSE, TRUE);

	// Post video
	/* UNUSED
	expose_function('video.add', 'video_add', array(
            'title' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'caption' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'tags' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'lat' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'long' => array(
                'type' => 'string',
                'required' => FALSE
            )
		), 'Add video', 'POST', FALSE, TRUE);
	*/

	// Get comments list for given object
	expose_function('comments.list', 'comments_list', array(
            'object_id' => array(
                'type' => 'int',
                'required' => TRUE
            ),
            'limit' => array(
                'type' => 'int',
                'required' => FALSE
            ),
            'offset' => array(
                'type' => 'int',
                'required' => FALSE
            )
	), 'Get comments for object', 'GET', FALSE, TRUE);

	// Post text message to the wire
	expose_function ('thewire.post', 'api_post_to_wire', array(
            'text' => array(
                'type' => 'string'
            ),
            'lat' => array(
                'type' => 'string',
                'required' => FALSE
            ),
            'long' => array(
                'type' => 'string',
                'required' => FALSE
            )
    ), 'Post to the wire', 'POST', FALSE, TRUE);
        
	// Get photos list in album
	expose_function('photo.list', 'get_photos_list_in_album', array(
			'album_guid' => array(
					'type' => 'int',
					'required' => TRUE
			)
	), 'Get photos list in album', 'GET', FALSE, TRUE);

	// Get albums list
	expose_function('albums.list', 'get_albums_list', array(
			'user_id' => array(
					'type' => 'int',
					'required' => FALSE
			)
	), 'Get photos list in album', 'GET', FALSE, TRUE);

	// Track iphone location
	/* UNUSED
	expose_function ('location.track', 'track_location', array(
            'lat' => array(
                'type' => 'string',
                'required' => TRUE
            ),
            'long' => array(
                'type' => 'string',
                'required' => TRUE
            )
		), 'Track location', 'POST', FALSE, TRUE);
	*/
	
	// Expose public site stats function
	expose_function('site.stats', 'get_site_stats', array(
			'photo_limit' => array(
					'type' => 'string', 
					'required' => TRUE
			)
	), 'Get Site Stats', 'GET', FALSE, FALSE);

	// Add method, that should return an amount of {status} todos assigned to me
	expose_function('todos.count', 'get_todos_count', array(
			'status' => array(
					'type' => 'string',
					'required' => FALSE
				),
			'user_role' => array(
					'type' => 'string',
					'required' => FALSE
				)
	), 'Get todos count', 'GET', FALSE, TRUE);

	// Get certain todo details
	expose_function('todo.show', 'todo_show', array(
			'todo_id' => array(
					'type' => 'int',
					'required' => TRUE
			)
	), 'Get todo detail', 'GET', FALSE, TRUE);
		
	// Get roles
	expose_function('roles.list', 'tgsapi_get_roles', array(
		'limit' => array(
			'type' => 'int',
			'required' => FALSE,
		), 
		'show_all' => array(
			'type' => 'int',
			'required' => FALSE,
		),
		'show_hidden' => array(
			'type' => 'int',
			'required' => FALSE,
		),
	), 'Get roles', 'GET', FALSE, TRUE);
	
	// Get roles
	expose_function('subtypes.list', 'tgsapi_get_subtypes', array(), 'Get subtypes', 'GET', FALSE, TRUE);
}

// Global Init Function
function tgsapi_global_init() {
	// Admin menu
	elgg_register_event_handler('pagesetup','system','tgsapi_adminmenu');
	
	// Register delete token action
	elgg_register_action("tgsapi/delete_token", elgg_get_plugins_path() . "tgsapi/actions/delete_token.php", 'admin');
}

// Use custom authentication handlers for the api
function tgsapi_init_handler() {
	// Admins can debug
	if (elgg_is_admin_logged_in()) {
		register_pam_handler('pam_auth_session');
	}
	
	// Global version check
	tgsapi_check_version();

	// user token can also be used for user authentication
	register_pam_handler('pam_auth_usertoken');

	// Returning true here cancels out all other pam handlers in lib/web_services
	return TRUE;
}

/**
 * Sets up API admin menu. Triggered on pagesetup.
 */
function tgsapi_adminmenu() {
	if (elgg_in_context('admin')) {
		elgg_register_admin_menu_item('administer', 'tokens', 'tgsapi');
	}
}