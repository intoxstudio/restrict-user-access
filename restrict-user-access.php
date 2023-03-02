<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 *
 * Plugin Name:       Restrict User Access
 * Plugin URI:        https://dev.institute/wordpress-memberships/
 * Description:       Easily restrict content and contexts to provide exclusive access for specific Access Levels.
 * Version:           2.4.2
 * Author:            Joachim Jensen - DEV Institute
 * Author URI:        https://dev.institute
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Text Domain:       restrict-user-access
 * Domain Path:       /lang/
 * License:           GPLv3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('ABSPATH')) {
    exit;
}

$rua_plugin_path = plugin_dir_path(__FILE__);

if (!function_exists('rua_fs')) {
    require $rua_plugin_path . 'freemius.php';
}

require $rua_plugin_path . '/lib/wp-content-aware-engine/bootstrap.php';
require $rua_plugin_path . '/lib/wp-db-updater/wp-db-updater.php';
require $rua_plugin_path . '/helpers/collection.php';
require $rua_plugin_path . '/interfaces/user_level.php';
require $rua_plugin_path . '/interfaces/user.php';
require $rua_plugin_path . '/interfaces/level.php';
require $rua_plugin_path . '/models/user.php';
require $rua_plugin_path . '/models/level.php';
require $rua_plugin_path . '/models/user_level.php';
require $rua_plugin_path . '/automators/base.php';
require $rua_plugin_path . '/automators/login.php';
require $rua_plugin_path . '/automators/edd_product.php';
require $rua_plugin_path . '/automators/woo_product.php';
require $rua_plugin_path . '/automators/bp_member_type.php';
require $rua_plugin_path . '/automators/user_role.php';
require $rua_plugin_path . '/automators/user_role_sync.php';
require $rua_plugin_path . '/admin/admin.php';
require $rua_plugin_path . '/admin/admin_bar.php';
require $rua_plugin_path . '/admin/level-list-table.php';
require $rua_plugin_path . '/admin/level-overview.php';
require $rua_plugin_path . '/admin/level-edit.php';
require $rua_plugin_path . '/admin/settings.php';
require $rua_plugin_path . '/admin/nav-menu.php';
require $rua_plugin_path . '/list-members.php';
require $rua_plugin_path . '/list-capabilities.php';
require $rua_plugin_path . '/app.php';
require $rua_plugin_path . '/level.php';
require $rua_plugin_path . '/api/deprecated.php';
require $rua_plugin_path . '/api/functions.php';

// Launch plugin
RUA_App::instance();

require $rua_plugin_path . '/db_updates.php';
