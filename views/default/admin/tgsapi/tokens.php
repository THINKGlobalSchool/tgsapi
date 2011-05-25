<?php
/**
 * TGS API Admin page
 *
 * @package ElggTGSAPI
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright Think Global School 2009-2010
 * @link http://www.thinkglobalschool.com
 */

global $CONFIG;
			
echo "<table class='elgg-table' style='width: 75%;'>
		<thead>
			<tr>
				<th>User</th>
				<th>Token</th>
				<th>Expires</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>";

$site_guid = $CONFIG->site_id;

$tokens = get_data("SELECT * from {$CONFIG->dbprefix}users_apisessions where site_guid=$site_guid");
	
foreach ($tokens as $token) {
	$user = get_user($token->user_guid);
	$action_url = elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/tgsapi/delete_token?at={$token->token}");
	$expires = date("M j G:i:s Y", $token->expires);
	
	echo "<tr><td>{$user->name}</td>";
	echo "<td>{$token->token}</td>";
	echo "<td style='color: #666666;'>$expires</td>"; 
	echo "<td><a href='$action_url'>Delete</a></td></tr>";	
}
	
echo "</tbody></table>";