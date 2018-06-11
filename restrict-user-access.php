<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */
/*
Plugin Name: Restrict User Access
Plugin URI: https://dev.institute/wordpress-memberships/
Description: Easily restrict content and contexts to provide premium access for specific User Levels.
Version: 1.0.1
Author: Joachim Jensen
Author URI: https://dev.institute
Text Domain: restrict-user-access
License: GPLv3

	Restrict User Access for WordPress
	Copyright (C) 2015-2018 Joachim Jensen - jv@intox.dk

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) {
	exit;
}

$rua_plugin_path = plugin_dir_path( __FILE__ );
require($rua_plugin_path.'/lib/wp-content-aware-engine/bootstrap.php');
require($rua_plugin_path.'/app.php');
require($rua_plugin_path.'/level.php');
require($rua_plugin_path.'/api/level.php');

if(is_admin()) {
	require($rua_plugin_path.'/lib/wp-db-updater/wp-db-updater.php');
	require($rua_plugin_path.'/db_updates.php');
	require($rua_plugin_path.'/admin/admin.php');
	require($rua_plugin_path.'/admin/level-list-table.php');
	require($rua_plugin_path.'/admin/level-overview.php');
	require($rua_plugin_path.'/admin/level-edit.php');
	require($rua_plugin_path.'/admin/settings.php');
	require($rua_plugin_path.'/admin/nav-menu.php');
	require($rua_plugin_path.'/list-members.php');
	require($rua_plugin_path.'/list-capabilities.php');
}

require($rua_plugin_path.'freemius.php');

// Launch plugin
RUA_App::instance();

//eol