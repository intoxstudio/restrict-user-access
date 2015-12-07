<?php
/**
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */
/*
Plugin Name: Restrict User Access
Plugin URI: 
Description: Easily restrict content and contexts to provide premium access for specific User Roles.
Version: 0.8
Author: Joachim Jensen, Intox Studio
Author URI: http://www.intox.dk/
Text Domain: restrict-user-access
Domain Path: /lang/
License: GPLv3

	Restrict User Access Plugin
	Copyright (C) 2015 Joachim Jensen - jv@intox.dk

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
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

$rua_plugin_path = plugin_dir_path( __FILE__ );
require($rua_plugin_path.'/lib/wp-content-aware-engine/core.php');
require($rua_plugin_path.'/app.php');
require($rua_plugin_path.'/level.php');

if(is_admin()) {
	require($rua_plugin_path.'/lib/wp-db-updater/wp-db-updater.php');
	require($rua_plugin_path.'/db_updates.php');
	require($rua_plugin_path.'/admin/level-edit.php');
	require($rua_plugin_path.'/admin/level-overview.php');
	require($rua_plugin_path."/list-members.php");
	require($rua_plugin_path."/list-capabilities.php");
}

// Launch plugin
RUA_App::instance();

//eol