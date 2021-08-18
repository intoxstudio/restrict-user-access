<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */
 
class RUA_Role_Member_Automator extends RUA_Member_Automator
{
    protected $type = 'trigger';

    public function __construct()
    {
        parent::__construct('user_role', __('User Role'));
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
            if(empty($this->get_level_data())) {
                return;
            }

            $user = rua_get_user($user_id);
            foreach ($this->get_level_data() as $level_id => $level_roles) {
                if(in_array($role, $level_roles)) {
                    $user->add_level($level_id);
                }
            }
        }, 10, 3);
    }

    /**
     * @inheritDoc
     */
    public function get_content($selected_value = null)
    {
        $role_list = [];
        foreach (get_editable_roles() as $id => $role) {
            if($selected_value !== null && $selected_value !== $id) {
                continue;
            }
            $role_list[$id] = $role['name'];
        }
        return $role_list;
    }
}
