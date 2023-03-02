<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class RUA_App
{
    /**
     * Plugin version
     */
    const PLUGIN_VERSION = '2.4.2';

    /**
     * Prefix for metadata
     * Same as wp-content-aware-engine
     */
    const META_PREFIX = '_ca_';

    /**
     * Post Type for restriction
     */
    const TYPE_RESTRICT = 'restriction';

    /**
     * Post type statuses
     */
    const STATUS_ACTIVE = 'publish';
    const STATUS_INACTIVE = 'draft';
    const STATUS_SCHEDULED = 'future';

    /**
     * Capability to manage restrictions
     * @deprecated use capability in post type object
     */
    const CAPABILITY = 'manage_options';

    const BASE_SCREEN = 'wprua';

    const ICON_SVG = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDIwIDIwIj48ZyBmaWxsPSIjYTBhNWFhIj48cGF0aCBkPSJNMTAuMDEyIDE0LjYyNUw1Ljc4IDEyLjI3Yy0xLjkwNi42NjQtMy42MDUgMS43Ni00Ljk4IDMuMTc4IDIuMTA1IDIuNzcgNS40MzYgNC41NiA5LjE4NSA0LjU2IDMuNzY2IDAgNy4xMTItMS44MDIgOS4yMTUtNC41OTMtMS4zOC0xLjQwNC0zLjA3LTIuNDk2LTQuOTctMy4xNTRsLTQuMjE4IDIuMzY3em0tLjAwNS0xNC42M0M3LjQxMi0uMDA1IDUuMzEgMS45MSA1LjMxIDQuMjhoOS4zOTNjMC0yLjM3LTIuMS00LjI4Ni00LjY5Ni00LjI4NnptNi4xMjYgMTAuNzFjLjE1OC0uMDMyLjY0LS4yMzIuNjMtLjMzMy0uMDI1LS4yNC0uNjg2LTUuNTg0LS42ODYtNS41ODRzLS40MjItLjI3LS42ODYtLjI5M2MuMDI0LjIxLjY5IDUuNzYuNzQ1IDYuMjF6bS0xMi4yNTMgMGMtLjE1OC0uMDMyLS42NC0uMjMyLS42My0uMzMzLjAyNS0uMjQuNjg2LTUuNTg0LjY4Ni01LjU4NHMuNDItLjI3LjY4Ni0uMjkzYy0uMDIuMjEtLjY5IDUuNzYtLjc0MiA2LjIxeiIvPjxwYXRoIGQ9Ik0xMCAxMy45NjdoLjAyM2wuOTc1LS41NXYtNC4yMWMuNzgtLjM3NyAxLjMxNC0xLjE3MyAxLjMxNC0yLjA5NyAwLTEuMjg1LTEuMDM1LTIuMzIzLTIuMzItMi4zMjNTNy42NyA1LjgyNSA3LjY3IDcuMTFjMCAuOTIzLjUzNSAxLjcyIDEuMzE1IDIuMDkzVjEzLjRsMS4wMTYuNTY3em0tMS43NjQtLjk4NXYtLjAzNWMwLTMuNjEtMS4zNS02LjU4My0zLjA4My02Ljk2bC0uMDMuMy0uNTIgNC42NyAzLjYzMyAyLjAyNXptMy41Ni0uMDM1YzAgLjAxNCAwIC4wMTguMDAzLjAyM2wzLjYxLTIuMDI1LS41My00LjY4LS4wMjgtLjI3M2MtMS43MjMuNC0zLjA1NyAzLjM2Mi0zLjA1NyA2Ljk1NXoiLz48L2c+PC9zdmc+';

    /**
     * @var array
     */
    private $levels = [];

    /**
     * @var array<int, int>
     */
    private $level_extends_map = [];

    /**
     * @var array<int, int[]>
     */
    private $level_extended_by_map = [];

    /**
     * @var WP_DB_Updater
     */
    private $db_updater;

    /**
     * @var RUA_App
     */
    private static $_instance;

    /**
     * @var RUA_Level_Manager
     */
    public $level_manager;

    /** @var RUA_Member_Automator[]|RUA_Collection<RUA_Member_Automator> */
    private $level_automators;

    public function __construct()
    {
        $this->level_manager = new RUA_Level_Manager();

        $this->db_updater = new WP_DB_Updater('rua_plugin_version', self::PLUGIN_VERSION);

        new RUA_Nav_Menu();

        if (is_admin()) {
            new RUA_Level_Overview();
            new RUA_Level_Edit();
            new RUA_Settings_Page();

            add_action(
                'admin_enqueue_scripts',
                [$this,'load_admin_scripts'],
                999
            );

            add_action(
                'show_user_profile',
                [$this,'add_field_access_level']
            );
            add_action(
                'edit_user_profile',
                [$this,'add_field_access_level']
            );
            add_action(
                'personal_options_update',
                [$this,'save_user_profile']
            );
            add_action(
                'edit_user_profile_update',
                [$this,'save_user_profile']
            );
            add_action(
                'delete_post',
                [$this,'sync_level_deletion']
            );

            add_filter(
                'manage_users_columns',
                [$this,'add_user_column_headers']
            );
            add_filter(
                'manage_users_custom_column',
                [$this,'add_user_columns'],
                10,
                3
            );
            add_filter(
                'cas/metadata/populate',
                [$this,'add_levels_to_visibility']
            );

            $file = plugin_basename(plugin_dir_path(__FILE__)) . '/restrict-user-access.php';
            add_filter(
                'plugin_action_links_' . $file,
                [$this,'plugin_action_links'],
                10,
                4
            );
        } else {
            new RUA_Admin_Bar();
        }

        add_action('wpca/loaded', [$this, 'ensure_wpca_loaded']);

        add_shortcode(
            'login-form',
            [$this,'shortcode_login_form']
        );

        add_filter(
            'cas/user_visibility',
            [$this,'sidebars_check_levels']
        );
    }

    public function ensure_wpca_loaded()
    {
        $this->process_level_automators();

        //hook early, other plugins might add dynamic caps later
        //fixes problem with WooCommerce Orders
        //todo: verify if this is still an issue, now that we run in wpca/loaded
        add_filter(
            'user_has_cap',
            [$this,'user_level_has_cap'],
            9,
            4
        );
    }

    /**
     * Override user caps with level caps.
     *
     * @param  array   $allcaps
     * @param  string  $cap
     * @param  array   $args {
     *     @type string  [0] Requested capability
     *     @type int     [1] User ID
     *     @type WP_User [2] Associated object ID (User object)
     * }
     * @param  WP_User $user
     *
     * @return array
     */
    public function user_level_has_cap($allcaps, $cap, $args, $user)
    {
        return rua_get_user($user)->get_caps($allcaps);
    }

    /**
     * Instantiates and returns class singleton
     *
     * @since  0.1
     * @return RUA_App
     */
    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @since  1.1
     * @return WP_DB_Updater
     */
    public function get_updater()
    {
        return $this->db_updater;
    }

    /**
     * Add Levels to sidebar visibility metadata list
     *
     * @since 0.12
     * @param WPCACollection  $metadata
     */
    public function add_levels_to_visibility($metadata)
    {
        $visibility = $metadata->get('visibility');
        $list = $visibility->get_input_list();

        if (isset($list['rua-levels'])) {
            return $metadata;
        }

        $levels = $this->get_levels();
        if ($levels) {
            $options = [];
            foreach ($levels as $level) {
                $options[$level->ID] = $level->post_title;
            }

            if (!defined('CAS_App::PLUGIN_VERSION')
                || version_compare(CAS_App::PLUGIN_VERSION, '3.8', '<')) {
                $list = $list + $options;
            } else {
                $list['rua-levels'] = [
                    'label'   => __('Access Levels', 'restrict-user-access'),
                    'options' => $options
                ];
            }
            $visibility->set_input_list($list);
        }

        return $metadata;
    }

    /**
     * Check if user level has access to sidebar
     *
     * @since  0.12
     * @param  array  $visibility
     * @return array
     */
    public function sidebars_check_levels($visibility)
    {
        $user = rua_get_user();

        if (!$user->has_global_access()) {
            return array_merge($visibility, $user->get_level_ids());
        }

        return array_merge($visibility, array_keys($this->get_levels()));
    }

    /**
     * Get login form in shotcode
     *
     * @version 0.9
     * @param   array     $atts
     * @param   string    $content
     * @return  string
     */
    public function shortcode_login_form($atts, $content = null)
    {
        if (is_user_logged_in()) {
            return $content;
        }
        $a = shortcode_atts([
            'remember'       => true,
            'redirect'       => '',
            'form_id'        => 'loginform',
            'id_username'    => 'user_login',
            'id_password'    => 'user_pass',
            'id_remember'    => 'rememberme',
            'id_submit'      => 'wp-submit',
            'label_username' => __('Username'),
            'label_password' => __('Password'),
            'label_remember' => __('Remember Me'),
            'label_log_in'   => __('Log In'),
            'value_username' => '',
            'value_remember' => false
        ], $atts);
        $a['echo'] = false;

        if (!$a['redirect']) {
            $parts = parse_url(home_url());
            $root = "{$parts['scheme']}://{$parts['host']}";
            if (isset($parts['port']) && $parts['port']) {
                $root .= ':' . $parts['port'];
            }
            if (isset($_GET['redirect_to'])) {
                $a['redirect'] = $root . urldecode($_GET['redirect_to']);
            } else {
                $a['redirect'] = $root . add_query_arg(null, null);
            }
        }

        return wp_login_form($a);
    }

    /**
     * Add Access Level to user profile
     *
     * @since 0.3
     * @param WP_User  $user
     */
    public function add_field_access_level($user)
    {
        $post_type = get_post_type_object(self::TYPE_RESTRICT);
        if (!current_user_can($post_type->cap->edit_posts) || is_network_admin()) {
            return;
        }
        $rua_user = rua_get_user($user);
        $user_levels = [];
        $link = '<a target="_blank" href="https://dev.institute/docs/restrict-user-access/getting-started/add-level-members/">' . __('Visitor Traits', 'restrict-user-access') . '</a>';
        foreach ($rua_user->level_memberships() as $membership) {
            $user_levels[] = $membership->get_level_id();
        } ?>
<h3><?php _e('Access Control', 'restrict-user-access'); ?>
</h3>
<table class="form-table">
    <tr>
        <th><label for="_ca_level"><?php _e('Level Memberships', 'restrict-user-access'); ?></label>
        </th>
        <td>
            <div style="width:25em;"><select style="width:100%;" class="js-rua-levels" multiple="multiple"
                    name="_ca_level[]"
                    data-value="<?php echo esc_html(implode(',', $user_levels)); ?>"></select>
            </div>
            <p class="description"><?php printf(__('Access Levels provided by %s will not be listed here.', 'restrict-user-access'), $link); ?>
            </p>
        </td>
    </tr>
</table>
<?php
    }

    /**
     * Save additional data for
     * user profile
     *
     * @since  0.3
     * @param  int  $user_id
     * @return void
     */
    public function save_user_profile($user_id)
    {
        $post_type = get_post_type_object(self::TYPE_RESTRICT);
        if (!current_user_can($post_type->cap->edit_posts) || is_network_admin()) {
            return;
        }

        $user = rua_get_user($user_id);
        $new_levels = isset($_POST[self::META_PREFIX . 'level']) ? (array) $_POST[self::META_PREFIX . 'level'] : [];

        $user_levels = [];
        foreach ($user->level_memberships() as $membership) {
            $user_levels[$membership->get_level_id()] = 1;
        }

        foreach ($new_levels as $level) {
            if (isset($user_levels[$level])) {
                unset($user_levels[$level]);
            } else {
                $user->add_level($level);
            }
        }
        foreach ($user_levels as $level => $value) {
            $user->remove_level($level);
        }
    }

    /**
     * Add column headers on
     * User overview
     *
     * @since 0.3
     * @param array  $column
     */
    public function add_user_column_headers($columns)
    {
        $new_columns = [];
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key == 'role') {
                $new_columns['level'] = __('Access Levels', 'restrict-user-access');
            }
        }
        return $new_columns;
    }

    /**
     * Add columns on user overview
     *
     * @since 0.3
     * @param string  $output
     * @param string  $column_name
     * @param int     $user_id
     */
    public function add_user_columns($output, $column_name, $user_id)
    {
        switch ($column_name) {
            case 'level':
                $level_links = [];
                foreach (rua_get_user($user_id)->level_memberships() as $membership) {
                    $level_links[] = sprintf(
                        '<a href="%s">%s%s</a>',
                        get_edit_post_link($membership->get_level_id()),
                        $membership->level()->get_title(),
                        !$membership->is_active() ? ' (' . $membership->get_status() . ') ' : ''
                    );
                }
                sort($level_links);
                $output = implode(', ', $level_links);
                break;
            default:
        }
        return $output;
    }

    /**
     * @param int $level_id
     *
     * @return int[]
     */
    public function get_level_extends($level_id)
    {
        $levels = [];
        while (isset($this->level_extends_map[$level_id])) {
            $level_id = $this->level_extends_map[$level_id];
            $levels[] = $level_id;
        }
        return $levels;
    }

    /**
     * @param int $level_id
     *
     * @return int[]
     */
    public function get_level_extended_by($level_id)
    {
        $levels = [];
        if (isset($this->level_extended_by_map[$level_id])) {
            foreach ($this->level_extended_by_map[$level_id] as $level) {
                $levels[] = $level;
                $levels = array_merge($levels, $this->get_level_extended_by($level));
            }
        }
        return $levels;
    }

    /**
     * Get all levels
     *
     * @since  0.3
     * @return array
     */
    public function get_levels()
    {
        if (!$this->levels) {
            $levels = get_posts([
                'numberposts' => -1,
                'post_type'   => self::TYPE_RESTRICT,
                'post_status' => [
                    self::STATUS_ACTIVE,
                    self::STATUS_INACTIVE,
                    self::STATUS_SCHEDULED
                ],
                'update_post_meta_cache' => true
            ]);
            foreach ($levels as $level) {
                $this->levels[$level->ID] = $level;
                if ($level->post_parent) {
                    $this->level_extends_map[$level->ID] = $level->post_parent;

                    if (!isset($this->level_extended_by_map[$level->post_parent])) {
                        $this->level_extended_by_map[$level->post_parent] = [];
                    }
                    $this->level_extended_by_map[$level->post_parent][] = $level->ID;
                }
            }
        }
        return $this->levels;
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

        if (!$post || $post->post_type != RUA_App::TYPE_RESTRICT) {
            return;
        }

        global $wpdb;

        //Delete user levels
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->usermeta
			 WHERE
			 (meta_key = %s AND meta_value = %d)
			 OR
			 meta_key = %s",
            self::META_PREFIX . 'level',
            $post_id,
            self::META_PREFIX . 'level_' . $post_id
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

    /**
     * Add actions to plugin in Plugins screen
     *
     * @version 1.0
     * @param   array     $actions
     * @param   string    $plugin_file
     * @param   [type]    $plugin_data
     * @param   [type]    $context
     * @return  array
     */
    public function plugin_action_links($actions, $plugin_file, $plugin_data, $context)
    {
        $new_actions = [];
        $new_actions['docs'] = '<a href="https://dev.institute/docs/restrict-user-access/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=plugin-list&amp;utm_campaign=rua" target="_blank">' . __('Documentation & FAQ', 'restrict-user-access') . '</a>';

        return array_merge($new_actions, $actions);
    }

    /**
     * Load scripts and styles for administration
     *
     * @since  0.1
     * @param  string  $hook
     * @return void
     */
    public function load_admin_scripts($hook)
    {
        $current_screen = get_current_screen();

        if ($current_screen->id == 'nav-menus' || $current_screen->id == 'user-edit' || $current_screen->id == 'profile') {
            //todo: enqueue automatically in wpcacore
            if (wp_script_is('select2', 'registered')) {
                wp_deregister_script('select2');
            }
            wp_register_script(
                'select2',
                plugins_url('/lib/wp-content-aware-engine/assets/js/select2.min.js', __FILE__),
                ['jquery'],
                '4.0.3',
                false
            );
            wp_enqueue_style(self::META_PREFIX . 'condition-groups');

            $levels = [];
            foreach ($this->get_levels() as $level) {
                $levels[] = [
                    'id'   => $level->ID,
                    'text' => $level->post_title
                ];
            }
            wp_enqueue_script('rua/admin/suggest-levels', plugins_url('/assets/js/suggest-levels.min.js', __FILE__), ['select2','jquery'], self::PLUGIN_VERSION);
            wp_localize_script('rua/admin/suggest-levels', 'RUA', [
                'search' => __('Search for Levels', 'restrict-user-access'),
                'levels' => $levels
            ]);
        }
    }

    /**
     * @return RUA_Collection|RUA_Member_Automator[]
     */
    public function get_level_automators()
    {
        if ($this->level_automators === null) {
            $automators = [
                new RUA_Role_Member_Automator(),
                new RUA_Role_Sync_Member_Automator(),
                new RUA_LoggedIn_Member_Automator(),
                new RUA_BP_Member_Type_Member_Automator(),
                new RUA_EDD_Product_Member_Automator(),
                new RUA_WooProduct_Member_Automator()
            ];

            $this->level_automators = new RUA_Collection();
            /** @var RUA_Member_Automator $automator */
            foreach ($automators as $automator) {
                if ($automator->can_enable()) {
                    $this->level_automators->put($automator->get_name(), $automator);
                    if (is_admin()) {
                        add_action(
                            'wp_ajax_rua/automator/' . $automator->get_name(),
                            [$automator,'ajax_print_content']
                        );
                    }
                }
            }
        }
        return $this->level_automators;
    }

    public function process_level_automators()
    {
        $metadata = $this->level_manager->metadata();
        $levels = $this->get_levels();
        $automators = $this->get_level_automators();

        foreach ($levels as $level) {
            if ($level->post_status != RUA_App::STATUS_ACTIVE) {
                continue;
            }

            $automators_data = $metadata->get('member_automations')->get_data($level->ID);
            if (empty($automators_data)) {
                continue;
            }

            foreach ($automators_data as $automator_data) {
                if (!isset($automator_data['value'],$automator_data['name'])) {
                    continue;
                }

                if (!$automators->has($automator_data['name'])) {
                    continue;
                }

                $automators->get($automator_data['name'])->queue($level->ID, $automator_data['value']);
            }
        }

        foreach ($automators as $automator) {
            if (!empty($automator->get_level_data())) {
                $automator->add_callback();
            }
        }
    }
}
