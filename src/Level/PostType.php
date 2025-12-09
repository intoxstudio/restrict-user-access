<?php

namespace RestrictUserAccess\Level;

use RestrictUserAccess\Hook\HookService;
use RestrictUserAccess\Hook\HookSubscriberInterface;

/**
 * Class PostType
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class PostType implements HookSubscriberInterface
{
    public const NAME = 'restriction';

    public function subscribe(HookService $service)
    {
        $service->add_action(
            'init',
            [$this, 'create_restrict_type'],
            99
        );
        $service->add_filter(
            'get_edit_post_link',
            [$this, 'get_edit_post_link'],
            10,
            3
        );
        $service->add_filter(
            'get_delete_post_link',
            [$this, 'get_delete_post_link'],
            10,
            3
        );
        $service->add_filter(
            'pre_wp_update_comment_count_now',
            [$this, 'update_member_count'],
            10,
            3
        );
        $service->add_action(
            'delete_post',
            [$this,'sync_level_deletion']
        );
    }

    /**
     * Create restrict post type and add it to WPCACore
     *
     * @since  0.1
     * @return void
     */
    public function create_restrict_type()
    {
        $capability_view = 'list_users';
        $capability_edit = 'promote_users';

        // Register the sidebar type
        register_post_type(self::NAME, [
            'labels' => [
                'name'               => __('Access Levels', 'restrict-user-access'),
                'singular_name'      => __('Access Level', 'restrict-user-access'),
                'add_new'            => _x('Add New', 'level', 'restrict-user-access'),
                'add_new_item'       => __('Add New Access Level', 'restrict-user-access'),
                'edit_item'          => __('Edit Access Level', 'restrict-user-access'),
                'new_item'           => __('New Access Level', 'restrict-user-access'),
                'all_items'          => __('Access Levels', 'restrict-user-access'),
                'view_item'          => __('View Access Level', 'restrict-user-access'),
                'search_items'       => __('Search Access Levels', 'restrict-user-access'),
                'not_found'          => __('No Access Levels found', 'restrict-user-access'),
                'not_found_in_trash' => __('No Access Levels found in Trash', 'restrict-user-access'),
                'parent_item_colon'  => __('Extend Level', 'restrict-user-access'),
                //wp-content-aware-engine specific
                'ca_title' => __('Only members can visit these pages', 'restrict-user-access')
            ],
            'capabilities' => [
                'edit_post'          => $capability_edit,
                'read_post'          => $capability_view,
                'delete_post'        => $capability_edit,
                'edit_posts'         => $capability_edit,
                'delete_posts'       => $capability_edit,
                'edit_others_posts'  => $capability_edit,
                'publish_posts'      => $capability_edit,
                'read_private_posts' => $capability_view
            ],
            'public'              => false,
            'hierarchical'        => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'menu_icon'           => \RUA_App::ICON_SVG,
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'supports'            => ['title','page-attributes'],
            'can_export'          => false,
            'delete_with_user'    => false
        ]);

        \WPCACore::types()->add(self::NAME);
    }

    /**
     * Get level edit link
     *
     * @since  0.15
     * @param  string  $link
     * @param  int     $post_id
     * @param  string  $context
     * @return string
     */
    public function get_edit_post_link($link, $post_id, $context)
    {
        $post = get_post($post_id);
        if ($post->post_type == self::NAME) {
            $sep = '&';
            if ($context == 'display') {
                $sep = '&amp;';
            }
            $link = admin_url('admin.php?page=wprua-level' . $sep . 'post=' . $post_id);

            //load page in all languages for wpml
            if (defined('ICL_SITEPRESS_VERSION') || defined('POLYLANG_VERSION')) {
                $link .= $sep . 'lang=all';
            }
        }
        return $link;
    }

    /**
     * Get level delete link
     *
     * @since  0.15
     * @param  string   $link
     * @param  int      $post_id
     * @param  boolean  $force_delete
     * @return string
     */
    public function get_delete_post_link($link, $post_id, $force_delete)
    {
        $post = get_post($post_id);
        if ($post->post_type == self::NAME) {
            $action = ($force_delete || !EMPTY_TRASH_DAYS) ? 'delete' : 'trash';

            $link = add_query_arg(
                'action',
                $action,
                admin_url('admin.php?page=wprua-level&post=' . $post_id)
            );
            $link = wp_nonce_url($link, "$action-post_{$post_id}");
        }
        return $link;
    }

    public function update_member_count($new, $old, $post_id)
    {
        $post = get_post($post_id);
        if ($post->post_type !== self::NAME) {
            return $new;
        }

        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_type = '%s' AND comment_post_ID = %d", \RUA_User_Level::ENTITY_TYPE, $post_id));
    }

    /**
     * Delete foreign metadata belonging to level
     *
     * @since  0.11.1
     * @param  int    $post_id
     * @return void
     */
    public function sync_level_deletion($post_id)
    {
        $post = get_post($post_id);

        if (!$post || $post->post_type != self::NAME) {
            return;
        }

        global $wpdb;

        //Delete legacy user levels
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->usermeta
			 WHERE
			 (meta_key = %s AND meta_value = %d)
			 OR
			 meta_key = %s",
            \RUA_App::META_PREFIX . 'level',
            $post_id,
            \RUA_App::META_PREFIX . 'level_' . $post_id
        ));

        //Delete nav menu item levels
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->postmeta
			 WHERE
			 meta_key = %s AND meta_value = %d",
            '_menu_item_level',
            $post_id
        ));
    }
}
