<?
/**
 * The auth.gettoken API.
 * This API call lets a user log in, returning an authentication token which can be used
 * to authenticate a user for a period of time. It is passed in future calls as the parameter
 * auth_token.
 *
 * @param string $username Username
 * @param string $password Clear text password
 * @return string Token string or exception
 * @throws SecurityException
 * @throws LoginException
 */
function auth_get_infinity_token($username, $password) {	
	// Use elgg's authentication
	$result = elgg_authenticate($username, $password);
	if ($result !== true) {
		throw new SecurityException($result);
	}

	// Get user
	$user = get_user_by_username($username);

	// Bad user
	if (!$user) {
		throw new SecurityException(elgg_echo('SecurityException:authenticationfailed'));
	}

	// Check if banned
    if ($user->isBanned()) {
        throw new LoginException(elgg_echo('LoginException:BannedUser'));
    }

	// Good to go, create token valid for about 100 years
    $token = create_user_token($username, 60 * 24 * 365 * 100);

    // Log auth_gettoken
	tgsapi_create_log_annotation('auth_gettoken', $user->guid);

    if ($token) {
        return $token;
    }
}

/**
 * Authenticate user by his google login and pass.
 * Same thing as the function above, but for google login.
 *
 * For now it supports only users who have visited the site at least once.
 * On site when the user comes first time, google asked about passing personal info to the site - we can't implement that in iphone
 * without loading browser, so only users who approved the transmission of data (so they exists in users_entity database table) could
 * be authetificated.
 *
 * @param string $username
 * @param string $password
 * @return string Token string or exception
 * @throws SecurityException
 */
function auth_get_google_token($username, $password) {
	// see if there is user with such username (email)
	$user = get_user_by_email($username);
	$guid = $user[0]->guid;

    // if user not exist throw auth failed
    if ($guid == false) {
        throw new SecurityException(elgg_echo('SecurityException:authenticationfailed'));
    }

	// try to authentificate on google. On fault throw exception
	if (!is_authenticated_on_google($username, $password)) {
		throw new SecurityException(elgg_echo('SecurityException:authenticationfailed'));
	}

	// user exist: it could be ok, not activated or banned
	$is_user_activated = elgg_get_user_validation_status($guid);
    $user = get_user($guid);
    $is_user_banned = ($user->banned == 'yes' ? true : false);

	// not activated
    if (!$is_user_activated) {
        throw new SecurityException(elgg_echo('user:not_activated'));
    }

	// banned
    if ($is_user_banned) {
        throw new SecurityException(elgg_echo('user:blocked'));
    }

	// user is allright, create token valid for about 100 years
    $token = create_user_token($user->username, 60 * 24 * 365 * 100);
    if ($token) {
        return $token;
    }
}
