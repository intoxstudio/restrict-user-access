<?php
namespace RestrictUserAccess\Module;

use RestrictUserAccess\Hook\HookService;
use RestrictUserAccess\Hook\HookSubscriberInterface;
use RestrictUserAccess\Repository\SettingRepositoryInterface;

/**
 * Class AdminAccess
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class AdminAccess implements HookSubscriberInterface
{
    public function subscribe(HookService $service)
    {
        if (!is_admin()) {
            return;
        }

        $service->add_action('auth_redirect', [$this, 'authorize_admin_access']);
    }

    /**
     * @param int $user_id
     * @return void
     */
    public function authorize_admin_access($user_id)
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        $rua_user = rua_get_user($user_id);
        if ($rua_user->has_global_access()) {
            return;
        }

        $user_levels = $rua_user->get_level_ids();
        if (empty($user_levels)) {
            return;
        }


        $metadata = \RUA_App::instance()->level_manager->metadata()->get('admin_access');
        foreach ($user_levels as $level_id) {
            //bail if user has at least 1 level with admin access
            if ($metadata->get_data($level_id, true)) {
                return;
            }
        }

        if (apply_filters('rua/auth/admin-access', false, $rua_user)) {
            return;
        }

        wp_die(__('Sorry, you are not allowed to access this page.'));
    }
}
