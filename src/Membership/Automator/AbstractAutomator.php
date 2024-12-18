<?php

namespace RestrictUserAccess\Membership\Automator;

use RestrictUserAccess\Level\PostType;

abstract class AbstractAutomator
{
    public const TYPE_TRIGGER = 'trigger';
    public const TYPE_TRAIT = 'trait';

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
        $this->title = $title;
    }

    public function ajax_print_content()
    {
        if (!check_ajax_referer('rua/admin/edit', 'nonce', false)) {
            wp_die();
        }

        $post_type = get_post_type_object(PostType::NAME);
        if (!current_user_can($post_type->cap->edit_posts)) {
            wp_die();
        }

        $response = $this->search_content(
            isset($_POST['search']) ? $_POST['search'] : null,
            isset($_POST['paged']) ? (int) $_POST['paged'] : 1,
            isset($_POST['limit']) ? (int) $_POST['limit'] : 20
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
    abstract public function get_content_title($selected_value);

    /**
     * @return bool
     */
    public function search_enabled()
    {
        return true;
    }

    /**
     * @param string|null $term
     * @param int $page
     * @param int $limit
     * @return array
     */
    abstract public function search_content($term, $page, $limit);

    /**
     * @return void
     */
    abstract public function add_callback();

    /**
     * @return string
     */
    abstract public function get_description();
}
