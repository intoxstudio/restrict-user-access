<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 *
 * @var WP_Post_Type $post_type
 * @var Freemius $freemius
 */

$nav_items = [];

if (current_user_can($post_type->cap->edit_posts)) {
    $nav_items['core']['levels'] = [
        'title' => __('Access Levels', 'restrict-user-access'),
        'link'  => admin_url('admin.php?page=wprua'),
    ];
    $nav_items['core']['settings'] = [
        'title' => __('Settings', 'restrict-user-access'),
        'link'  => admin_url('admin.php?page=wprua-settings'),
    ];
}
$nav_items['core']['addons'] = [
    'title' => __('Add-ons', 'restrict-user-access'),
    'link'  => $freemius->get_addons_url(),
];
$nav_items['core']['bundle'] = [
    'title' => '<span class="dashicons dashicons-superhero-alt"></span> ' . __('Bundle & Save', 'restrict-user-access'),
    'link'  => 'https://dev.institute/wordpress-memberships/bundles/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=top-bar&amp;utm_campaign=rua',
    'meta'  => [
        'class'  => 'rua-nav-upgrade',
        'target' => '_blank',
        'rel'    => 'noopener'
    ]
];

$nav_items['extra']['docs'] = [
    'title' => '<span class="dashicons dashicons-welcome-learn-more"></span> ' . __('Docs', 'restrict-user-access'),
    'link'  => 'https://dev.institute/docs/restrict-user-access/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=nav&amp;utm_campaign=cas',
    'meta'  => [
        'target' => '_blank',
        'rel'    => 'noopener'
    ]
];
$nav_items['extra']['forums'] = [
    'title' => '<span class="dashicons dashicons-sos"></span> ' . __('Forums', 'restrict-user-access'),
    'link'  => 'https://wordpress.org/support/plugin/restrict-user-access/',
    'meta'  => [
        'target' => '_blank',
        'rel'    => 'noopener noreferrer',
    ]
];

$nav_items = apply_filters('rua/admin/top_nav', $nav_items);

function rua_display_nav($items)
{
    foreach ($items as $item) {
        $meta = '';
        if (!isset($item['meta']['class'])) {
            $item['meta']['class'] = '';
        }
        $item['meta']['class'] .= ' rua-nav-link';
        foreach ($item['meta'] as $key => $value) {
            $meta .= ' ' . $key . '="' . $value . '"';
        }

        echo '<a href="' . esc_url($item['link']) . '"' . $meta . '>';
        echo $item['title'];
        echo '</a>';
    }
}

?>

<div class="rua-navbar">
    <img src="<?php echo $freemius->get_local_icon_url(); ?>" width="36" height="36" alt="" />
    <h2>
        <span class="screen-reader-text"><?php _e('Restrict User Access', 'restrict-user-access'); ?></span>
    </h2>
    <div style="display: inline-block;vertical-align: middle;padding-left: 20px;">
        <?php rua_display_nav($nav_items['core']); ?>
    </div>
    <div style="display: inline-block;vertical-align: middle;float:right;overflow: hidden;">
        <?php rua_display_nav($nav_items['extra']); ?>
    </div>
</div>