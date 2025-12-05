<?php

namespace RestrictUserAccess\Membership\Automator;

class RegistrationTriggerAutomator extends AbstractAutomator
{
    protected $type = AbstractAutomator::TYPE_TRIGGER;
    protected $name = 'user_registration';

    public function __construct()
    {
        parent::__construct(__('Registration', 'restrict-user-access'));
    }

    /**
     * @inheritDoc
     */
    public function get_description()
    {
        return __('Add membership when user completes registration', 'restrict-user-access');
    }

    /**
     * @inheritDoc
     */
    public function add_callback()
    {
        add_action(
            'user_register',
            function ($user_id) {
                $user = rua_get_user($user_id);
                foreach ($this->get_level_data() as $level_id => $values) {
                    $user->add_level($level_id);
                }
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function search_enabled()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function search_content($term, $page, $limit)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function get_content_title($selected_value)
    {
        return null;
    }
}
