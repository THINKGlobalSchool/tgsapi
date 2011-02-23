<?php
	/**
	 * TGS API Delete token action
	 *
	 * @package ElggTGSAPI
	 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
	 * @author Jeff Tilson
	 * @copyright Think Global School 2009-2010
	 * @link http://www.thinkglobalschool.com
	 */

	$token =  get_input('at', null);
	
	if ($token) {
		remove_user_token($token);
	}
	
	forward($_SERVER['HTTP_REFERER']);
