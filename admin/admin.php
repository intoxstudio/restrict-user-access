<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
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
            $callback = array($this, $callback);
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
            $callback = array($this, $callback);
        }
        add_filter($tag, $callback, $priority, $accepted_args);
    }

    /**
     * Set up screen and menu if necessary
     *
     * @since 0.15
     */
    public function add_menu()
    {
        $this->_screen = $this->get_screen();
        $this->add_action('load-'.$this->_screen, 'load_screen');
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
        $this->prepare_screen();
        $this->add_action('admin_enqueue_scripts', 'add_scripts_styles', 11);
    }
}
