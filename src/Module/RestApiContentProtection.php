<?php

namespace RestrictUserAccess\Module;

use RestrictUserAccess\Hook\HookService;
use RestrictUserAccess\Hook\HookSubscriberInterface;
use RestrictUserAccess\Repository\SettingRepositoryInterface;

/**
 * Class RestApiContentProtection
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class RestApiContentProtection implements HookSubscriberInterface
{
    /** @var SettingRepositoryInterface */
    private $settingRepository;

    public function __construct(
        SettingRepositoryInterface  $settingRepository
    ) {
        $this->settingRepository = $settingRepository;
    }

    public function subscribe(HookService $service)
    {
        $service->add_filter(
            'rest_authentication_errors',
            [$this, 'rest_api_access']
        );
    }

    public function rest_api_access($result)
    {
        //bail if auth has been handled elsewhere
        if ($result === true || is_wp_error($result)) {
            return $result;
        }

        if (rua_get_user()->has_global_access()) {
            return $result;
        }

        if (!$this->settingRepository->get_bool('rua_rest_api_access', true)) {
            return $result;
        }

        //Contributor is the lowest role that should have access,
        //since they can see content in admin area
        if (current_user_can('edit_posts')) {
            return $result;
        }

        $restricted = [
            '/wp/v2/search' => true,
            '/wp/v2/users'  => true
        ];

        $ignored_post_types = [
            'nav_menu_item'    => true,
            'wp_block'         => true,
            'wp_template'      => true,
            'wp_template_part' => true,
            'wp_navigation'    => true
        ];
        foreach (get_post_types(['show_in_rest' => true], 'objects') as $post_type) {
            if (empty($post_type->rest_base)) {
                continue;
            }
            if (isset($ignored_post_types[$post_type->name])) {
                continue;
            }
            $restricted['/'.$post_type->rest_namespace.'/'.$post_type->rest_base] = true;
        }
        $ignored_taxonomies = [
            'menu' => true,
        ];
        foreach (get_taxonomies(['show_in_rest' => true], 'objects') as $taxonomy) {
            if (empty($taxonomy->rest_base)) {
                continue;
            }
            if (isset($ignored_taxonomies[$post_type->name])) {
                continue;
            }
            $restricted['/'.$taxonomy->rest_namespace.'/'.$taxonomy->rest_base] = true;
        }

        global $wp;

        $route = $wp->query_vars['rest_route'];
        $route = preg_replace('/(\/\d+)$/', '', $route, 1);

        if (!isset($restricted[$route])) {
            return $result;
        }

        return new \WP_Error(
            'rest_forbidden',
            __('Sorry, you are not allowed to do that.'),
            ['status' => rest_authorization_required_code()]
        );
    }
}
