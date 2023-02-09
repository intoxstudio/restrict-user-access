<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

interface RUA_User_Level_Interface
{
    /**
     * @since 2.1
     *
     * @return int
     */
    public function get_user_id();

    /**
     * @since  2.1
     * @return RUA_User_Interface
     */
    public function user();

    /**
     * @since 2.1
     *
     * @return int
     */
    public function get_level_id();

    /**
     * Get ids of all levels that extend level()
     *
     * @since 2.1
     *
     * @return int[]
     */
    public function get_level_extend_ids();

    /**
     * @since 2.1
     *
     * @return RUA_Level_Interface
     */
    public function level();

    /**
     * @since 2.1
     *
     * @return string
     */
    public function get_status();

    /**
     * @param $status
     * @return self
     */
    public function update_status($status);

    /**
     * @since 2.1
     *
     * @return int unixtime or 0
     */
    public function get_start();

    /**
     * @param int $start unixtime
     * @return self
     */
    public function update_start($start);

    /**
     * @since 2.1
     *
     * @return int unixtime or 0
     */
    public function get_expiry();

    /**
     * @param int $expiry unixtime
     * @return self
     */
    public function update_expiry($expiry);

    /**
     * Reset expiry with level duration
     *
     * @return self
     */
    public function reset_expiry();

    /**
     * @since 2.1
     *
     * @return bool
     */
    public function is_active();
}
