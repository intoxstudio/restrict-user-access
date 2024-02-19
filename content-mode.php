<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class RUA_Content_Mode
{
    protected $handled_content_ids = [];

    public function __construct()
    {
        add_action('wp_head', [$this, 'init']);
        add_action('rest_api_init', [$this, 'init_rest']);
    }

    public function init()
    {
        if (!$this->is_enabled()) {
            return;
        }
        if (is_singular()) {
            return;
        }
        $this->add_content_filters();
    }

    /**
     * @param WP_Rest_Server $wp_rest_server
     * @return void
     */
    public function init_rest(WP_Rest_Server $wp_rest_server)
    {
        if (!$this->is_enabled()) {
            return;
        }
        if (current_user_can('edit_posts')) {
            return;
        }
        $this->add_content_filters();
    }

    /**
     * @param string $content
     * @return string
     */
    public function restrict_the_content($content)
    {
        $id = get_the_ID();
        if (isset($this->handled_content_ids[$id])) {
            return $content;
        }

        $this->handled_content_ids[$id] = true;

        //bail if password is required, wp will show input form
        if (post_password_required()) {
            return $content;
        }
        //bail if is $more_link_text is used, already showing teaser
        if (preg_match('/href=".+#more-[0-9]*"/', $content)) {
            return $content;
        }
        return get_the_excerpt();
    }

    private function is_enabled()
    {
        return (int)get_option('rua_list_content_mode', 0) !== 0;
    }

    private function add_content_filters()
    {
        $option_value = (int)get_option('rua_list_content_mode', 0);
        switch ($option_value) {
            case 1:
                add_filter('the_content', [$this, 'restrict_the_content']);
                return;
            case 2:
                add_filter('the_content', '__return_empty_string');
                add_filter('the_excerpt', '__return_empty_string');
                return;
            default:
                return;
        }
    }
}
