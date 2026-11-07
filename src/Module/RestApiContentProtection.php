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
        SettingRepositoryInterface $settingRepository
    ) {
        $this->settingRepository = $settingRepository;
    }

    public function subscribe(HookService $service)
    {
        $service->add_filter(
            'rest_pre_dispatch',
            [$this, 'rest_api_access'],
            10,
            3
        );
    }

    public function rest_api_access($result, \WP_REST_Server $server, \WP_REST_Request $request)
    {
        // Dispatch has already been handled elsewhere
        if ($result !== null) {
            return $result;
        }

        if (rua_get_user()->has_global_access()) {
            return null;
        }

        if (! $this->settingRepository->get_bool('rua_rest_api_access', true)) {
            return null;
        }

        // Contributor is the lowest role that should have access,
        // since they can see content in admin area
        if (current_user_can('edit_posts')) {
            return null;
        }

        // Treat /resource/123 as /resource
        $route = preg_replace('#/\d+$#', '', rtrim($request->get_route(), "\n"));
        $route = $this->normalize_rest_route($route);

        foreach ($this->restricted_rest_routes() as $restricted_route) {
            if ($route === $this->normalize_rest_route($restricted_route)) {
                return new \WP_Error(
                    'rest_forbidden',
                    __('Sorry, you are not allowed to do that.'),
                    array(
                        'status' => rest_authorization_required_code(),
                    )
                );
            }
        }

        return null;
    }

    private function restricted_rest_routes(): \Generator
    {
        yield '/wp/v2/search';
        yield '/wp/v2/users';

        $ignored_post_types = [
            'nav_menu_item'    => true,
            'wp_block'         => true,
            'wp_template'      => true,
            'wp_template_part' => true,
            'wp_navigation'    => true,
        ];

        foreach (get_post_types(['show_in_rest' => true], 'objects') as $post_type) {
            if (isset($ignored_post_types[$post_type->name])) {
                continue;
            }

            $route = rest_get_route_for_post_type_items($post_type->name);

            if ($route !== null && $route !== '') {
                yield $route;
            }
        }

        $ignored_taxonomies = [
            'menu' => true,
        ];

        foreach (get_taxonomies(['show_in_rest' => true], 'objects') as $taxonomy) {
            if (isset($ignored_taxonomies[$taxonomy->name])) {
                continue;
            }

            $route = rest_get_route_for_taxonomy_items($taxonomy->name);

            if ($route !== null && $route !== '') {
                yield $route;
            }
        }
    }

    private function normalize_rest_route($route): string
    {
        return strtolower(untrailingslashit('/' . ltrim($route, '/')));
    }
}
