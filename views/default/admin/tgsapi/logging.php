<?php
/**
 * TGS API Logging Admin page
 *
 * @package ElggTGSAPI
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright Think Global School 2009-2010
 * @link http://www.thinkglobalschool.com
 */

// Register and load helpers lib
elgg_register_library('tgsapi:helpers', elgg_get_plugins_path() . 'tgsapi/lib/helpers.php');
elgg_load_library('tgsapi:helpers'); // Load first

$plugin = elgg_get_plugin_from_id('tgsapi');

$options = array(
	'guid' => $plugin->getGUID(),
	'annotation_name' => 'tgsapi_logging',
	'limit' => 25,
	'offset' => (int) max(get_input('annoff', 0), 0),
);

$total_logs = $plugin->countAnnotations('tgsapi_logging');

echo "<strong>Total logs</strong>: $total_logs<br /><br />";

echo elgg_list_entities($options, 'elgg_get_annotations', 'tgsapi_view_log_annotation_list');
