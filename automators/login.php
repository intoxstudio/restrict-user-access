<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

class RUA_LoggedIn_Member_Automator extends RUA_Member_Automator
{
    protected $type = 'trait';

    public function __construct()
    {
        parent::__construct('login', __('Login State'));
    }

    /**
     * @inheritDoc
     */
    public function get_description()
    {
        return __('Include user for as long as they are');
    }

    /**
     * @inheritDoc
     */
    public function add_callback()
    {
        add_filter('rua/user_levels', function ($level_ids, $user) {
            if(empty($this->get_level_data())) {
                return $level_ids;
            }

            $logged_in_id = get_current_user_id();
            if($user->get_id() !== $logged_in_id) {
                return $level_ids;
            }

            foreach ($this->get_level_data() as $level_id => $states) {
                if($logged_in_id > 0) {
                    $state = 'login';
                } else {
                    $state = 'logout';
                }
                if(in_array($state, $states)) {
                    $level_ids[] = $level_id;
                }
            }
            return $level_ids;
        }, 10, 2);
    }

    /**
     * @inheritDoc
     */
    public function get_content($selected_value = null)
    {
        $states = [
            'login' => __('Logged-in','restrict-user-access'),
            'logout' => __('Not logged-in','restrict-user-access')
        ];

        if($selected_value !== null && isset($states[$selected_value])) {
            return $states[$selected_value];
        }

        return $states;
    }
}
