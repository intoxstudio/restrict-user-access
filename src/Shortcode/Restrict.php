<?php

namespace RestrictUserAccess\Shortcode;

/**
 * Class Restrict
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class Restrict implements ShortcodeInterface
{
    public function get_names()
    {
        return [
            'restrict',
            'restrict-inner'
        ];
    }

    public function get_callback($atts, $content = null)
    {
        $user = rua_get_user();
        if ($user->has_global_access()) {
            return do_shortcode($content);
        }

        $a = shortcode_atts([
            'role'      => '',
            'level'     => '',
            'page'      => 0,
            'drip_days' => 0
        ], $atts, 'restrict');

        $has_access = false;
        $legacy_app = \RUA_App::instance();

        if ($a['level'] !== '') {
            $has_negation = strpos($a['level'], '!') !== false;
            $user_levels = array_flip($user->get_level_ids());
            if (!empty($user_levels) || $has_negation) {
                $level_names = explode(',', str_replace(' ', '', $a['level']));
                $not_found = 0;
                foreach ($level_names as $level_name) {
                    $level = $legacy_app->level_manager->get_level_by_name(ltrim($level_name, '!'));
                    if (!$level) {
                        $not_found++;
                        continue;
                    }
                    //if level param is negated, give access only if user does not have it
                    if ($level->post_name != $level_name) {
                        $has_access = !isset($user_levels[$level->ID]);
                    } elseif (isset($user_levels[$level->ID])) {
                        $drip = (int)$a['drip_days'];
                        if ($drip > 0 && $user->has_level($level->ID)) {
                            //@todo if extended level drips content, use start date
                            //of level user is member of
                            $start = $user->level_memberships()->get($level->ID)->get_start();
                            if ($start > 0) {
                                $drip_time = strtotime('+'.$drip.' days 00:00', $start);
                                $should_drip = apply_filters(
                                    'rua/auth/content-drip',
                                    time() <= $drip_time,
                                    $user,
                                    $level->ID
                                );
                                if ($should_drip) {
                                    continue;
                                }
                            }
                        }
                        $has_access = true;
                    }
                    if ($has_access) {
                        break;
                    }
                }
                //if levels do not exist, make content visible
                if (!$has_access && $not_found && $not_found === count($level_names)) {
                    $has_access = true;
                }
            }
        } elseif ($a['role'] !== '') {
            $user_roles = array_flip(wp_get_current_user()->roles);
            if (!empty($user_roles)) {
                $roles = explode(',', str_replace(' ', '', $a['role']));
                foreach ($roles as $role_name) {
                    $role = ltrim($role_name, '!');
                    $not = $role != $role_name;
                    //when role is negated, give access if user does not have it
                    //otherwise give access only if user has it
                    if ($not xor isset($user_roles[$role])) {
                        $has_access = true;
                        break;
                    }
                }
            }
        }

        /**
         * @var bool $has_access
         * @var \RUA_User_Interface $user
         * @var array $a
         */
        $has_access = apply_filters('rua/shortcode/restrict', $has_access, $user, $a);

        if (!$has_access) {
            $content = '';

            // Only apply the page content if it exists
            $page = $a['page'] ? get_post($a['page']) : null;
            if ($page) {
                setup_postdata($page);
                $content = get_the_content();
                wp_reset_postdata();
            }
        }

        return do_shortcode($content);
    }
}
