<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class RUA_Admin_Screen_Addons extends RUA_Admin
{
    /** @var Freemius */
    protected $freemius;

    public function __construct($freemius)
    {
        parent::__construct();
        $this->freemius = $freemius;
    }

    /**
     * @inheritDoc
     */
    public function get_screen()
    {
        return 'user-access_page_wprua-addons';
    }

    /**
     * @inheritDoc
     */
    public function authorize_user()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function prepare_screen()
    {
    }

    /**
     * @inheritDoc
     */
    public function admin_hooks()
    {
    }

    /**
     * @inheritDoc
     */
    public function add_scripts_styles()
    {
    }
}
