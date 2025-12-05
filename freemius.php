<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

defined('ABSPATH') || exit;

// Create a helper function for easy SDK access.
function rua_fs()
{
    global $rua_fs;

    if (!isset($rua_fs)) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/lib/freemius/start.php';

        /** @phpstan-ignore function.notFound */
        $rua_fs = fs_dynamic_init([
            'id'                             => '1538',
            'slug'                           => 'restrict-user-access',
            'type'                           => 'plugin',
            'public_key'                     => 'pk_606dec7b339c246a1bad6a6a04c52',
            'is_premium'                     => false,
            'has_addons'                     => true,
            'has_paid_plans'                 => false,
            'bundle_id'                      => '3207',
            'bundle_public_key'              => 'pk_e636ffbaa31bcdaa9d017a9f9a77a',
            'bundle_license_auto_activation' => true,
            'menu'                           => [
                'slug'    => 'wprua',
                'contact' => false,
                'support' => false,
                'account' => true
            ],
            'opt_in_moderation' => [
                'new'       => 100,
                'updates'   => 0,
                'localhost' => false,
            ],
        ]);
        $rua_fs->add_filter('connect-header', function ($text) use ($rua_fs) {
            return '<h2>' .
                sprintf(
                    __('Thank you for installing %s!', 'restrict-user-access'),
                    esc_html($rua_fs->get_plugin_name())
                ) . '</h2>';
        });
        $rua_fs->add_filter('connect_message_on_update', 'rua_fs_connect_message_update', 10, 6);
        $rua_fs->add_filter('connect_message', 'rua_fs_connect_message_update', 10, 6);
        $rua_fs->add_filter('plugin_icon', 'rua_fs_get_plugin_icon');
        $rua_fs->add_filter('permission_extensions_default', '__return_true');
        $rua_fs->add_filter('hide_freemius_powered_by', '__return_true');
    }
    return $rua_fs;
}

// Init Freemius.
$rua_fs = rua_fs();

function rua_fs_connect_message_update(
    $message,
    $user_first_name,
    $plugin_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
        __('Please help us improve the plugin by securely sharing some basic WordPress environment info. If you skip this, that\'s okay! %2$s will still work just fine.', 'restrict-user-access'),
        $user_first_name,
        $plugin_title,
        $user_login,
        $site_link,
        $freemius_link
    );
}

function rua_fs_get_plugin_icon()
{
    return dirname(__FILE__) . '/assets/img/icon.png';
}

// Signal that SDK was initiated.
do_action('rua_fs_loaded', $rua_fs);
