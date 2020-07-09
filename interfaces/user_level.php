<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2020 by Joachim Jensen
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
     * @since 2.1
     *
     * @return int - unixtime or 0
     */
    public function get_start();

    /**
     * @since 2.1
     *
     * @return int - unixtime or 0
     */
    public function get_expiry();

    /**
     * @since 2.1
     *
     * @return bool
     */
    public function is_active();
}
