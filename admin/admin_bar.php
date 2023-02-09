<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class RUA_Admin_Bar
{
    const NODE_ROOT = 'wprua-tool';
    const NODE_CONDITION_TYPES = 'condition-types';
    const NODE_ACCESS_LEVELS = 'levels';

    const DOCS_MAP = [
        'author'        => 'https://dev.institute/docs/restrict-user-access/conditions/authors/',
        'bb_profile'    => 'https://dev.institute/docs/restrict-user-access/conditions/bbpress-user-profiles/',
        'bp_member'     => 'https://dev.institute/docs/restrict-user-access/conditions/buddypress-profile-sections/',
        'date'          => 'https://dev.institute/docs/restrict-user-access/conditions/dates/',
        'language'      => 'https://dev.institute/docs/restrict-user-access/conditions/languages/',
        'page_template' => 'https://dev.institute/docs/restrict-user-access/conditions/page-templates/',
        'taxonomy'      => 'https://dev.institute/docs/restrict-user-access/conditions/taxonomies/',
        'pods'          => 'https://dev.institute/docs/restrict-user-access/conditions/pods-pages/',
        'post_type'     => 'https://dev.institute/docs/restrict-user-access/conditions/post-types/',
        'static'        => 'https://dev.institute/docs/restrict-user-access/conditions/static-pages/',
    ];

    public function __construct()
    {
        add_action('admin_bar_init', [$this,'initiate']);
    }

    public function initiate()
    {
        if (!$this->authorize_user()) {
            return;
        }

        add_action('admin_bar_menu', [$this,'add_menu'], 99);
        add_action('wp_head', [$this,'print_styles']);
    }

    public function print_styles()
    {
        echo '<style type="text/css" media="screen">' . "\n"; ?>
        #wp-admin-bar-wprua-tool .rua-logo {
            float: left;
            width: 20px;
            height: 30px;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 20px auto;
            background-image: url("<?php echo RUA_App::ICON_SVG; ?>");
        }
        #wp-admin-bar-wprua-tool .wprua-ok .ab-item {
            color:#8c8!important;
            background-color:rgba(136, 204, 136, 0.1);
        }
        #wp-admin-bar-wprua-tool .wprua-warn .ab-item {
            color:#dba617!important;
            background-color:rgba(219, 166, 23, 0.1);
        }
        #wp-admin-bar-wprua-tool #wp-admin-bar-wprua-tool-condition-types .ab-sub-wrapper {
            min-width:100%;
        }
        #wp-admin-bar-wprua-tool #wp-admin-bar-wprua-tool-condition-types .ab-icon {
            float:right!important;
            margin-right:0!important;
            font-size:14px!important;
        }
        <?php
        echo '</style>';
    }

    /**
     * @param WP_Admin_Bar $admin_bar
     * @return void
     */
    public function add_menu($admin_bar)
    {
        $post_type_object = get_post_type_object(RUA_App::TYPE_RESTRICT);

        $this
        ->add_node($admin_bar, [
            'id'    => self::NODE_ROOT,
            'title' => '<span class="ab-item rua-logo"></span>',
            'href'  => admin_url('admin.php?page=wprua'),
            'meta'  => [
                'title' => __('Restrict User Access', 'restrict-user-access')
            ]
        ])
        ->add_node($admin_bar, [
            'id'    => 'add_new',
            'title' => $post_type_object->labels->add_new,
            'href'  => admin_url('admin.php?page=wprua-level'),
        ])
        ->add_node($admin_bar, [
            'id'    => self::NODE_CONDITION_TYPES,
            'title' => __('Condition Types', 'restrict-user-access'),
        ]);

        $cache = get_option(WPCACore::OPTION_CONDITION_TYPE_CACHE, []);
        if (isset($cache[RUA_App::TYPE_RESTRICT]) && !empty($cache[RUA_App::TYPE_RESTRICT])) {
            $this->add_node($admin_bar, [
                'id'    => 'condition_cache',
                'title' => __('Cache Active', 'restrict-user-access'),
                'meta'  => [
                    'class' => 'wprua-ok'
                ]
            ], self::NODE_CONDITION_TYPES);
        } else {
            $this->add_node($admin_bar, [
                'id'    => 'condition_cache',
                'title' => __('Boost Speed Now', 'restrict-user-access'),
                'href'  => wp_nonce_url(admin_url('admin.php?page=wprua-settings&action=update_condition_type_cache'), 'update_condition_type_cache'),
                'meta'  => [
                    'class' => 'wprua-warn',
                ]
            ], self::NODE_CONDITION_TYPES);
        }

        $levels = WPCACore::get_posts(RUA_App::TYPE_RESTRICT);
        $args = [];
        foreach (WPCACore::get_conditional_modules(RUA_App::TYPE_RESTRICT) as $module) {
            $args[] = [
                'id'    => $module->get_id(),
                'title' => $module->get_name()
            ];
        }
        $this->add_nodes($admin_bar, $args, self::NODE_CONDITION_TYPES);

        $admin_bar->add_group([
            'id'     => self::NODE_ROOT . '-' . self::NODE_ACCESS_LEVELS,
            'parent' => self::NODE_ROOT,
            'meta'   => [
                'class' => 'ab-sub-secondary'
            ]
        ]);

        $args = [];
        foreach ($levels as $id => $data) {
            $level = get_post($data->ID);
            $args[] = [
                'id'    => $level->ID,
                'title' => $level->post_title,
                'href'  => get_edit_post_link($level)
            ];
        }
        $this->add_nodes($admin_bar, $args, self::NODE_ACCESS_LEVELS);

        if (empty($args)) {
            $args = [
                'id'    => 'no_levels',
                'title' => __('This page is not restricted', 'restrict-user-access')
            ];
            $this->add_node($admin_bar, $args, self::NODE_ACCESS_LEVELS);
        }
    }

    /**
     * @param WP_Admin_Bar $admin_bar
     * @param array $args
     * @param string $parent
     * @return self
     */
    private function add_node($admin_bar, $args, $parent = null)
    {
        $id = $args['id'];
        if (!isset($args['href']) && array_key_exists($id, self::DOCS_MAP)) {
            $args['title'] = '<span class="ab-icon dashicons dashicons-external"></span> ' . $args['title'];
            $args['href'] = self::DOCS_MAP[$id] . '?utm_source=plugin&amp;utm_medium=admin_bar&amp;utm_campaign=rua';
            if (!isset($args['meta'])) {
                $args['meta'] = [];
            }
            $args['meta'] = array_merge($args['meta'], [
                'target' => '_blank',
                'rel'    => 'noopener'
            ]);
        }

        if ($args['id'] !== self::NODE_ROOT) {
            $args['parent'] = self::NODE_ROOT . (!is_null($parent) ? '-' . $parent : '');
            $args['id'] = $args['parent'] . '-' . $args['id'];
        }
        $admin_bar->add_node($args);

        return $this;
    }

    /**
     * @param WP_Admin_Bar $admin_bar
     * @param array $nodes
     * @param string|null $parent
     * @return void
     */
    private function add_nodes($admin_bar, $nodes, $parent = null)
    {
        usort($nodes, [$this,'sort_nodes']);
        foreach ($nodes as $node_args) {
            $this->add_node($admin_bar, $node_args, $parent);
        }
    }

    /**
     * @param string $a
     * @param string $b
     * @return int
     */
    private function sort_nodes($a, $b)
    {
        return strcasecmp($a['id'], $b['id']);
    }

    /**
     * @return bool
     */
    private function authorize_user()
    {
        $post_type_object = get_post_type_object(RUA_App::TYPE_RESTRICT);
        return current_user_can($post_type_object->cap->create_posts);
    }
}
