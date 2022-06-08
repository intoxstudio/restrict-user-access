<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

abstract class RUA_Admin
{
    /**
     * Screen identifier
     * @var string
     */
    protected $_screen;

    public function __construct()
    {
        if (is_admin()) {
            $this->add_action('admin_menu', 'add_menu', 99);
            $this->admin_hooks();
        } else {
            $this->frontend_hooks();
        }
    }

    /**
     * Set up screen and menu if necessary
     *
     * @since 0.15
     */
    public function add_menu()
    {
        $this->_screen = $this->get_screen();
        $this->add_action('load-' . $this->_screen, 'load_screen');
    }

    /**
     * Add filters and actions for admin dashboard
     * e.g. AJAX calls
     *
     * @since  0.15
     * @return void
     */
    abstract public function admin_hooks();

    /**
     * Add filters and actions for frontend
     *
     * @since  0.15
     * @return void
     */
    abstract public function frontend_hooks();

    /**
     * Get current screen
     *
     * @since  0.15
     * @return string
     */
    abstract public function get_screen();

    /**
     * Prepare screen load
     *
     * @since  0.15
     * @return void
     */
    abstract public function prepare_screen();

    /**
     * Authorize user for screen
     *
     * @since  0.15
     * @return boolean
     */
    abstract public function authorize_user();

    /**
     * Register and enqueue scripts styles
     * for screen
     *
     * @since 0.15
     */
    abstract public function add_scripts_styles();

    /**
     * Prepare plugin upgrade modal
     *
     * @since  0.15
     * @return void
     */
    public function load_screen()
    {
        if (!$this->authorize_user()) {
            wp_die(
                '<p>' . __('You do not have access to this screen.', 'restrict-user-access') . '</p>',
                403
            );
        }

        $path = plugin_dir_path(__FILE__) . '../view/';
        $view = WPCAView::make($path . '/top_bar.php', [
            'freemius' => rua_fs()
        ]);

        add_action('in_admin_header', [$view, 'render']);

        $this->prepare_screen();
        $this->add_action('admin_enqueue_scripts', 'add_general_scripts_styles', 11);
    }

    /**
     * Add general scripts to admin screens
     *
     * @since 1.2
     */
    public function add_general_scripts_styles()
    {
        $this->enqueue_style('rua/admin/style', 'style');
        $this->add_scripts_styles();
    }

    /**
     * @return WP_Post_Type
     */
    protected function get_restrict_type()
    {
        return get_post_type_object(RUA_App::TYPE_RESTRICT);
    }

    /**
     * @since 1.2
     * @param string $tag
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args
     *
     * @return void
     */
    protected function add_action($tag, $callback, $priority = 10, $accepted_args = 1)
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }
        add_action($tag, $callback, $priority, $accepted_args);
    }

    /**
     * @since 1.2
     * @param string $tag
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args
     *
     * @return void
     */
    protected function add_filter($tag, $callback, $priority = 10, $accepted_args = 1)
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }
        add_filter($tag, $callback, $priority, $accepted_args);
    }

    /**
     * @since 1.2
     * @param string $handle
     * @param string $filename
     * @param array $deps
     * @param bool $in_footer
     * @param string $ver
     *
     * @return void
     */
    protected function enqueue_script($handle, $filename, $deps = [], $ver = '', $in_footer = false)
    {
        $this->register_script($handle, $filename, $deps, $ver, $in_footer);
        wp_enqueue_script($handle);
    }

    /**
     * @since 1.2
     * @param string $handle
     * @param string $filename
     * @param array $deps
     * @param bool $in_footer
     * @param string $ver
     *
     * @return void
     */
    protected function register_script($handle, $filename, $deps = [], $ver = '', $in_footer = false)
    {
        $suffix = '.min.js';
        if ($ver === '') {
            $ver = RUA_App::PLUGIN_VERSION;
        }
        wp_register_script($handle, plugins_url('assets/js/' . $filename . $suffix, dirname(__FILE__)), $deps, $ver, $in_footer);
    }

    /**
     * @since 1.2
     * @param string $handle
     * @param string $filename
     * @param array $deps
     * @param string $ver
     *
     * @return void
     */
    protected function enqueue_style($handle, $filename, $deps = [], $ver = '')
    {
        $this->register_style($handle, $filename, $deps, $ver);
        wp_enqueue_style($handle);
    }

    /**
     * @since 1.2
     * @param string $handle
     * @param string $filename
     * @param array $deps
     * @param string $ver
     *
     * @return void
     */
    protected function register_style($handle, $filename, $deps = [], $ver = '')
    {
        $suffix = '.css';
        if ($ver === '') {
            $ver = RUA_App::PLUGIN_VERSION;
        }
        wp_enqueue_style($handle, plugins_url('assets/css/' . $filename . $suffix, dirname(__FILE__)), $deps, $ver);
    }
}
