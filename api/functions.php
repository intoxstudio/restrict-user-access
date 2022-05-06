<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

/**
 * @since  1.1
 * @param  WP_User|int|null  $user null or omit for current user
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
 * @since 2.1
 * @param WP_Post|int $post
 *
 * @return RUA_Level_Interface
 * @throws Exception
 */
function rua_get_level($post)
{
    if (is_numeric($post)) {
        $post = WP_Post::get_instance($post);
    }

    if (!($post instanceof WP_Post)) {
        throw new Exception();
    }

    return new RUA_Level($post);
}

/**
 * @since 2.1
 * @param RUA_Level_Interface|WP_Post|int $level
 * @param RUA_User_Interface|WP_User|int|null $user null or omit for current user
 *
 * @return RUA_User_Level_Interface
 */
function rua_get_user_level($level, $user = null)
{
    if (!($level instanceof RUA_Level_Interface)) {
        $level = rua_get_level($level);
    }

    if (!($user instanceof RUA_User_Interface)) {
        $user = rua_get_user($user);
    }

    $user_level = new RUA_User_Level($user, $level);
    $user_level->refresh();
    return $user_level;
}

/**
 * @since  0.9
 * @param  string  $name
 * @return WP_Post|bool
 */
function rua_get_level_by_name($name)
{
    return RUA_App::instance()->level_manager->get_level_by_name($name);
}

/**
 * @since  0.13
 * @param  int   $level_id
 * @param  bool  $hierarchical
 * @return array
 */
function rua_get_level_caps($level_id, $hierarchical = false)
{
    $levels = [ $level_id ];
    if ($hierarchical) {
        $levels = array_merge($levels, get_post_ancestors((int) $level_id));
        $levels = array_reverse($levels);
    }
    $caps = RUA_App::instance()->level_manager->get_levels_caps($levels);
    return $caps;
}
