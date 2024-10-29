<?php
/*
Plugin Name: Bang Faceted Search
Plugin URI: http://www.bang-on.net/
Description: Create a faceted search interface for any post type.
Version: 2.0
Author: Marcus Downing
Contributors: marcus.downing, diddledan
Author URI: http://www.bang-on.net/
License: GPLv2
*/

if (!defined('BANG_FS_DEBUG'))
	define('BANG_FS_DEBUG', 0);
if (!defined('BANG_FS_MU_DEBUG'))
  define('BANG_FS_MU_DEBUG', 0);
if (!defined('BANG_FS_GET_DEBUG'))
  define('BANG_FS_GET_DEBUG', 0);
if (!defined('BANG_FS_AUTO_STYLE_THRESHOLD'))
  define('BANG_FS_AUTO_STYLE_THRESHOLD', 8);
if (BANG_FS_DEBUG) @include('../bang-syslog/include.php');

define('BANG_FS_PLUGIN_FILE', __FILE__);

add_action('plugins_loaded', 'bang_fs_load', 1);
function bang_fs_load() {
	$folder = dirname(__FILE__)."/functions/*.php";
	foreach (glob($folder) as $filename) {
	  include_once($filename);
	}
}

add_filter("plugin_action_links_".plugin_basename(__FILE__), 'bang_fs_settings_links');

function bang_fs_show_settings() {
  if (current_user_can('manage_options'))
    require_once('faceted-search-settings.php');
}
