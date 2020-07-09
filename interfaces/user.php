<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2020 by Joachim Jensen
 */

interface RUA_User_Interface
{

    /**
     * @since  1.1
     * @return int
     */
    public function get_id();

    /**
     * Get any attribute from WP_User
     *
     * @since 2.1
     * @param string $name
     * @param mixed|null $default_value
     *
     * @return mixed|null
     */
    public function get_attribute($name, $default_value = null);

    /**
     * @since  1.1
     * @return bool
     */
    public function has_global_access();

    /**
     * @since 2.1
     *
     * @return RUA_Collection<RUA_User_Level>|RUA_User_Level[]
     */
    public function level_memberships();

    /**
     * @since  1.1
     * @param  bool $hierarchical - deprecated
     * @param  bool $synced_roles - deprecated
     * @param  bool $include_expired - deprecated
     * @return array
     */
    public function get_level_ids(
        $hierarchical = true,
        $synced_roles = true,
        $include_expired = false
    );

    /**
     * @since 1.1
     * @param int  $level_id
     * @return bool
     */
    public function add_level($level_id);

    /**
     * @since  1.1
     * @param  int    $level_id
     * @return bool
     */
    public function remove_level($level_id);

    /**
     * @since  1.1
     * @param  int  $level
     * @return bool
     */
    public function has_level($level);

    /**
     * @since  1.1
     * @param  int      $level_id
     * @return int
     * @deprecated 2.1
     * @see level_memberships()->get($level_id)->get_start()
     */
    public function get_level_start($level_id);

    /**
     * @since  1.1
     * @param  int      $level_id
     * @return int
     * @deprecated 2.1
     * @see level_memberships()->get($level_id)->get_expiry()
     */
    public function get_level_expiry($level_id);

    /**
     * @since  1.1
     * @param  int      $level_id
     * @return bool
     * @deprecated 2.1
     * @see !level_memberships()->get($level_id)->is_active()
     */
    public function is_level_expired($level_id);

    /**
     * Get all user level capabilities (also checks hierarchy)
     *
     * @since  1.1
     * @param  array  $current_caps (optional preset)
     * @return array
     */
    public function get_caps($current_caps = array());
}
