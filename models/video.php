<?php

require_once 'functions.php';

/**
 * Post video to site.
 * Outer api method.
 *
 * For now it's just save the video file to the data/tmp_videos folder.
 *
 * @todo when caltura video plugin will support elgg 1.8, we need to rewrite method in order to use this plugin
 *
 * @param string $title video's title
 * @param string $caption video's caption
 * @param string $tags tags (comma separated)
 * @param float $lat latitude
 * @param float $long longitude
 * @return true|string true on success and string (message) on error
 */
function video_add($title, $caption = '', $tags = '', $lat, $long) {
	require_once dirname(dirname(__FILE__)) .'/config.php';

    if (count($_FILES) == 0) {
        return 'no files';
    }

    $username = get_loggedin_user()->username;
    $uploaddir = elgg_get_data_path() . 'tmp_videos/';

    foreach($_FILES as $file) {
		$filename = $username . '_' . time() . '_' . $file['name'];
		$dot_pos =  strrpos($file['name'], '.');
		$file_ext = substr($file['name'], $dot_pos + 1, strlen($file['name']) - $dot_pos + 1);

		if (!in_array($file_ext, $video_extensions)) {
			return 'unacceptable file type';
		}

		if (!move_uploaded_file($file['tmp_name'], $uploaddir . $filename)) {
			return 'error when uploading file';
		}
    }
    
    return true;
}
