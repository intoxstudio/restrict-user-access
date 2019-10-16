<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

interface RUA_User_Interface
{

    /**
     * @since  1.1
     * @return int
     */
    public function get_id();

    /**
     * @since  1.1
     * @return bool
     */
    public function has_global_access();

    /**
     * @since  1.1
     * @param  bool $hierarchical - include inherited levels
     * @param  bool $synced_roles - include levels synced with role
     * @param  bool $include_expired
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
     */
    public function get_level_start($level_id);

    /**
     * @since  1.1
     * @param  int      $level_id
     * @return int
     */
    public function get_level_expiry($level_id);

    /**
     * @since  1.1
     * @param  int      $level_id
     * @return bool
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
