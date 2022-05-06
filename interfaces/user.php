<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

interface RUA_User_Interface
{

    /**
     * @since  1.1
     * @return int
     */
    public function get_id();

    /**
     * Get any attribute from underlying WP_User
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
     * Get all level memberships, including inactive
     *
     * @since 2.1
     *
     * @return RUA_Collection<RUA_User_Level_Interface>|RUA_User_Level_Interface[]
     */
    public function level_memberships();

    /**
     * Get ids of all levels user is active member of,
     * directly or indirectly
     *
     * @since  1.1
     * @param  bool $hierarchical - deprecated
     * @param  bool $synced_roles - deprecated
     * @param  bool $include_expired - deprecated
     * @return int[]
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
     * Check if user has active membership of level,
     * not including indirect memberships
     *
     * @since  1.1
     * @param  int  $level_id
     * @return bool
     */
    public function has_level($level_id);

    /**
     * @since  1.1
     * @param  int      $level_id
     * @return int
     * @deprecated 2.1 use level_memberships()->get($level_id)->get_start()
     * @see RUA_User_Interface::level_memberships()
     */
    public function get_level_start($level_id);

    /**
     * @since  1.1
     * @param  int      $level_id
     * @return int
     * @deprecated 2.1 use level_memberships()->get($level_id)->get_expiry()
     * @see RUA_User_Level_Interface::get_expiry()
     */
    public function get_level_expiry($level_id);

    /**
     * @since  1.1
     * @param  int      $level_id
     * @return bool
     * @deprecated 2.1 use !level_memberships()->get($level_id)->is_active()
     * @see RUA_User_Level_Interface::is_active()
     */
    public function is_level_expired($level_id);

    /**
     * Get all user capabilities,
     * based on level memberships
     *
     * @since  1.1
     * @param  array  $current_caps (optional preset)
     * @return array
     */
    public function get_caps($current_caps = []);
}
