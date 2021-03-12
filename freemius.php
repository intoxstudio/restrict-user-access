<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

defined('ABSPATH') || exit;

// Create a helper function for easy SDK access.
function rua_fs()
{
    global $rua_fs;

    if (! isset($rua_fs)) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/lib/freemius/start.php';

        $rua_fs = fs_dynamic_init([
            'id'             => '1538',
            'slug'           => 'restrict-user-access',
            'type'           => 'plugin',
            'public_key'     => 'pk_606dec7b339c246a1bad6a6a04c52',
            'is_premium'     => false,
            'has_addons'     => true,
            'has_paid_plans' => false,
            'menu'           => [
                'slug'    => 'wprua',
                'contact' => false,
                'support' => false,
                'account' => false
            ],
        ]);
    }
    return $rua_fs;
}

// Init Freemius.
$rua_fs = rua_fs();
// Signal that SDK was initiated.
do_action('rua_fs_loaded', $rua_fs);

function rua_fs_connect_message_update(
    $message,
    $user_first_name,
    $plugin_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
        __('Hey %1$s') . ',<br>' .
        __('Please help us improve %2$s by securely sharing some usage data with %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'restrict-user-access'),
        $user_first_name,
        '<b>' . $plugin_title . '</b>',
        '<b>' . $user_login . '</b>',
        $site_link,
        $freemius_link
    );
}

function rua_fs_get_plugin_icon()
{
    return dirname(__FILE__).'/assets/img/icon.png';
}

$rua_fs->add_filter('connect_message_on_update', 'rua_fs_connect_message_update', 10, 6);
$rua_fs->add_filter('connect_message', 'rua_fs_connect_message_update', 10, 6);
$rua_fs->add_filter('plugin_icon', 'rua_fs_get_plugin_icon');
$rua_fs->add_filter('permission_extensions_default', '__return_true');
