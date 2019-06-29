<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

/**
 * @deprecated 0.17
 * @since      0.9
 * @param      int   $user_id
 * @return     array
 */
function rua_get_user_roles($user_id)
{
    _deprecated_function(__FUNCTION__, '0.17', 'get_userdata()->roles');
    return array();
}

/**
 * @deprecated 1.1
 * @see rua_get_user()->get_level_ids()
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
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user()->get_level_ids()');
    return rua_get_user($user_id)->get_level_ids($hierarchical, $synced_roles, $include_expired);
}

/**
 * @deprecated 1.1
 * @see rua_get_user()->get_level_start()
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int
 */
function rua_get_user_level_start($user_id = null, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user()->get_level_start()');
    return rua_get_user($user_id)->get_level_start($level_id);
}

/**
 * @deprecated 1.1
 * @see rua_get_user()->get_level_expiry()
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int
 */
function rua_get_user_level_expiry($user_id = null, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user()->get_level_expiry()');
    return rua_get_user($user_id)->get_level_expiry($level_id);
}

/**
 * @deprecated 1.1
 * @see rua_get_user()->is_level_expired()
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_is_user_level_expired($user_id = null, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user()->is_level_expired()');
    return rua_get_user($user_id)->is_level_expired($level_id);
}

/**
 * @deprecated 1.1
 * @see rua_get_user()->has_level()
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_has_user_level($user_id, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user()->has_level()');
    return rua_get_user($user_id)->has_level($level_id);
}

/**
 * @deprecated 1.1
 * @see rua_get_user()->add_level()
 * @since  0.10
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int|boolean
 */
function rua_add_user_level($user_id, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user()->add_level()');
    return rua_get_user($user_id)->add_level($level_id);
}

/**
 * @deprecated 1.1
 * @see rua_get_user()->remove_level()
 * @since  0.10
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_remove_user_level($user_id, $level_id)
{
    _deprecated_function(__FUNCTION__, '1.1', 'rua_get_user()->remove_level()');
    return rua_get_user($user_id)->remove_level($level_id);
}
