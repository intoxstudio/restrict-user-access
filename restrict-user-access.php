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
 * Version:           2.7
 * Author:            DEV Institute
 * Author URI:        https://dev.institute
 * Requires at least: 5.5
 * Requires PHP:      7.1
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
require $path . '/src/Autoloader.php';
if(!\RestrictUserAccess\Autoloader::init($path)) {
    return;
}

RUA_App::instance(); //legacy
rua()->init();

require $path . 'db_updates.php';
