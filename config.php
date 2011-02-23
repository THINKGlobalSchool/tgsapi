<?php

	// non-commentable activity types
    $not_commentable = array('doc_activity', 'site_activity');
	// not shown activity types
    $not_shown = array( 'messages', 'pages_welcome', 'plugin', 'resourcerequest', 'resourcerequesttype', 'shared_access', 'site', 'widget', 'googleapps');

    $black_list= array_merge($not_commentable, $not_shown);

	// known video extension types
	$video_extensions = array('mpg', 'mpeg','avi','mp4', 'wmv', 'mov');

	// known activity types
	$known_types = array(
		'image',
		'doc_activity',
		'site_activity',
		'album',
		'blog',
		'tidypics_batch',
		'conversations',
		'videolist',
		'document',
		'feedback',
		'groupforumtopic',
		'todo',
		'bookmarks',
		'group',
		'thewire',
		'todosubmission',
		'file',
		'user'
	);

?>
