<?php
/**
 * This file used for testing the api from browser.
 * When you add/change some functionality, add a form or link here to be able to test it.
 */

require_once("../../engine/start.php");

// Admins only
if (!elgg_is_admin_logged_in()) {
	echo "Access Denied";
	return FALSE;
}

// define environments
$env = get_input('env');
switch (@$env) {
	case 'local':
	default:
		$site = 'http://192.168.0.119/elgg/';
		$user_guid = get_user_by_username('jtilson')->guid;
		break;
	case 'spot18':
		$site = 'http://spot18.thinkglobalschool.com/';
		$user_guid = get_user_by_username('testuser1')->guid;
		break;
}

echo $site;

?>
<html>
	<h1>API TEST</h1>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=activity.list&limit=10&offset=0">Activity list</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=profile.show&user_id=<?php echo $user_guid; ?>&limit=10&offset=0">user profile</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=comments.list&object_id=22178">Comment list for object</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=albums.list">Albums list for user</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.list&status=complete&limit=10&offset=0&user_role=owned">Completed ToDo list (owned)</a>
<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.list&status=complete&limit=10&offset=0&user_role=assigned">Completed ToDo list (assigned)</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.list&status=incomplete&limit=10&offset=0&user_role=owned">Incompleted ToDo list (owned)</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.list&status=incomplete&limit=10&offset=0&user_role=assigned">Incompleted ToDo list (assigned)</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todos.count&status=unaccepted&user_role=assigned">Count unaccepted todos assigned to me</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.show&todo_id=32217876">Get todo details</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=roles.list">Get roles</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=subtypes.list">Get subtypes</a>
	<hr />
POST photo
<form action="<?=$site?>services/api/rest/xml/?method=photo.add" enctype="multipart/form-data" method="post">
<input type="hidden" name="auth_token" value="<?=$token?>">
<p><input type="file" name="pic"></p>

<p>Title <input type="text" name="title"></p>
<p>Caption <input type="text" name="caption"></p>
<p>Tag <input type="text" name="tags"></p>
<p>Album id <input type="text" name="album_id"></p>
<p>Lat <input type="text" name="lat"></p>
<p>Long <input type="text" name="long"></p>
<input type="submit">
</form>

<hr />
POST video
<form action="<?=$site?>services/api/rest/xml/?method=video.add" enctype="multipart/form-data" method="post">
<input type="hidden" name="auth_token" value="<?=$token?>">
<p><input type="file" name="video"></p>

<p>Title <input type="text" name="title"></p>
<p>Caption <input type="text" name="caption"></p>
<p>Tag <input type="text" name="tags"></p>
<p>Lat <input type="text" name="lat"></p>
<p>Long <input type="text" name="long"></p>
<input type="submit">
</form>

<hr />

POST Comment
<form action="<?=$site?>services/api/rest/xml/?method=comment.add" method="post">
<input type="hidden" name="auth_token" value="<?=$token?>">
<p>object id <input type="text" name="activity_id"></p>
<p>text <input type="text" name="text"></p>
<input type="submit">
</form>

<hr />

Post to wire
<form action="<?=$site?>services/api/rest/xml/?method=thewire.post" method="post">
<input type="hidden" name="auth_token" value="<?=$token?>">
<p>Text <input type="text" name="text"></p>
<p>Lat <input type="text" name="lat"></p>
<p>Long <input type="text" name="long"></p>
<input type="submit">
</form>

<hr />

Token
<form action="<?=$site?>services/api/rest/xml/?method=auth.get_infinity_token" method="post">
<p>Username <input type="text" name="username"></p>
<p>Password <input type="text" name="password"></p>
<input type="submit">
</form>

Google auth
<form action="<?=$site?>services/api/rest/xml/?method=auth.get_google_token" method="post">
<p>Username <input type="text" name="username"></p>
<p>Password <input type="text" name="password"></p>
<input type="submit">
</form>

<hr />

Track location
<form action="<?=$site?>services/api/rest/xml/?method=location.track" method="post">
<input type="hidden" name="auth_token" value="<?=$token?>">
<p>Lat <input type="text" name="lat"></p>
<p>Long <input type="text" name="long"></p>
<input type="submit">
</form>

<hr />

Accept todo
<form action="<?=$site?>services/api/rest/xml/?method=todo.accept" method="post">
<input type="hidden" name="auth_token" value="<?=$token?>">
<p>todo guid <input type="text" name="todo_id"></p>
<input type="submit">
</form>

<hr />

Complete todo
<form action="<?=$site?>services/api/rest/xml/?method=todo.complete" method="post">
<input type="hidden" name="auth_token" value="<?=$token?>">
<p>todo guid <input type="text" name="todo_id"></p>
<input type="submit">
</form>

</html>
