<?php
/**
 * User authentification via api errors
 */
$english = array(
	'admin:tgsapi' => 'TGS API',
	'admin:tgsapi:tokens' => 'API Tokens',
	
	'user:blocked' => 'Looks like your accout is blocked',
	'user:not_activated' => 'Looks like your account is not active',
	'tgsapi:admin' => 'TGS API Admin',
	
	// API Subtypes
	'tgsapi:thewire' => 'Post',
	'tgsapi:tidypics_batch' => 'Photos',
	'tgsapi:todosubmission' => 'Todo Submission',
	'tgsapi:image' => 'Photo',
	'tgsapi:shared_doc' => 'Shared Document',
	'tgsapi:site_activity' => 'Wiki Activity',
	'tgsapi:album' => 'Album',
	'tgsapi:blog' => 'Blog',
	'tgsapi:feedback' => 'Feedback',
	'tgsapi:todo' => 'Todo',
	'tgsapi:bookmarks' => 'Bookmarks',
	'tgsapi:group' => 'Group',
	'tgsapi:file' => 'File',
	'tgsapi:user' => 'User',
	'tgsapi:forum_topic' => 'Forum Topic',
	'tgsapi:forum_reply' => 'Forum Reply',
	
	// Cleaner password error
	'APIException:MissingParameterInMethod' => 'Missing parameter \'%s\'',
	
);

add_translation("en",$english);
