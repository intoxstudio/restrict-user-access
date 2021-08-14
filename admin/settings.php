<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class RUA_Settings_Page
{

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
     */
    private $prefix = 'rua-';

    /**
     * Settings
     * @var array
     */
    private $settings;

    public function __construct()
    {
        add_action(
            'admin_init',
            [$this, 'init_settings'],
            99
        );
        add_action(
            'admin_menu',
            [$this, 'add_settings_menu'],
            99
        );
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

        $this->settings['general']['fields'][] = [
            'name'     => 'registration-level',
            'title'    => __('New User Default Level', 'restrict-user-access'),
            'callback' => [$this,'dropdown_levels'],
            'args'     => [
                'label_for' => $this->prefix.'registration-level'
            ]
        ];

        $default_role = get_option('default_role');
        $roles = get_editable_roles();
        $this->settings['general']['fields'][] = [
            'name'     => 'registration-role',
            'title'    => __('New User Default Role'),
            'callback' => [$this,'setting_moved'],
            'args'     => [
                'option' => $roles[$default_role]['name'],
                'title'  => __('General Settings'),
                'url'    => 'options-general.php'
            ],
            'register' => false
        ];

        $this->settings['general']['fields'][] = [
            'name'     => 'registration',
            'title'    => __('Enable Registration', 'restrict-user-access'),
            'callback' => [$this,'setting_moved'],
            'args'     => [
                'option' => get_option('users_can_register') ? __('Yes') : __('No'),
                'title'  => __('General Settings'),
                'url'    => 'options-general.php'
            ],
            'register' => false
        ];

        foreach ($this->settings as $section) {
            add_settings_section(
                $this->prefix.$section['name'],
                $section['title'],
                $section['callback'],
                $this->slug
            );
            foreach ($section['fields'] as $field) {
                add_settings_field(
                    $this->prefix.$field['name'],
                    $field['title'],
                    $field['callback'],
                    $this->slug,
                    $this->prefix.$section['name'],
                    $field['args']
                );
                if (!isset($field['register']) || $field['register']) {
                    register_setting($this->option_group, $this->prefix.$field['name']);
                }
            }
        }
    }

    /**
     * Add Settings submenu and page
     *
     * @since  0.10
     * @return void
     */
    public function add_settings_menu()
    {
        $post_type_object = get_post_type_object(RUA_App::TYPE_RESTRICT);
        add_submenu_page(
            RUA_App::BASE_SCREEN,
            __('User Access Settings', 'restrict-user-access'),
            __('Settings'),
            $post_type_object->cap->edit_posts,
            $this->slug,
            [$this, 'settings_page']
        );
    }

    /**
     * Render levels dropdown
     * Skip synchronized levels
     *
     * @since  0.17
     * @param  array  $args
     * @return void
     */
    public function dropdown_levels($args)
    {
        echo '<select name="'.$this->prefix.'registration-level" id="'.$this->prefix.'registration-level">';
        echo '<option value="0">'.__('-- None --').'</option>';
        foreach (RUA_App::instance()->get_levels() as $id => $level) {
            $synced_role = get_post_meta($level->ID, RUA_App::META_PREFIX.'role', true);
            if ($synced_role === '') {
                echo '<option value="'.$level->ID.'" '.selected($level->ID, get_option($this->prefix.'registration-level'), false).'>'.$level->post_title.'</option>';
            }
        }
        echo '</select>';
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
        $option = get_option($args['label_for']);
        echo '<input type="checkbox" name="'.$args['label_for'].'" value="1" '.checked($option, 1, 0).'/>';
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
        echo '<p class="description">'.sprintf(
            __('Setting can be changed in %s', 'restrict-user-access'),
            '<a href="'.admin_url($args['url']).'">'.$args['title'].'</a>'
        ).'</p>';
    }

    /**
     * Render settings page
     *
     * @since  0.10
     * @return void
     */
    public function settings_page()
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
}
