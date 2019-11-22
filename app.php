<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class RUA_App
{

    /**
     * Plugin version
     */
    const PLUGIN_VERSION = '1.2.1';

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
     */
    const CAPABILITY = 'manage_options';

    const BASE_SCREEN = 'wprua';

    const ICON_SVG = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDIwIDIwIj48ZyBmaWxsPSIjYTBhNWFhIj48cGF0aCBkPSJNMTAuMDEyIDE0LjYyNUw1Ljc4IDEyLjI3Yy0xLjkwNi42NjQtMy42MDUgMS43Ni00Ljk4IDMuMTc4IDIuMTA1IDIuNzcgNS40MzYgNC41NiA5LjE4NSA0LjU2IDMuNzY2IDAgNy4xMTItMS44MDIgOS4yMTUtNC41OTMtMS4zOC0xLjQwNC0zLjA3LTIuNDk2LTQuOTctMy4xNTRsLTQuMjE4IDIuMzY3em0tLjAwNS0xNC42M0M3LjQxMi0uMDA1IDUuMzEgMS45MSA1LjMxIDQuMjhoOS4zOTNjMC0yLjM3LTIuMS00LjI4Ni00LjY5Ni00LjI4NnptNi4xMjYgMTAuNzFjLjE1OC0uMDMyLjY0LS4yMzIuNjMtLjMzMy0uMDI1LS4yNC0uNjg2LTUuNTg0LS42ODYtNS41ODRzLS40MjItLjI3LS42ODYtLjI5M2MuMDI0LjIxLjY5IDUuNzYuNzQ1IDYuMjF6bS0xMi4yNTMgMGMtLjE1OC0uMDMyLS42NC0uMjMyLS42My0uMzMzLjAyNS0uMjQuNjg2LTUuNTg0LjY4Ni01LjU4NHMuNDItLjI3LjY4Ni0uMjkzYy0uMDIuMjEtLjY5IDUuNzYtLjc0MiA2LjIxeiIvPjxwYXRoIGQ9Ik0xMCAxMy45NjdoLjAyM2wuOTc1LS41NXYtNC4yMWMuNzgtLjM3NyAxLjMxNC0xLjE3MyAxLjMxNC0yLjA5NyAwLTEuMjg1LTEuMDM1LTIuMzIzLTIuMzItMi4zMjNTNy42NyA1LjgyNSA3LjY3IDcuMTFjMCAuOTIzLjUzNSAxLjcyIDEuMzE1IDIuMDkzVjEzLjRsMS4wMTYuNTY3em0tMS43NjQtLjk4NXYtLjAzNWMwLTMuNjEtMS4zNS02LjU4My0zLjA4My02Ljk2bC0uMDMuMy0uNTIgNC42NyAzLjYzMyAyLjAyNXptMy41Ni0uMDM1YzAgLjAxNCAwIC4wMTguMDAzLjAyM2wzLjYxLTIuMDI1LS41My00LjY4LS4wMjgtLjI3M2MtMS43MjMuNC0zLjA1NyAzLjM2Mi0zLjA1NyA2Ljk1NXoiLz48L2c+PC9zdmc+';

    /**
     * @var array
     */
    private $levels = array();

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
                array($this,'load_admin_scripts'),
                999
            );

            add_action(
                'show_user_profile',
                array($this,'add_field_access_level')
            );
            add_action(
                'edit_user_profile',
                array($this,'add_field_access_level')
            );
            add_action(
                'personal_options_update',
                array($this,'save_user_profile')
            );
            add_action(
                'edit_user_profile_update',
                array($this,'save_user_profile')
            );
            add_action(
                'delete_post',
                array($this,'sync_level_deletion')
            );

            add_filter(
                'manage_users_columns',
                array($this,'add_user_column_headers')
            );
            add_filter(
                'manage_users_custom_column',
                array($this,'add_user_columns'),
                10,
                3
            );
            add_filter(
                'cas/metadata/populate',
                array($this,'add_levels_to_visibility')
            );


            $file = plugin_basename(plugin_dir_path(__FILE__)).'/restrict-user-access.php';
            add_filter(
                'plugin_action_links_'.$file,
                array($this,'plugin_action_links'),
                10,
                4
            );
        }

        add_shortcode(
            'login-form',
            array($this,'shortcode_login_form')
        );

        add_filter(
            'cas/user_visibility',
            array($this,'sidebars_check_levels')
        );
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
     * @param WPCAObjectManager  $metadata
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
            $options = array();
            foreach ($levels as $level) {
                $options[$level->ID] = $level->post_title;
            }

            if (!defined('CAS_App::PLUGIN_VERSION')
                || version_compare(CAS_App::PLUGIN_VERSION, '3.8', '<')) {
                $list = $list + $options;
            } else {
                $list['rua-levels'] = array(
                    'label'   => __('Access Levels', 'restrict-user-access'),
                    'options' => $options
                );
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
        $a = shortcode_atts(array(
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
        ), $atts);
        $a['echo'] = false;

        if (!$a['redirect']) {
            if (isset($_GET['redirect_to'])) {
                $a['redirect'] = urldecode($_GET['redirect_to']);
            } else {
                $a['redirect'] = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
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
        if (current_user_can(self::CAPABILITY) && !is_network_admin()) {
            $user_levels = rua_get_user($user)->get_level_ids(false, false, true); ?>
<h3><?php _e('Access', 'restrict-user-access'); ?>
</h3>
<table class="form-table">
    <tr>
        <th><label for="_ca_level"><?php _e('Access Levels', 'restrict-user-access'); ?></label>
        </th>
        <td>
            <div style="width:25em;"><select style="width:100%;" class="js-rua-levels" multiple="multiple"
                    name="_ca_level[]"
                    data-value="<?php echo esc_html(implode(',', $user_levels)); ?>"></select>
            </div>
            <p class="description"><?php _e('Access Levels synchronized with User Roles will not be listed here.', 'restrict-user-access'); ?>
            </p>
        </td>
    </tr>
</table>
<?php
        }
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
        if (!current_user_can(self::CAPABILITY) || is_network_admin()) {
            return false;
        }

        $user = rua_get_user($user_id);
        $new_levels = isset($_POST[self::META_PREFIX.'level']) ? (array) $_POST[self::META_PREFIX.'level'] : array();

        $user_levels = array_flip($user->get_level_ids(false, false, true));

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
        $new_columns = array();
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
                $levels = $this->get_levels();
                $level_links = array();
                foreach (rua_get_user($user_id)->get_level_ids(false, true, true) as $user_level) {
                    $user_level = isset($levels[$user_level]) ? $levels[$user_level] : null;
                    if ($user_level) {
                        $level_links[] = '<a href="'.get_edit_post_link($user_level->ID).'">'.$user_level->post_title.'</a>';
                    }
                }
                $output = implode(', ', $level_links);
                break;
            default:
        }
        return $output;
    }

    /**
     * Get all levels not synced with roles
     *
     * @since  0.3
     * @return array
     */
    public function get_levels()
    {
        if (!$this->levels) {
            $levels = get_posts(array(
                'numberposts' => -1,
                'post_type'   => self::TYPE_RESTRICT,
                'post_status' => array(
                    self::STATUS_ACTIVE,
                    self::STATUS_INACTIVE,
                    self::STATUS_SCHEDULED
                )
            ));
            foreach ($levels as $level) {
                $this->levels[$level->ID] = $level;
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
        if (!current_user_can(self::CAPABILITY)) {
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
            self::META_PREFIX.'level',
            $post_id,
            self::META_PREFIX.'level_'.$post_id
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
        $new_actions = array();
        $new_actions['docs'] = '<a href="https://dev.institute/docs/restrict-user-access/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=plugin-list&amp;utm_campaign=rua" target="_blank">'.__('Documentation & FAQ', 'restrict-user-access').'</a>';

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
                array('jquery'),
                '4.0.3',
                false
            );
            wp_enqueue_style(self::META_PREFIX.'condition-groups');

            $levels = array();
            foreach ($this->get_levels() as $level) {
                $synced_role = get_post_meta($level->ID, self::META_PREFIX.'role', true);
                if ($current_screen->id != 'nav-menus' && $synced_role !== '') {
                    continue;
                }
                $levels[] = array(
                    'id'   => $level->ID,
                    'text' => $level->post_title
                );
            }
            wp_enqueue_script('rua/admin/suggest-levels', plugins_url('/assets/js/suggest-levels.min.js', __FILE__), array('select2','jquery'), self::PLUGIN_VERSION);
            wp_localize_script('rua/admin/suggest-levels', 'RUA', array(
                'search' => __('Search for Levels', 'restrict-user-access'),
                'levels' => $levels
            ));
        }
    }
}
