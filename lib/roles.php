<?php
/**
 * Elgg Role API Functions
 *
 * @package ElggTGSAPI
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright Think Global School 2009-2010
 * @link http://www.thinkglobalschool.com
 *
 */

/**
 * API exposed function to get a list of site roles
 *
 * @param int  $limit 
 * @param bool $show_hidden
 * @param bool $show_all
 */
function tgsapi_get_roles($limit = 0, $show_all = TRUE, $show_hidden = FALSE) {
	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'limit' => $limit,
	);

	// If we're not showing hidden roles, only include visible ones
	if (!$show_hidden) {
		$options['metadata_name'] = 'hidden';
		$options['metadata_value'] = 0;
	}

	$roles = elgg_get_entities_from_metadata($options);

	$role_array = array();
	
	if ($show_all) {
		$role_array[] = array(
			'guid' => 0,
			'name' => elgg_echo('all'),
		);
	}

	foreach($roles as $role) {
		$role_array[] = array(
			'guid' => $role->guid,
			'name' => $role->title,
		);
	}

	return $role_array;
}