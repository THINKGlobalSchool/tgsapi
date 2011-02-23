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
				
	echo "<br /><h3>Token Admin</h3>";
	echo "<table>
			<tr>
				<td style='padding: 5px'><h4>User</h4></td>
				<td style='padding: 5px'><h4>Token</h4></td>
				<td style='padding: 5px'><h4>Expires</h4></td>
				<td style='padding: 5px'><h4>Action</h4></td>
			</tr>";
	
	$site_guid = $CONFIG->site_id;
	
	$tokens = get_data("SELECT * from {$CONFIG->dbprefix}users_apisessions where site_guid=$site_guid");
		
	foreach ($tokens as $token) {
		
		$user = get_user($token->user_guid);
		$action_url = elgg_add_action_tokens_to_url($CONFIG->wwwroot . "action/tgsapi/delete_token?at={$token->token}");
		$expires = date("M j G:i:s Y", $token->expires);
		
		echo "<tr><td style='padding: 5px'>{$user->name}</td>";
		echo "<td style='padding: 5px'>{$token->token}</td>";
		echo "<td style='padding: 5px; color: #666666;'>$expires</td>"; 
		echo "<td style='padding: 5px'><a href='$action_url'>Delete</a></td></tr>";
		
	}
		
	echo "</table>";