<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

class RUA_Role_Member_Automator extends RUA_Member_Automator
{
    protected $type = 'trigger';
    protected $name = 'user_role';

    public function __construct()
    {
        parent::__construct(__('User Role'));
    }

    /**
     * @inheritDoc
     */
    public function get_description()
    {
        return __('Add membership when user gets role');
    }

    /**
     * @inheritDoc
     */
    public function add_callback()
    {
        add_action('set_user_role', function ($user_id, $role, $old_roles) {
            $user = rua_get_user($user_id);
            foreach ($this->get_level_data() as $level_id => $level_roles) {
                if (in_array($role, $level_roles)) {
                    $user->add_level($level_id);
                }
            }
        }, 10, 3);
    }

    /**
     * @inheritDoc
     */
    public function search_content($term, $page, $limit)
    {
        $roles = get_editable_roles();
        uasort($roles, function ($a, $b) {
            return $a['name'] > $b['name'];
        });

        $i = 0;
        $offset = ($page - 1) * $limit;
        $list = [];
        foreach ($roles as $id => $role) {
            if (!empty($term) && stripos($role['name'], $term) === false) {
                continue;
            }
            $i++;
            if ($i <= $offset) {
                continue;
            }
            if ($i > $limit + $offset) {
                break;
            }
            $list[$id] = $role['name'];
        }
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function get_content_title($selected_value)
    {
        $roles = get_editable_roles();
        if (isset($roles[$selected_value]['name'])) {
            return $roles[$selected_value]['name'];
        }
        return null;
    }
}
