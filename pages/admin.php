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

	include_once dirname(dirname(dirname(dirname(__FILE__)))) . "/engine/start.php";

	global $CONFIG;

	admin_gatekeeper();
	set_context('admin');
	set_page_owner($_SESSION['guid']);
	
	$body = elgg_view_title(elgg_echo('tgsapi:admin'));
	
	$body .= elgg_view("tgsapi/admin_tokens");
	
	echo elgg_view_page(elgg_echo('tgsapi:admin'), elgg_view_layout("administration", array('content' => $body)), 'admin');