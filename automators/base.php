<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

abstract class RUA_Member_Automator
{
    const TYPE_TRIGGER = 'trigger';
    const TYPE_TRAIT = 'trait';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var array
     */
    protected $level_data = [];

    /**
     * @param string $title
     */
    public function __construct($title)
    {
        //backwards compat
        $args = func_get_args();
        if (count($args) == 2) {
            $this->name = $args[0];
            $this->title = $args[1];
            if (is_admin()) {
                add_action(
                    'wp_ajax_rua/automator/' . $this->get_name(),
                    [$this,'ajax_print_content']
                );
            }
            return;
        }

        $this->title = $title;
    }

    public function ajax_print_content()
    {
        if (!check_ajax_referer('rua/admin/edit', 'nonce', false)) {
            wp_die();
        }

        $post_type = get_post_type_object(RUA_App::TYPE_RESTRICT);
        if (!current_user_can($post_type->cap->edit_posts)) {
            wp_die();
        }

        $response = $this->search_content(
            isset($_POST['search']) ? $_POST['search'] : null,
            isset($_POST['paged']) ? $_POST['paged'] : 1,
            isset($_POST['limit']) ? $_POST['limit'] : 20
        );

        $fix_response = [];
        foreach ($response as $id => $title) {
            $fix_response[] = [
                'id'   => $id,
                'text' => $title
            ];
        }

        wp_send_json($fix_response);
    }

    /**
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function get_type_icon()
    {
        return $this->get_type() === self::TYPE_TRIGGER ? 'dashicons-superhero' : 'dashicons-groups';
    }

    /**
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function get_level_data()
    {
        return $this->level_data;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @param int $level_id
     * @param mixed $value
     * @return void
     */
    public function queue($level_id, $value)
    {
        if (!isset($this->level_data[$level_id])) {
            $this->level_data[$level_id] = [];
        }
        $this->level_data[$level_id][] = $value;
    }

    /**
     * @return bool
     */
    public function can_enable()
    {
        return true;
    }

    /**
     * @param mixed $selected_value
     * @return string|null
     */
    public function get_content_title($selected_value)
    {
        //backwards compatibility
        if (!method_exists($this, 'get_content')) {
            throw new Exception('Automator must implement get_content_title()');
        }
        return $this->get_content($selected_value);
    }

    /**
     * @param string|null $term
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function search_content($term, $page, $limit)
    {
        //backwards compatibility
        if (!method_exists($this, 'get_content')) {
            throw new Exception('Automator must implement get_content()');
        }
        return $this->get_content();
    }

    /**
     * @return void
     */
    abstract public function add_callback();

    /**
     * @return string
     */
    abstract public function get_description();
}
