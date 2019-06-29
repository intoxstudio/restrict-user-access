<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

/**
 * @since  1.1
 * @param  WP_User|int|null  $user
 * @return RUA_User_Interface
 */
function rua_get_user($user = null)
{
    if (is_null($user) && is_user_logged_in()) {
        $user = wp_get_current_user();
    }

    if (!($user instanceof WP_User)) {
        $user = new WP_User($user);
    }

    return new RUA_User($user);
}

/**
 * API to get level by name
 *
 * @since  0.9
 * @param  string  $name
 * @return int
 */
function rua_get_level_by_name($name)
{
    return RUA_App::instance()->level_manager->get_level_by_name($name);
}

/**
 * API to get level capabilities
 *
 * @since  0.13
 * @param  int   $level_id
 * @param  bool  $hierarchical
 * @return array
 */
function rua_get_level_caps($level_id, $hierarchical = false)
{
    $levels = array( $level_id );
    if ($hierarchical) {
        $levels = array_merge($levels, get_post_ancestors((int) $level_id));
        $levels = array_reverse($levels);
    }
    $caps = RUA_App::instance()->level_manager->get_levels_caps($levels);
    return $caps;
}
