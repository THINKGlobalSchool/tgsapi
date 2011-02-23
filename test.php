<?php
/**
 * This file used for testing the api from browser.
 * When you add/change some functionality, add a form or link here to be able to test it.
 */


require_once("../../engine/start.php");

	// define environments
	$env = @$_GET['env'];
	switch (@$env) {
		case 'loc':
			$site = 'http://elgg.leonid2.tsweb.toa/';
			$token = auth_get_infinity_token('admin', '123456');
			break;
                case 'mig':
			$site = 'http://spotmigration.thinkglobalschool.com/';
			$token = auth_get_infinity_token('testuser1', 'user1pass');
			break;
		case 'dev':
		default:
			$site = 'http://spotsvn.thinkglobalschool.com/';
			$token = '9adcc1176baeae91d8143c6e87d10aae';
			break;
	}

	echo "site=".$site;
	echo "<br /> token = ".$token;

?>
<html>

	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=activity.list&limit=10&offset=0&auth_token=<?=$token?>">Activity list</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=activity.show&activity_id=57&auth_token=<?=$token?>">Activity details</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=profile.show&user_id=2&limit=10&offset=0&auth_token=<?=$token?>">user profile</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=comments.list&object_id=24&auth_token=<?=$token?>">Comment list for object</a>
	<hr />
	<a href="<?=$site?>services/api/rest/xml/?method=albums.list&auth_token=<?=$token?>">Albums list for user</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.list&status=completed&limit=10&offset=0&user_role=assigner&auth_token=<?=$token?>">Completed ToDo list (assigner)</a>
<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.list&status=completed&limit=10&offset=0&user_role=assignee&auth_token=<?=$token?>">Completed ToDo list (assignee)</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.list&status=incompleted&limit=10&offset=0&user_role=assigner&auth_token=<?=$token?>">Incompleted ToDo list (assigner)</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.list&status=incompleted&limit=10&offset=0&user_role=assignee&auth_token=<?=$token?>">Incompleted ToDo list (assignee)</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todos.count&status=unaccepted&user_role=assignee&auth_token=<?=$token?>">Count unaccepted todos assigned to me</a>
	<hr/>
	<a href="<?=$site?>services/api/rest/xml/?method=todo.show&todo_id=376&auth_token=<?=$token?>">Get todo details</a>
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
