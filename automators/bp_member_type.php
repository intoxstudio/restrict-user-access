<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

class RUA_BP_Member_Type_Member_Automator extends RUA_Member_Automator
{
    protected $type = 'trait';
    protected $name = 'bp_member_type';

    public function __construct()
    {
        parent::__construct(__('BuddyPress Member Type'));
    }

    /**
     * @inheritDoc
     */
    public function get_description()
    {
        return __('Include user for as long as they have Member Type');
    }

    /**
     * @inheritDoc
     */
    public function can_enable()
    {
        return function_exists('BP_VERSION');
    }

    /**
     * @inheritDoc
     */
    public function add_callback()
    {
        add_filter('rua/user_levels', function ($level_ids, $user) {
            $logged_in_id = get_current_user_id();
            if ($logged_in_id === 0 || $user->get_id() !== $logged_in_id) {
                return $level_ids;
            }

            $member_types = bp_get_member_type($user->get_id(), false);
            if (empty($member_types)) {
                return $level_ids;
            }

            foreach ($this->get_level_data() as $level_id => $level_member_types) {
                if (array_intersect($level_member_types, $member_types)) {
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
        $types = $this->get_types();
        asort($types);

        $i = 0;
        $offset = ($page - 1) * $limit;
        $list = [];
        foreach ($types as $id => $type) {
            if (!empty($term) && stripos($type, $term) === false) {
                continue;
            }
            $i++;
            if ($i <= $offset) {
                continue;
            }
            if ($i > $limit + $offset) {
                break;
            }
            $list[$id] = $type;
        }
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function get_content_title($selected_value)
    {
        $types = $this->get_types();
        return isset($types[$selected_value]) ? $types[$selected_value] : null;
    }

    /**
     * @return array
     */
    private function get_types()
    {
        $types = [];
        foreach (bp_get_member_types([], 'objects') as $type) {
            $types[$type->name] = $type->labels['singular_name'] ?: $type->name;
        }
        return $types;
    }
}
