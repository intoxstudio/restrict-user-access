<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

defined('ABSPATH') || exit;

/**
 * @deprecated
 */
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
                'rua/auth/page-no-access',
                [$this, 'set_multilingual_non_member_action_page'],
                10,
                2
            );
        }
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
            if ($level->post_name == $name && $level->post_status == RUA_Level::STATUS_ACTIVE) {
                return $level;
            }
        }
        return false;
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
                __('Duration', 'restrict-user-access'),
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
                __('Hide Admin Toolbar', 'restrict-user-access'),
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
                __('Member Automation', 'restrict-user-access'),
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
}
