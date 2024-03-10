<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class RUA_Admin_Screen_Account extends RUA_Admin
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
        return 'user-access_page_wprua-account';
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
        $this->freemius->add_filter('hide_account_tabs', '__return_true');
        $this->freemius->add_filter('hide_billing_and_payments_info', '__return_true');

        $path = plugin_dir_path(dirname(__FILE__)) . 'view/';
        $view = WPCAView::make($path . 'account_login.php', [
            'list' => [
                __('Manage Subscription', 'restrict-user-access'),
                __('Access Invoices', 'restrict-user-access'),
                __('View Licenses', 'restrict-user-access')
            ]
        ]);
        $this->freemius->add_action('after_account_details', [$view, 'render']);
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
