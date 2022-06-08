<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

class RUA_LoggedIn_Member_Automator extends RUA_Member_Automator
{
    protected $type = 'trait';
    protected $name = 'login';

    public function __construct()
    {
        parent::__construct(__('Login State'));
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
            $logged_in_id = get_current_user_id();
            if ($user->get_id() !== $logged_in_id) {
                return $level_ids;
            }

            foreach ($this->get_level_data() as $level_id => $states) {
                if ($logged_in_id > 0) {
                    $state = 'login';
                } else {
                    $state = 'logout';
                }
                if (in_array($state, $states)) {
                    $level_ids[] = $level_id;
                }
            }
            return $level_ids;
        }, 10, 2);
    }

    /**
     * @inheritDoc
     */
    public function search_content($term, $page, $limit)
    {
        $list = [];
        foreach ($this->get_states() as $id => $state) {
            if (!empty($term) && stripos($state, $term) === false) {
                continue;
            }
            $list[$id] = $state;
        }
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function get_content_title($selected_value)
    {
        $states = $this->get_states();
        return isset($states[$selected_value]) ? $states[$selected_value] : null;
    }

    private function get_states()
    {
        return [
            'login'  => __('Logged-in', 'restrict-user-access'),
            'logout' => __('Not logged-in', 'restrict-user-access')
        ];
    }
}
