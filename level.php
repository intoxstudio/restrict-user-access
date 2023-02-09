<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class RUA_Level_Manager
{
    /**
     * Metadata
     *
     * @var WPCACollection
     */
    private $metadata;

    public function __construct()
    {
        $this->add_actions();
        $this->add_filters();

        add_shortcode('restrict', [$this,'shortcode_restrict']);
        add_shortcode('restrict-inner', [$this,'shortcode_restrict']);
        add_shortcode('rua-user-levels', [$this,'shortcode_user_levels']);
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
            [$this,'authorize_access']
        );
        add_action(
            'init',
            [$this,'create_restrict_type'],
            99
        );
        add_action(
            'user_register',
            [$this,'registered_add_level']
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
                [$this,'show_admin_toolbar'],
                99
            );
            add_filter(
                'rua/auth/page-no-access',
                [$this, 'set_multilingual_non_member_action_page'],
                10,
                2
            );
        } else {
            add_action('auth_redirect', [$this, 'authorize_admin_access']);
        }

        add_filter('get_edit_post_link', [$this,'get_edit_post_link'], 10, 3);
        add_filter('get_delete_post_link', [$this,'get_delete_post_link'], 10, 3);
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

        $metadata = $this->metadata()->get('admin_access');
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
     * @param   array     $atts
     * @return  string
     */
    public function shortcode_user_levels($atts)
    {
        $a = shortcode_atts([
            'id' => null
        ], $atts, 'rua-user-level');

        $user = rua_get_user($a['id']);

        $levels = RUA_App::instance()->get_levels();
        $level_names = [];
        foreach ($user->get_level_ids() as $id) {
            if (isset($levels[$id])) {
                $level_names[] = $levels[$id]->post_title;
            }
        }

        return implode(', ', $level_names);
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

        $a = shortcode_atts([
            'role'      => '',
            'level'     => '',
            'page'      => 0,
            'drip_days' => 0
        ], $atts, 'restrict');

        $has_access = false;

        if ($a['level'] !== '') {
            $has_negation = strpos($a['level'], '!') !== false;
            $user_levels = array_flip($user->get_level_ids());
            if (!empty($user_levels) || $has_negation) {
                $level_names = explode(',', str_replace(' ', '', $a['level']));
                $not_found = 0;
                foreach ($level_names as $level_name) {
                    $level = $this->get_level_by_name(ltrim($level_name, '!'));
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
                                $drip_time = strtotime('+' . $drip . ' days 00:00', $start);
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
     * @return WPCACollection
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
        $options = [
            new WPCAMeta(
                'handle',
                _x('Non-Member Action', 'option', 'restrict-user-access'),
                0,
                'select',
                [
                    0 => __('Redirect', 'restrict-user-access'),
                    1 => __('Tease & Include', 'restrict-user-access')
                ],
                __('Redirect to another page or show teaser.', 'restrict-user-access')
            ),
            new WPCAMeta(
                'page',
                __('Page'),
                0,
                'select',
                [],
                __('Page to redirect to or display content from under teaser.', 'restrict-user-access')
            ),
            new WPCAMeta(
                'duration',
                __('Duration'),
                'day',
                'select',
                [
                    'day'   => __('Day(s)', 'restrict-user-access'),
                    'week'  => __('Week(s)', 'restrict-user-access'),
                    'month' => __('Month(s)', 'restrict-user-access'),
                    'year'  => __('Year(s)', 'restrict-user-access')
                ],
                __('Set to 0 for unlimited.', 'restrict-user-access')
            ),
            new WPCAMeta(
                'caps',
                __('Capabilities', 'restrict-user-access'),
                [],
                '',
                [],
                '',
                [$this,'sanitize_capabilities']
            ),
            new WPCAMeta(
                'hide_admin_bar',
                __('Hide Admin Toolbar'),
                '',
                'checkbox',
                [],
                ''
            ),
            new WPCAMeta(
                'default_access',
                __('Deny Access to Unprotected Content', 'restrict-user-access'),
                1,
                'checkbox',
                [],
                '',
                [$this, 'sanitize_checkbox_option']
            ),
            new WPCAMeta(
                'admin_access',
                __('Deny Access to Admin Area', 'restrict-user-access'),
                1,
                'checkbox',
                [],
                '',
                [$this, 'sanitize_checkbox_option']
            ),
            new WPCAMeta(
                'member_automations',
                __('Member Automation'),
                [],
                'select',
                [],
                ''
            )
        ];

        $this->metadata = new WPCACollection();
        foreach ($options as $option) {
            $this->metadata->put($option->get_id(), $option);
        }

        apply_filters('rua/metadata', $this->metadata);
    }

    /**
     * @param array|mixed $value
     *
     * @return array
     */
    public function sanitize_capabilities($value)
    {
        $existing_capabilities = get_post_meta($_POST['post'], WPCACore::PREFIX . 'caps', false);

        if ((is_array($value) && !empty($value)) || !empty($existing_capabilities)) {
            $valid_values = [
                -1 => true,
                0  => true,
                1  => true
            ];
            $value = (array) $value;
            $user = rua_get_user();
            if (!$user->has_global_access()) {
                $value = array_intersect_key($value, $user->get_caps());
            }

            $value = array_merge($existing_capabilities, $value);
            $inherited_caps = isset($_POST['inherited_caps']) ? $_POST['inherited_caps'] : [];
            foreach ($value as $name => $cap) {
                if (is_integer($name) || !isset($valid_values[$cap])) {
                    unset($value[$name]);
                }
                /**
                 * do not save if:
                 * - value is equal to inherited
                 * - no inherited value and unsetting
                 */
                elseif (isset($inherited_caps[$name]) ? $inherited_caps[$name] == $cap
                    : $cap == -1) {
                    unset($value[$name]);
                }
            }
        }
        return $value;
    }

    /**
     * ensure "0" is stored when unchecked
     *
     * @param mixed $value
     * @return mixed
     */
    public function sanitize_checkbox_option($value)
    {
        if (empty($value)) {
            return '0';
        }
        return $value;
    }

    /**
     * Populate input fields for metadata
     *
     * @since  0.8
     * @return void
     */
    public function populate_metadata()
    {
    }

    /**
     * Create restrict post type and add it to WPCACore
     *
     * @since  0.1
     * @return void
     */
    public function create_restrict_type()
    {
        $capability_view = 'list_users';
        $capability_edit = 'promote_users';

        // Register the sidebar type
        register_post_type(RUA_App::TYPE_RESTRICT, [
            'labels' => [
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
            ],
            'capabilities' => [
                'edit_post'          => $capability_edit,
                'read_post'          => $capability_view,
                'delete_post'        => $capability_edit,
                'edit_posts'         => $capability_edit,
                'delete_posts'       => $capability_edit,
                'edit_others_posts'  => $capability_edit,
                'publish_posts'      => $capability_edit,
                'read_private_posts' => $capability_view
            ],
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
            'supports'            => ['title','page-attributes'],
            'can_export'          => false,
            'delete_with_user'    => false
        ]);

        WPCACore::types()->add(RUA_App::TYPE_RESTRICT);
    }

    /**
     * @param string|int $page
     * @param RUA_User_Interface $rua_user
     * @return string|int
     */
    public function set_multilingual_non_member_action_page($page, $rua_user)
    {
        if (!is_numeric($page)) {
            return $page;
        }

        if (defined('POLYLANG_VERSION')) {
            $language_current = pll_current_language();
            $language_target = $language_current !== false ? $language_current : pll_default_language();

            if ($language_target !== false) {
                $page_current_language = pll_get_post($page, $language_target);
                //ensure translated page exists
                if (!empty($page_current_language)) {
                    return $page_current_language;
                }
            }
        }

        return $page;
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

        $authorized_levels = WPCACore::get_posts(RUA_App::TYPE_RESTRICT);

        if ($authorized_levels === false) {
            return;
        }

        $user_levels = array_flip(array_reverse($rua_user->get_level_ids()));
        $kick = false;

        //does user have level to view unrestricted content by default?
        foreach ($user_levels as $level => $val) {
            if ($this->metadata()->get('default_access')->get_data($level, true)) {
                $kick = false;
                break;
            }
            $kick = $level;
        }

        //does user have authorized level?
        foreach ($authorized_levels as $level) {
            if (isset($user_levels[$level->ID])) {
                $kick = false;
                break;
            }
            $kick = $level->ID;
        }

        if (!empty($authorized_levels) && $kick === false && $rua_user->get_id() !== 0) {
            $conditions = WPCACore::get_conditions(RUA_App::TYPE_RESTRICT);
            foreach ($conditions as $condition => $level) {
                //Check post type
                if (!isset($authorized_levels[$level])) {
                    continue;
                }

                $drip = get_post_meta($condition, RUA_App::META_PREFIX . 'opt_drip', true);
                //Restrict access to dripped content
                if ($drip > 0 && $rua_user->has_level($level)) {
                    //@todo if extended level drips content, use start date
                    //of level user is member of
                    $start = $rua_user->level_memberships()->get($level)->get_start();
                    if ($start > 0) {
                        $drip_time = strtotime('+' . $drip . ' days 00:00', $start);
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
                }
                $kick = false;
                break;
            }
        }

        $kick = apply_filters('rua/auth/content-access', $kick, $rua_user);
        if ($kick === false || $kick === null) {
            return;
        }

        $action = is_archive() || (is_home() && !is_page()) ? 0 : $this->metadata()->get('handle')->get_data($kick);

        self::$page = apply_filters('rua/auth/page-no-access', $this->metadata()->get('page')->get_data($kick), $rua_user);
        switch ($action) {
            case 0:
                $redirect = '';

                $current_path = remove_query_arg('redirect_to', add_query_arg(null, null));
                $parts = parse_url(get_site_url());
                $pos = isset($parts['path']) ? stripos($current_path, $parts['path']) : false;
                if ($pos !== false) {
                    $relative_path = substr($current_path, $pos + strlen($parts['path']));
                } else {
                    $relative_path = $current_path;
                }

                if (is_numeric(self::$page)) {
                    if (self::$page != get_the_ID()) {
                        $redirect = get_permalink(self::$page);
                    }
                } else {
                    /**
                     * WP always appends /
                     * also check case where non-member action does not have it,
                     * which can cause infinite loop
                     */
                    if ($relative_path != self::$page && $relative_path != self::$page . '/') {
                        $redirect = get_site_url() . self::$page;
                    }
                }

                //only redirect if current page != redirect page
                if ($redirect) {
                    wp_safe_redirect(add_query_arg(
                        'redirect_to',
                        urlencode($current_path),
                        $redirect
                    ));
                    exit;
                }
                break;
            case 1:
                add_filter('the_content', [$this,'content_tease'], 8);
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
        if (!in_the_loop()) {
            return $content;
        }

        if (get_queried_object_id() !== get_the_ID()) {
            return $content;
        }

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

        remove_filter('the_content', [$this, 'content_tease'], 8);
        return $content;
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
        $caps = [];
        foreach ($levels as $level) {
            $level_caps = $this->metadata()->get('caps')->get_data($level, true);
            foreach ($level_caps as $key => $level_cap) {
                if ($level_cap > -1) {
                    $caps[$key] = (bool)$level_cap;
                } else {
                    unset($caps[$key]);
                }
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
        try {
            $level_id = get_option('rua-registration-level', 0);
            if ($level_id) {
                rua_get_user($user_id)->add_level($level_id);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Get level edit link
     * TODO: Consider changing post type _edit_link instead
     *
     * @since  0.15
     * @param  string  $link
     * @param  int     $post_id
     * @param  string  $context
     * @return string
     */
    public function get_edit_post_link($link, $post_id, $context)
    {
        $post = get_post($post_id);
        if ($post->post_type == RUA_App::TYPE_RESTRICT) {
            $sep = '&';
            if ($context == 'display') {
                $sep = '&amp;';
            }
            $link = admin_url('admin.php?page=wprua-level' . $sep . 'post=' . $post_id);

            //load page in all languages for wpml
            if (defined('ICL_SITEPRESS_VERSION') || defined('POLYLANG_VERSION')) {
                $link .= $sep . 'lang=all';
            }
        }
        return $link;
    }

    /**
     * Get level delete link
     * TODO: Consider changing post type _edit_link instead
     *
     * @since  0.15
     * @param  string   $link
     * @param  int      $post_id
     * @param  boolean  $force_delete
     * @return string
     */
    public function get_delete_post_link($link, $post_id, $force_delete)
    {
        $post = get_post($post_id);
        if ($post->post_type == RUA_App::TYPE_RESTRICT) {
            $action = ($force_delete || !EMPTY_TRASH_DAYS) ? 'delete' : 'trash';

            $link = add_query_arg(
                'action',
                $action,
                admin_url('admin.php?page=wprua-level&post=' . $post_id)
            );
            $link = wp_nonce_url($link, "$action-post_{$post_id}");
        }
        return $link;
    }
}
