<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class RUA_Level_Manager
{

    /**
     * Metadata
     *
     * @var WPCAObjectManager
     */
    private $metadata;

    public function __construct()
    {
        $this->add_actions();
        $this->add_filters();

        add_shortcode('restrict', array($this,'shortcode_restrict'));
        add_shortcode('restrict-inner', array($this,'shortcode_restrict'));
    }

    /**
     * Add callbacks to actions queue
     *
     * @since 0.5
     */
    protected function add_actions()
    {
        add_action(
            'template_redirect',
            array($this,'authorize_access')
        );
        add_action(
            'init',
            array($this,'create_restrict_type'),
            99
        );
        add_action(
            'user_register',
            array($this,'registered_add_level')
        );
    }

    /**
     * Add callbacks to filters queue
     *
     * @since 0.5
     */
    protected function add_filters()
    {
        if (!is_admin()) {
            add_filter(
                'show_admin_bar',
                array($this,'show_admin_toolbar'),
                99
            );
        }

        //hook early, other plugins might add dynamic caps later
        //fixes problem with WooCommerce Orders
        add_filter(
            'user_has_cap',
            array($this,'user_level_has_cap'),
            9,
            4
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

        $metadata = $this->metadata()->get('hide_admin_bar');

        //if user has at least 1 level without this option
        //don't hide the toolbar
        foreach ($levels as $level_id) {
            if ($metadata->get_data($level_id) != '1') {
                return $show;
            }
        }

        return false;
    }


    /**
     * Get level by name
     *
     * @since  0.6
     * @param  string  $name
     * @return WP_Post|bool
     */
    public function get_level_by_name($name)
    {
        $all_levels = RUA_App::instance()->get_levels();
        foreach ($all_levels as $id => $level) {
            if ($level->post_name == $name && $level->post_status == RUA_App::STATUS_ACTIVE) {
                return $level;
            }
        }
        return false;
    }

    /**
     * Restrict content in shortcode
     *
     * @version 0.1
     * @param   array     $atts
     * @param   string    $content
     * @return  string
     */
    public function shortcode_restrict($atts, $content = null)
    {
        $user = rua_get_user();
        if ($user->has_global_access()) {
            return do_shortcode($content);
        }

        $a = shortcode_atts(array(
            'role'  => '',
            'level' => '',
            'page'  => 0
        ), $atts, 'restrict');

        $has_access = false;

        if ($a['level'] !== '') {
            $level = $this->get_level_by_name(ltrim($a['level'], '!'));
            if ($level) {
                $not = $level->post_name != $a['level'];
                $user_levels = array_flip($user->get_level_ids());
                //when level is negated, give access if user does not have it
                //otherwise give access only if user has it
                if ($not xor isset($user_levels[$level->ID])) {
                    $has_access = true;
                }
            } else {
                //if level does not exist, make content visible
                $has_access = true;
            }
        } elseif ($a['role'] !== '') {
            $roles = explode(',', str_replace(' ', '', $a['role']));
            if (array_intersect($roles, wp_get_current_user()->roles)) {
                $has_access = true;
            }
        }

        /**
         * @var bool $has_access
         * @var RUA_User_Interface $user
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

    /**
     * Get instance of metadata manager
     *
     * @since  1.0
     * @return WPCAObjectManager
     */
    public function metadata()
    {
        if (!$this->metadata) {
            $this->_init_metadata();
        }
        return $this->metadata;
    }

    /**
     * Create and populate metadata fields
     *
     * @since  0.1
     * @return void
     */
    private function _init_metadata()
    {
        $this->metadata = new WPCAObjectManager();
        $this->metadata
        ->add(new WPCAMeta(
            'role',
            __('Synchronized Role'),
            '',
            'select',
            array()
        ), 'role')
        ->add(new WPCAMeta(
            'handle',
            _x('Action', 'option', 'restrict-user-access'),
            0,
            'select',
            array(
                0 => __('Redirect', 'restrict-user-access'),
                1 => __('Tease & Include', 'restrict-user-access')
            ),
            __('Redirect to another page or show teaser.', 'restrict-user-access')
        ), 'handle')
        ->add(new WPCAMeta(
            'page',
            __('Page'),
            0,
            'select',
            array(),
            __('Page to redirect to or display content from under teaser.', 'restrict-user-access')
        ), 'page')
        ->add(new WPCAMeta(
            'duration',
            __('Duration'),
            'day',
            'select',
            array(
                'day'   => __('Day(s)', 'restrict-user-access'),
                'week'  => __('Week(s)', 'restrict-user-access'),
                'month' => __('Month(s)', 'restrict-user-access'),
                'year'  => __('Year(s)', 'restrict-user-access')
            ),
            __('Set to 0 for unlimited.', 'restrict-user-access')
        ), 'duration')
        ->add(new WPCAMeta(
            'caps',
            __('Capabilities'),
            array(),
            '',
            array(),
            ''
        ), 'caps')
        ->add(new WPCAMeta(
            'hide_admin_bar',
            __('Hide Admin Toolbar'),
            '',
            'checkbox',
            array(),
            ''
        ), 'hide_admin_bar');

        apply_filters('rua/metadata', $this->metadata);
    }

    /**
     * Populate input fields for metadata
     *
     * @since  0.8
     * @return void
     */
    public function populate_metadata()
    {
        $role_list = array(
            '' => __('-- None --', 'restrict-user-access'),
            -1 => __('Logged-in', 'restrict-user-access'),
            0  => __('Not logged-in', 'restrict-user-access')
        );

        foreach (get_editable_roles() as $id => $role) {
            $role_list[$id] = $role['name'];
        }

        $this->metadata()->get('role')->set_input_list($role_list);
    }

    /**
     * Create restrict post type and add it to WPCACore
     *
     * @since  0.1
     * @return void
     */
    public function create_restrict_type()
    {

        // Register the sidebar type
        register_post_type(RUA_App::TYPE_RESTRICT, array(
            'labels' => array(
                'name'               => __('Access Levels', 'restrict-user-access'),
                'singular_name'      => __('Access Level', 'restrict-user-access'),
                'add_new'            => _x('Add New', 'level', 'restrict-user-access'),
                'add_new_item'       => __('Add New Access Level', 'restrict-user-access'),
                'edit_item'          => __('Edit Access Level', 'restrict-user-access'),
                'new_item'           => __('New Access Level', 'restrict-user-access'),
                'all_items'          => __('Access Levels', 'restrict-user-access'),
                'view_item'          => __('View Access Level', 'restrict-user-access'),
                'search_items'       => __('Search Access Levels', 'restrict-user-access'),
                'not_found'          => __('No Access Levels found', 'restrict-user-access'),
                'not_found_in_trash' => __('No Access Levels found in Trash', 'restrict-user-access'),
                'parent_item_colon'  => __('Extend Level', 'restrict-user-access'),
                //wp-content-aware-engine specific
                'ca_title' => __('Members-Only Access', 'content-aware-sidebars')
            ),
            'capabilities' => array(
                'edit_post'          => RUA_App::CAPABILITY,
                'read_post'          => RUA_App::CAPABILITY,
                'delete_post'        => RUA_App::CAPABILITY,
                'edit_posts'         => RUA_App::CAPABILITY,
                'delete_posts'       => RUA_App::CAPABILITY,
                'edit_others_posts'  => RUA_App::CAPABILITY,
                'publish_posts'      => RUA_App::CAPABILITY,
                'read_private_posts' => RUA_App::CAPABILITY
            ),
            'public'              => false,
            'hierarchical'        => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'menu_icon'           => RUA_App::ICON_SVG,
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'supports'            => array('title','page-attributes'),
            'can_export'          => false,
            'delete_with_user'    => false
        ));

        WPCACore::types()->add(RUA_App::TYPE_RESTRICT);
    }

    /**
     * Get conditional restrictions
     * and authorize access for user
     *
     * @since  0.1
     * @return void
     */
    public function authorize_access()
    {
        $rua_user = rua_get_user();

        if ($rua_user->has_global_access()) {
            return;
        }

        $posts = WPCACore::get_posts(RUA_App::TYPE_RESTRICT);

        if (!$posts) {
            return;
        }

        $kick = false;
        $levels = array_flip($rua_user->get_level_ids());
        foreach ($posts as $post) {
            if (isset($levels[$post->ID])) {
                $kick = false;
                break;
            }
            $kick = $post->ID;
        }

        if ($kick === false && is_user_logged_in()) {
            $conditions = WPCACore::get_conditions(RUA_App::TYPE_RESTRICT);
            foreach ($conditions as $condition => $level) {
                //Check post type
                if (!isset($posts[$level])) {
                    continue;
                }

                $drip = get_post_meta($condition, RUA_App::META_PREFIX.'opt_drip', true);
                //Restrict access to dripped content
                if ($drip && $this->metadata()->get('role')->get_data($level) === '') {
                    $start = $rua_user->get_level_start($level);
                    $drip_time = strtotime('+'.$drip.' days 00:00', $start);
                    $should_drip = apply_filters(
                        'rua/auth/content-drip',
                        time() <= $drip_time,
                        $rua_user,
                        $level
                    );
                    if ($should_drip) {
                        $kick = $level;
                        continue;
                    }
                }
                $kick = false;
                break;
            }
        }

        if ($kick === false) {
            return;
        }

        $action = $this->metadata()->get('handle')->get_data($kick);
        self::$page = $this->metadata()->get('page')->get_data($kick);
        switch ($action) {
            case 0:
                $redirect = '';
                $url = 'http'.(is_ssl() ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $url = remove_query_arg('redirect_to', $url);
                if (is_numeric(self::$page)) {
                    if (self::$page != get_the_ID()) {
                        $redirect = get_permalink(self::$page);
                    }
                } elseif ($url != get_site_url().self::$page) {
                    $redirect = get_site_url().self::$page;
                }
                //only redirect if current page != redirect page
                if ($redirect) {
                    wp_safe_redirect(add_query_arg(
                        'redirect_to',
                        urlencode($url),
                        $redirect
                    ));
                    exit;
                }
                break;
            case 1:
                add_filter('the_content', array($this,'content_tease'), 8);
                break;
            default: break;
        }
    }

    /**
     * Carry over page from restriction metadata
     * @var integer
     */
    public static $page = false;

    /**
     * Limit content to only show teaser and
     * page content from restriction metadata
     *
     * @since   0.1
     * @param   string    $content
     * @return  string
     */
    public function content_tease($content)
    {
        if (preg_match('/(<span id="more-[0-9]*"><\/span>)/', $content, $matches)) {
            $teaser = explode($matches[0], $content, 2);
            $content = $teaser[0];
        } else {
            $content = '';
        }

        if (is_numeric(self::$page)) {
            setup_postdata(get_post(self::$page));
            $content .= get_the_content();
            wp_reset_postdata();
        }

        return $content;
    }

    /**
     * Override user caps with level caps.
     *
     * @param  array   $allcaps
     * @param  string  $cap
     * @param  array   $args {
     *     @type string  [0] Requested capability
     *     @type int     [1] User ID
     *     @type WP_User [2] Associated object ID (User object)
     * }
     * @param  WP_User $user
     *
     * @return array
     */
    public function user_level_has_cap($allcaps, $cap, $args, $user)
    {
        $rua_user = rua_get_user($user);

        $global_access = $rua_user->has_global_access();

        // if ($cap && $cap[0] == RUA_App::CAPABILITY && $global_access ) {
        // 	$allcaps[ $cap[0] ] = true;
        // }

        if (!$global_access && defined('WPCA_VERSION')) {
            $allcaps = $rua_user->get_caps($allcaps);
        }
        return $allcaps;
    }

    /**
     * Get all capabilities of one or multiple levels
     *
     * If you pass an array the order of these levels should be set correctly!
     * The first level caps will be overwritten by the second etc.
     *
     * @since  0.13
     * @param  array|int  $levels
     * @return array
     */
    public function get_levels_caps($levels)
    {
        $levels = (array) $levels;
        $caps = array();
        foreach ($levels as $level) {
            $level_caps = $this->metadata()->get('caps')->get_data($level, true);
            foreach ($level_caps as $key => $level_cap) {
                $caps[$key] = !!$level_cap;
            }
        }
        return $caps;
    }

    /**
     * Maybe add level on user register
     *
     * @since  0.10
     * @param  int  $user_id
     * @return void
     */
    public function registered_add_level($user_id)
    {
        $level_id = get_option('rua-registration-level', 0);
        if ($level_id) {
            rua_get_user($user_id)->add_level($level_id);
        }
    }
}
