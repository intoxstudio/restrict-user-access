<?php
/**
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * API to get user roles
 *
 * @since  0.9
 * @param  int   $user_id
 * @return array
 */
function rua_get_user_roles($user_id) {
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
 * @since  0.10.x
 * @param  int  $level_id
 * @return mixed
 */
function rua_get_level_caps($level_id) {
	return RUA_App::instance()->level_manager->metadata()->get('caps')->get_data($level_id);
}

//eol