<?php

namespace RestrictUserAccess\Membership\Automator;

use RestrictUserAccess\Hook\HookService;
use RestrictUserAccess\Hook\HookSubscriberInterface;

class AutomatorService implements HookSubscriberInterface
{
    private $level_automators;

    public function subscribe(HookService $service)
    {
        $service->add_action(
            'wpca/loaded',
            [$this,'process_level_automators']
        );
    }

    /**
     * @return \RUA_Collection|AbstractAutomator[]
     */
    public function get_level_automators()
    {
        if ($this->level_automators === null) {
            $automators = [
                new UserRoleTriggerAutomator(),
                new UserRoleTraitAutomator(),
                new LoginStateTraitAutomator(),
                new BPMemberTypeTraitAutomator(),
                new EDDProductTriggerAutomator(),
                new WooProductTriggerAutomator(),
                new GiveWPDonationTriggerAutomator(),
            ];

            $this->level_automators = new \RUA_Collection();
            /** @var AbstractAutomator $automator */
            foreach ($automators as $automator) {
                if ($automator->can_enable()) {
                    $this->level_automators->put($automator->get_name(), $automator);
                }
            }
        }
        return $this->level_automators;
    }

    public function process_level_automators()
    {
        $legacy_app = \RUA_App::instance();
        $metadata = $legacy_app->level_manager->metadata();
        $levels = $legacy_app->get_levels();
        $automators = $this->get_level_automators();

        foreach ($levels as $level) {
            if ($level->post_status != \RUA_App::STATUS_ACTIVE) {
                continue;
            }

            $automators_data = $metadata->get('member_automations')->get_data($level->ID);
            if (empty($automators_data)) {
                continue;
            }

            foreach ($automators_data as $automator_data) {
                if (!isset($automator_data['value'],$automator_data['name'])) {
                    continue;
                }

                if (!$automators->has($automator_data['name'])) {
                    continue;
                }

                $automators->get($automator_data['name'])->queue($level->ID, $automator_data['value']);
            }
        }

        foreach ($automators as $automator) {
            if (!empty($automator->get_level_data())) {
                $automator->add_callback();
            }
            if (is_admin()) {
                add_action(
                    'wp_ajax_rua/automator/'.$automator->get_name(),
                    [$automator,'ajax_print_content']
                );
            }
        }
    }
}
