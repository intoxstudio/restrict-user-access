<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 *
 * Plugin Name:       Restrict User Access
 * Plugin URI:        https://dev.institute/wordpress-memberships/
 * Description:       Easily restrict content and contexts to provide exclusive access for specific Access Levels.
 * Version:           2.8
 * Author:            DEV Institute
 * Author URI:        https://dev.institute
 * Requires at least: 5.8
 * Requires PHP:      7.2
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

defined('ABSPATH') || exit;

$path = plugin_dir_path(__FILE__);
require $path . 'src/Autoloader.php';
if(!\RestrictUserAccess\Autoloader::init($path)) {
    return;
}

$legacy_files = [
    "lib/wp-content-aware-engine/start.php",
    "src/helpers.php",
    "lib/wp-db-updater/wp-db-updater.php",
    "helpers/collection.php",
    "helpers/rua-member-query.php",
    "interfaces/user_level.php",
    "interfaces/user.php",
    "interfaces/level.php",
    "models/user.php",
    "models/level.php",
    "models/user_level.php",
    "admin/admin.php",
    "admin/admin_bar.php",
    "admin/level-list-table.php",
    "admin/level-overview.php",
    "admin/level-edit.php",
    "admin/settings.php",
    "admin/screen_account.php",
    "admin/screen_addons.php",
    "admin/nav-menu.php",
    "list-members.php",
    "list-capabilities.php",
    "app.php",
    "level.php",
    "freemius.php",
    "api/deprecated.php",
    "api/functions.php",
    "automators/base.php"
];
foreach($legacy_files as $file) {
    require_once $path . $file;
}

RUA_App::instance(); //legacy
rua()->init();

require $path . 'db_updates.php';
