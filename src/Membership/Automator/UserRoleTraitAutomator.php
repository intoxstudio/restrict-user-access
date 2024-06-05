<?php
namespace RestrictUserAccess\Membership\Automator;

class UserRoleTraitAutomator extends UserRoleTriggerAutomator
{
    protected $type = AbstractAutomator::TYPE_TRAIT;
    protected $name = 'user_role_sync';

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
            if (!$user->get_id()) {
                return $level_ids;
            }

            $current_user = wp_get_current_user();
            if ($user->get_id() !== $current_user->ID) {
                return $level_ids;
            }

            foreach ($this->get_level_data() as $level_id => $level_roles) {
                if (array_intersect($current_user->roles, $level_roles)) {
                    $level_ids[] = $level_id;
                }
            }
            return $level_ids;
        }, 10, 2);
    }
}
