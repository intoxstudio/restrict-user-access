<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2023 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class RUA_Settings_Page extends RUA_Admin
{
    const PREFIX = 'rua_';
    /**
     * Settings slug
     * @var string
     */
    private $slug = 'wprua-settings';

    /**
     * Settings option group
     * @var string
     */
    private $option_group = 'rua-group-main';

    /**
     * Settings prefix
     * @var string
     * @deprecated
     */
    private $prefix = 'rua-';

    /**
     * Settings
     * @var array
     */
    private $settings;

    /**
     * Add filters and actions for admin dashboard
     * e.g. AJAX calls
     *
     * @since  0.15
     * @return void
     */
    public function admin_hooks()
    {
        $this->add_action('admin_init', 'init_settings', 99);
    }

    /**
     * Add filters and actions for frontend
     *
     * @since  0.15
     * @return void
     */
    public function frontend_hooks()
    {
    }

    /**
     * Setup admin menus and get current screen
     *
     * @since  0.15
     * @return string
     */
    public function get_screen()
    {
        $post_type_object = $this->get_restrict_type();
        return add_submenu_page(
            RUA_App::BASE_SCREEN,
            __('User Access Settings', 'restrict-user-access'),
            __('Settings'),
            $post_type_object->cap->edit_posts,
            $this->slug,
            [$this, 'render_screen']
        );
    }

    /**
     * Authorize user for screen
     *
     * @since  0.15
     * @return boolean
     */
    public function authorize_user()
    {
        return current_user_can($this->get_restrict_type()->cap->edit_posts);
    }

    /**
     * @inheritDoc
     */
    public function prepare_screen()
    {
        $this->process_actions();
    }

    /**
     * @inheritDoc
     */
    public function process_actions()
    {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        if (!$action) {
            return;
        }

        check_admin_referer($action);

        $sendback = wp_get_referer();

        switch ($action) {
            case 'update_condition_type_cache':
                WPCACore::cache_condition_types();
                break;
            default:
                break;
        }

        wp_safe_redirect($sendback);
        exit();
    }

    /**
     * Render screen
     *
     * @since  0.15
     * @return void
     */
    public function render_screen()
    {
        ?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
<?php
            settings_errors(); ?>
			<form method="post" action="options.php">
<?php
            settings_fields($this->option_group);
        do_settings_sections($this->slug);
        submit_button(); ?>
			</form>
		</div>
<?php
    }

    /**
     * Register and enqueue scripts styles
     * for screen
     *
     * @since 0.15
     */
    public function add_scripts_styles()
    {
        WPCACore::enqueue_scripts_styles(RUA_App::TYPE_RESTRICT);
    }

    public function init_settings()
    {
        $this->settings = [
            'general' => [
                'name'     => 'general',
                'title'    => __('General', 'restrict-user-access'),
                'callback' => '',
                'fields'   => []
            ]
        ];

        $levels = [
            0 => __('-- None --')
        ];
        foreach (RUA_App::instance()->get_levels() as $id => $level) {
            $levels[$level->ID] = $level->post_title;
        }

        $this->settings['general']['fields'][] = [
            'name'     => 'rua-registration-level',
            'title'    => __('New User Default Level', 'restrict-user-access'),
            'callback' => [$this,'dropdown'],
            'args'     => [
                'options' => $levels
            ]
        ];

        $default_role = get_option('default_role');
        $roles = get_editable_roles();
        $this->settings['general']['fields'][] = [
            'name'     => 'rua-registration-role',
            'title'    => __('New User Default Role'),
            'callback' => [$this,'setting_moved'],
            'args'     => [
                'option'   => !empty($roles[$default_role]) ? $roles[$default_role]['name'] : $default_role,
                'wp_title' => __('General Settings'),
                'url'      => 'options-general.php'
            ],
            'register' => false
        ];

        $this->settings['general']['fields'][] = [
            'name'     => 'rua-registration',
            'title'    => __('Enable Registration', 'restrict-user-access'),
            'callback' => [$this,'setting_moved'],
            'args'     => [
                'option'   => get_option('users_can_register') ? __('Yes') : __('No'),
                'wp_title' => __('General Settings'),
                'url'      => 'options-general.php'
            ],
            'register' => false
        ];

        foreach ($this->settings as $section) {
            add_settings_section(
                $this->prefix . $section['name'],
                $section['title'],
                $section['callback'],
                $this->slug
            );
            foreach ($section['fields'] as $field) {
                $field['args']['title'] = $field['title'];
                $field['args']['label_for'] = $field['name'];
                add_settings_field(
                    $field['name'],
                    $field['title'],
                    $field['callback'],
                    $this->slug,
                    $this->prefix . $section['name'],
                    $field['args']
                );
                if (!isset($field['register']) || $field['register']) {
                    register_setting($this->option_group, $field['name']);
                }
            }
        }
    }

    /**
     * Render checkbox
     *
     * @since  0.10
     * @param  array  $args
     * @return void
     */
    public function checkbox($args)
    {
        $option = $this->get_setting_value($args);

        echo '<label class="cae-toggle">';
        echo '<input type="checkbox" name="' . $args['label_for'] . '" value="1"' . checked($option, 1, 0) . '>';
        echo '<div class="cae-toggle-bar"></div>';
        echo '</label>';
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
        if (isset($args['recommended'])) {
            echo '<p class="description">Recommended: <code>' . $args['recommended'] . '</code></p>';
        }
    }

    /**
     * @param $args
     * @return void
     */
    public function radio($args)
    {
        $current_value = $this->get_setting_value($args);

        echo '<fieldset>';
        echo '<legend class="screen-reader-text">' . $args['title'] . '</legend>';
        echo '<p>';
        foreach ($this->get_options($args) as $option_value => $label) {
            echo '<label>';
            echo '<input type="radio" name="' . $args['label_for'] . '" value="' . $option_value . '"' . checked($current_value, $option_value, false) . '> ' . $label;
            echo '</label><br />';
        }
        echo '</p>';
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
        if (isset($args['recommended'])) {
            echo '<p class="description">Recommended: <code>' . $args['recommended'] . '</code></p>';
        }
        echo '</fieldset>';
    }

    /**
     * @param $args
     * @return void
     */
    public function dropdown($args)
    {
        $current_value = $this->get_setting_value($args);

        echo '<select name="' . $args['label_for'] . '" id="' . $args['label_for'] . '">';
        foreach ($this->get_options($args) as $option_value => $label) {
            echo '<option value="' . $option_value . '" ' . selected($option_value, $current_value, false) . '>' . $label . '</option>';
        }
        echo '</select>';
        if (isset($args['recommended'])) {
            echo '<p class="description">Recommended: <code>' . $args['recommended'] . '</code></p>';
        }
    }

    /**
     * Render moved setting
     *
     * @since  0.10
     * @param  array  $args
     * @return void
     */
    public function setting_moved($args)
    {
        echo $args['option'];
        echo '<p class="description">' . sprintf(
            __('Setting can be changed in %s', 'restrict-user-access'),
            '<a href="' . admin_url($args['url']) . '">' . $args['wp_title'] . '</a>'
        ) . '</p>';
    }

    private function get_setting_value($args)
    {
        return get_option($args['label_for'], isset($args['default_value']) ? $args['default_value'] : false);
    }

    private function get_options($args)
    {
        return isset($args['options']) ? $args['options'] : [];
    }
}
