<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */
if (!defined('ABSPATH')) {
	exit;
}

/**
 * API to get user roles
 *
 * @deprecated 0.17
 * @since      0.9
 * @param      int   $user_id
 * @return     array
 */
function rua_get_user_roles($user_id) {
	if($user_id) {
		_deprecated_function( __FUNCTION__, '0.17', 'get_userdata()->roles' );
	} else {
		_deprecated_argument( __FUNCTION__, '0.17', __('Use Access Level for logged-out users instead.','restrict-user-access'));
	}
	return RUA_App::instance()->level_manager->get_user_roles($user_id);
}

/**
 * API to get user levels
 *
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
	$include_expired = false) {
	return RUA_App::instance()->level_manager->get_user_levels($user_id,$hierarchical,$synced_roles,$include_expired);
}

/**
 * API to get user level start time
 *
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int
 */
function rua_get_user_level_start($user_id = null,$level_id) {
	return RUA_App::instance()->level_manager->get_user_level_start($user_id,$level_id);
}

/**
 * API to get user level expiry time
 *
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int
 */
function rua_get_user_level_expiry($user_id = null, $level_id) {
	return RUA_App::instance()->level_manager->get_user_level_expiry($user_id,$level_id);
}

/**
 * API to check if user level is expired
 *
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_is_user_level_expired($user_id = null, $level_id) {
	return RUA_App::instance()->level_manager->is_user_level_expired($user_id,$level_id);
}

/**
 * API to check if user has level
 *
 * @since  0.9
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_has_user_level($user_id,$level_id) {
	return RUA_App::instance()->level_manager->has_user_level($user_id,$level_id);
}

/**
 * API to add level to user
 *
 * @since  0.10
 * @param  int  $user_id
 * @param  int  $level_id
 * @return int|boolean
 */
function rua_add_user_level($user_id,$level_id) {
	return RUA_App::instance()->level_manager->add_user_level($user_id,$level_id);
}

/**
 * API to remove level from user
 *
 * @since  0.10
 * @param  int  $user_id
 * @param  int  $level_id
 * @return boolean
 */
function rua_remove_user_level($user_id,$level_id) {
	return RUA_App::instance()->level_manager->remove_user_level($user_id,$level_id);
}

/**
 * API to get level by name
 *
 * @since  0.9
 * @param  string  $name
 * @return int
 */
function rua_get_level_by_name($name) {
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
function rua_get_level_caps($level_id, $hierarchical = false) {
	$levels = array( $level_id );
	if ( $hierarchical ) {
		$levels = array_merge( $levels, get_post_ancestors( (int) $level_id ) );
		$levels = array_reverse( $levels );
	}
	$caps = RUA_App::instance()->level_manager->get_levels_caps( $levels );
	return $caps;
}

//eol