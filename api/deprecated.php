<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

/**
 * @deprecated 0.17 use get_userdata($user_id)->roles
 * @since      0.9
 * @param      int   $user_id
 * @return     array
 */
function rua_get_user_roles($user_id)
{
    _deprecated_function(__FUNCTION__, '0.17', 'get_userdata($user_id)->roles');
    return [];
}

/**
 * @deprecated 1.1 use rua_get_user($user_id)->get_level_ids()
 * @see RUA_User_Interface::get_level_ids()
 * @since  0.9
 * @param  int     $user_id
 * @param  boolean $hierarchical
 * @param  boolean $synced_roles
 * @param  boolean $include_expired
 * @return array
 */
function rua_get_user_levels(
    $user_id = null,
    $hierarchical = true,
    $synced_roles = true,
    $include_expired = false
) {
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user($user_id)->get_level_ids()');
    return rua_get_user($user_id)->get_level_ids($hierarchical, $synced_roles, $include_expired);
}

/**
 * @deprecated 1.1 use rua_get_user($user_id)->level_memberships()->get($level_id)->get_start()
 * @see RUA_User_Level_Interface::get_start()
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int
 */
function rua_get_user_level_start($user_id = null, $level_id = null)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user($user_id)->level_memberships()->get($level_id)->get_start()');
    if(is_null($level_id)) {
        return 0;
    }
    return rua_get_user($user_id)->level_memberships()->get($level_id)->get_start();
}

/**
 * @deprecated 1.1 use rua_get_user($user_id)->level_memberships()->get($level_id)->get_expiry()
 * @see RUA_User_Level_Interface::get_expiry()
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int
 */
function rua_get_user_level_expiry($user_id = null, $level_id = null)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user($user_id)->level_memberships()->get($level_id)->get_expiry()');
    if(is_null($level_id)) {
        return 0;
    }
    return rua_get_user($user_id)->level_memberships()->get($level_id)->get_expiry();
}

/**
 * @deprecated 1.1 use !rua_get_user($user_id)->level_memberships()->get($level_id)->is_active()
 * @see RUA_User_Level_Interface::is_active()
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_is_user_level_expired($user_id = null, $level_id = null)
{
    _deprecated_function(__FUNCTION__, '1.1', '!rua_get_user($user_id)->level_memberships()->get($level_id)->is_active()');
    if(is_null($level_id)) {
        return 0;
    }
    return !rua_get_user($user_id)->level_memberships()->get($level_id)->is_active();
}

/**
 * @deprecated 1.1 use rua_get_user($user_id)->has_level($level_id)
 * @see RUA_User_Interface::has_level()
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_has_user_level($user_id, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user($user_id)->has_level()');
    return rua_get_user($user_id)->has_level($level_id);
}

/**
 * @deprecated 1.1 use rua_get_user($user_id)->add_level($level_id)
 * @see RUA_User_Interface::add_level()
 * @since  0.10
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int|boolean
 */
function rua_add_user_level($user_id, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user($user_id)->add_level($level_id)');
    return rua_get_user($user_id)->add_level($level_id);
}

/**
 * @deprecated 1.1 use rua_get_user($user_id)->remove_level($level_id)
 * @see RUA_User_Interface::remove_level()
 * @since  0.10
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_remove_user_level($user_id, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user($user_id)->remove_level($level_id)');
    return rua_get_user($user_id)->remove_level($level_id);
}
