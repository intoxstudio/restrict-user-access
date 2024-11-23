<?php

namespace RestrictUserAccess\Module;

use RestrictUserAccess\Hook\HookService;
use RestrictUserAccess\Hook\HookSubscriberInterface;
use RestrictUserAccess\Repository\SettingRepositoryInterface;

/**
 * Class AdminBar
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class AdminBar implements HookSubscriberInterface
{
    public function subscribe(HookService $service)
    {
        if (is_admin()) {
            return;
        }

        $service->add_filter(
            'show_admin_bar',
            [$this,'show_admin_toolbar'],
            99
        );
    }

    /**
     * Maybe hide admin toolbar for Users
     *
     * @since  1.1
     * @return bool
     */
    public function show_admin_toolbar($show)
    {
        $user = rua_get_user();
        if ($user->has_global_access()) {
            return $show;
        }

        $levels = $user->get_level_ids();
        if (empty($levels)) {
            return $show;
        }

        $metadata = \RUA_App::instance()->level_manager->metadata()->get('hide_admin_bar');
        //if user has at least 1 level without this option
        //don't hide the toolbar
        foreach ($levels as $level_id) {
            if ($metadata->get_data($level_id) != '1') {
                return $show;
            }
        }

        return false;
    }
}
