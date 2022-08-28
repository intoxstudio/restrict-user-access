<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class RUA_Nav_Menu
{
    public function __construct()
    {
        $enabled = apply_filters('rua/module/nav_menu', true);

        if (!$enabled) {
            return;
        }

        if (is_admin()) {
            add_action(
                'wp_update_nav_menu_item',
                [$this,'update_item'],
                10,
                3
            );
            add_action(
                'wp_nav_menu_item_custom_fields',
                [$this,'render_level_option'],
                99,
                4
            );

            //filter added in wp5.4
            if (version_compare(get_bloginfo('version'), '5.4', '<')) {
                add_filter(
                    'wp_edit_nav_menu_walker',
                    [$this,'set_edit_walker'],
                    999
                );
            }
        } else {
            // 	add_action( 'pre_get_posts',
            // 		array($this,'filter_nav_menus_query'));

            add_filter(
                'wp_get_nav_menu_items',
                [$this,'filter_nav_menus'],
                10,
                3
            );
        }
    }

    /**
    * Filter navigation menu items by level
    *
    * @since  1.0
    * @param  array   $items
    * @param  string  $menu
    * @param  array   $args
    * @return array
    */
    public function filter_nav_menus($items, $menu, $args)
    {
        $user = rua_get_user();
        if (!$user->has_global_access()) {
            $user_levels = array_flip($user->get_level_ids());
            foreach ($items as $key => $item) {
                $menu_levels = get_post_meta($item->ID, '_menu_item_level', false);
                if ($menu_levels && !array_intersect_key($user_levels, array_flip($menu_levels))) {
                    unset($items[$key]);
                }
            }
        }
        return $items;
    }

    /**
     * Filter navigation menu items by level
     * using query
     * Might have better performance
     *
     * @since  1.0
     * @param  WP_Query  $query
     * @return void
     */
    public function filter_nav_menus_query($query)
    {
        if (isset($query->query['post_type'],$query->query['include']) && $query->query['post_type'] == 'nav_menu_item' && $query->query['include']) {
            $levels = rua_get_user()->get_level_ids();
            $meta_query = [];
            $meta_query[] = [
                'key'     => '_menu_item_level',
                'value'   => 'wpbug',
                'compare' => 'NOT EXISTS'
            ];
            if ($levels) {
                $meta_query['relation'] = 'OR';
                $meta_query[] = [
                    'key'     => '_menu_item_level',
                    'value'   => $levels,
                    'compare' => 'IN'
                ];
            }
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Update menu item
     *
     * @since  0.11
     * @param  int    $menu_id
     * @param  int    $menu_item_db_id
     * @param  array  $args
     * @return void
     */
    public function update_item($menu_id, $menu_item_db_id, $args)
    {
        $post_type = get_post_type_object(RUA_App::TYPE_RESTRICT);
        if (!current_user_can($post_type->cap->edit_posts)) {
            return false;
        }

        $key = '_menu_item_level';
        $request_key = 'menu-item-access-levels';

        $new_levels = isset($_POST[$request_key][$menu_item_db_id]) ? $_POST[$request_key][$menu_item_db_id] : [];

        //weird empty key.
        //possible bug if WP uses nav-menu-data json to mimic $_POST
        unset($new_levels['']);

        $menu_levels = array_flip(get_post_meta($menu_item_db_id, $key, false));

        foreach ($new_levels as $level) {
            if (isset($menu_levels[$level])) {
                unset($menu_levels[$level]);
            } else {
                add_post_meta($menu_item_db_id, $key, $level);
            }
        }
        foreach ($menu_levels as $level => $value) {
            delete_post_meta($menu_item_db_id, $key, $level);
        }
    }

    /**
     * Set menu items walker for edit
     *
     * @since 0.11
     */
    public function set_edit_walker()
    {
        // Guard for plugins using wp_edit_nav_menu_walker wrong
        if (!class_exists('Walker_Nav_Menu_Edit')) {
            require_once ABSPATH . 'wp-admin/includes/class-walker-nav-menu-edit.php';
        }
        require_once dirname(__FILE__) . '/walker-nav-menu.php';
        return 'RUA_Walker_Nav_Menu_Edit';
    }

    /**
     * Render level option on menu item
     *
     * @since  0.11
     * @param  int    $id
     * @param  [type] $item
     * @param  int    $depth
     * @param  array  $args
     * @return void
     */
    public function render_level_option($id, $item, $depth, $args)
    {
        $post_type = get_post_type_object(RUA_App::TYPE_RESTRICT);
        if (!current_user_can($post_type->cap->edit_posts)) {
            return false;
        }

        /**
         * Compat if a similar menu walker from an other plugin is used
         * @see walder-nav-menu.php
         */
        if (empty($id)) {
            $id = (int) $item->ID;
        }

        $levels = (array) get_post_meta($id, '_menu_item_level', false); ?>
<p class="field-access-levels description description-wide">
    <label for="edit-menu-item-access-levels-<?php echo $id; ?>">
        <?php _e('Access Levels', 'restrict-user-access'); ?>:

        <select style="width:100%;" class="js-rua-levels" multiple="multiple"
            id="edit-menu-item-access-levels-<?php echo $id; ?>"
            name="menu-item-access-levels[<?php echo $id; ?>][]"
            data-value="<?php echo esc_html(implode(',', $levels)); ?>">
        </select>
        <span class="description"><?php _e('Restrict menu item to users with these levels or higher.', 'restrict-user-access'); ?></span>
    </label>
</p>
<?php
    }
}
