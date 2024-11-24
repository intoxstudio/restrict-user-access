<?php

namespace RestrictUserAccess\Shortcode;

/**
 * Class UserLevels
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class UserLevels implements ShortcodeInterface
{
    public function get_names()
    {
        return [
            'rua-user-levels'
        ];
    }

    public function get_callback($atts, $content = null)
    {
        $a = shortcode_atts([
            'id' => null
        ], $atts, 'rua-user-level');

        $user = rua_get_user($a['id']);

        $levels = \RUA_App::instance()->get_levels();
        $level_names = [];
        foreach ($user->get_level_ids() as $id) {
            if (isset($levels[$id])) {
                $level_names[] = $levels[$id]->post_title;
            }
        }

        return implode(', ', $level_names);
    }
}
