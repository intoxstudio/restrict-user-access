<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

interface RUA_Level_Interface
{
    /**
     * @since  2.1
     * @return int 0 if new
     */
    public function get_id();

    /**
     * @since 2.1
     *
     * @return string
     */
    public function get_title();

    /**
     * @since 2.1
     *
     * @return bool
     */
    public function exists();
}
