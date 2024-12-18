<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

defined('ABSPATH') || exit;

final class RUA_Level_Edit extends RUA_Admin
{
    /**
     * Add filters and actions for admin dashboard
     * e.g. AJAX calls
     *
     * @since  0.15
     * @return void
     */
    public function admin_hooks()
    {
        $this->add_action('save_post_' . RUA_App::TYPE_RESTRICT, 'save_post');
        $this->add_action('rua/admin/add_meta_boxes', 'create_meta_boxes');
        $this->add_action('wp_ajax_rua/user/suggest', 'ajax_get_users');
        $this->add_action('wp_ajax_rua/page/suggest', 'ajax_get_pages');
        $this->add_action('wp_ajax_rua/membership/extend', 'ajax_extend_membership');

        $this->add_filter('wpca/condition/meta', 'register_level_meta', 10, 2);
    }

    /**
     * Register meta data for conditions
     *
     * @since  0.15
     * @param  array   $meta
     * @param  string  $post_type
     * @return array
     */
    public function register_level_meta($meta, $post_type)
    {
        if ($post_type == RUA_App::TYPE_RESTRICT) {
            $meta['_ca_opt_drip'] = 0;
        }
        return $meta;
    }

    /**
     * Get available users for level
     *
     * @since  0.15
     * @return void
     */
    public function ajax_get_users()
    {
        if (!check_ajax_referer('rua/admin/edit', 'nonce', false)) {
            wp_die();
        }

        $results = [];
        $post_type = $this->get_restrict_type();
        if (current_user_can($post_type->cap->edit_posts)) {
            $user_query = new WP_User_Query([
                'search'         => '*' . $_REQUEST['q'] . '*',
                'search_columns' => ['user_login','user_email','user_nicename'],
                'fields'         => ['ID','user_login','user_email'],
                'number'         => 10,
                'offset'         => 0
            ]);
            foreach ($user_query->get_results() as $user) {
                $levels = (array) get_user_meta($user->ID, RUA_App::META_PREFIX . 'level', false);
                if (!in_array($_REQUEST['post_id'], $levels)) {
                    $results[] = $user;
                }
            }
        }
        wp_send_json($results);
    }

    /**
     * Get redirect/include pages for level
     *
     * @since  0.17
     * @return void
     */
    public function ajax_get_pages()
    {
        if (!check_ajax_referer('rua/admin/edit', 'nonce', false)) {
            wp_die();
        }

        $posts_list = [];
        $post_type = $this->get_restrict_type();
        if (current_user_can($post_type->cap->edit_posts)) {
            foreach (get_posts([
                'posts_per_page' => 20,
                'orderby' => 'post_title',
                'order' => 'ASC',
                'post_type' => 'page',
                'post_status' => 'publish',
                's' => $_REQUEST['search'],
                'paged' => $_REQUEST['paged'],
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false
            ]) as $post) {
                $posts_list[] = [
                    'id'   => $post->ID,
                    'text' => $post->post_title ? $post->post_title : __('(no title)')
                ];
            }
        }
        wp_send_json($posts_list);
    }

    public function ajax_extend_membership()
    {
        if (!check_ajax_referer('rua/admin/edit', 'nonce', false)) {
            wp_send_json_error(__('Unauthorized request', 'restrict-user-access'), 403);
        }

        $post_type = $this->get_restrict_type();
        if (!current_user_can($post_type->cap->edit_posts)) {
            wp_send_json_error(__('Unauthorized request', 'restrict-user-access'), 403);
        }

        $level_id = (int) $_POST['post_id'];
        $user_id = (int) $_POST['user_id'];

        switch ((int) $_POST['extend_type']) {
            case 0:
                $expiration = 0;
                break;
            case 1:
                $expiration = get_gmt_from_date($_POST['extend_date'], 'U');
                if (empty($expiration)) {
                    wp_send_json_error(__('Select a valid date and time', 'restrict-user-access'), 400);
                }
                break;
        }

        $level_memberships = rua_get_level_members($level_id, [
            'user_id' => $user_id
        ]);

        if (!$level_memberships->has($user_id)) {
            wp_send_json_error(__('Membership not found', 'restrict-user-access'), 404);
        }

        /** @var RUA_User_Level_Interface $level_membership */
        $level_membership = $level_memberships->get($user_id);

        if ($level_membership->get_expiry() !== $expiration) {
            $level_membership->update_expiry($expiration);
            if (!$level_membership->is_active() && ($expiration === 0 || $expiration > time())) {
                $level_membership->update_status(RUA_User_Level::STATUS_ACTIVE);
            }
        }

        wp_send_json_success();
    }

    /**
     * Meta boxes for restriction edit
     *
     * @since  0.1
     * @return void
     */
    public function create_meta_boxes($post)
    {
        $path = plugin_dir_path(__FILE__) . '../view/';

        $boxes = [];
        $boxes[] = [
            'id'      => 'rua-options',
            'title'   => __('Options', 'restrict-user-access'),
            'view'    => 'options',
            'context' => 'section-options'
        ];
        $boxes[] = [
            'id'      => 'rua-member-triggers',
            'title'   => __('Automations', 'restrict-user-access'),
            'view'    => 'member_triggers',
            'context' => 'section-members'
        ];
        $boxes[] = [
            'id'      => 'rua-members',
            'title'   => __('Members', 'restrict-user-access'),
            'view'    => 'members',
            'context' => 'section-members'
        ];
        $boxes[] = [
            'id'      => 'rua-capabilities',
            'title'   => __('Capabilities', 'restrict-user-access'),
            'view'    => 'caps',
            'context' => 'section-capabilities'
        ];

        //Add meta boxes
        foreach ($boxes as $box) {
            $view = WPCAView::make($path . 'meta_box_' . $box['view'] . '.php', [
                'post' => $post
            ]);

            add_meta_box(
                $box['id'],
                $box['title'],
                [$view,'render'],
                RUA_App::BASE_SCREEN . '-level',
                $box['context'],
                isset($box['priority']) ? $box['priority'] : 'default'
            );
        }

        $this->add_action('wpca/group/settings', 'render_condition_options');

        //todo: refactor add of meta box
        //with new bootstrapper, legacy core might be loaded
        if (method_exists('WPCACore', 'render_group_meta_box')) {
            WPCACore::render_group_meta_box($post, RUA_App::BASE_SCREEN . '-level', 'section-conditions', 'default');
        }
    }

    /**
     * Render support description
     *
     * @since  0.15
     * @param  string  $post_type
     * @return void
     */
    public function show_review_link($post_type)
    {
        if ($post_type == RUA_App::TYPE_RESTRICT) {
            echo '<div style="overflow: hidden; padding: 2px 0px;">';
            echo '<div style="line-height:24px;">';
            echo '<span style="color:rgb(172, 23, 10);">❤</span> ';
            printf(__('Like this plugin? %1$sPlease help make it better with a %2$s rating%3$s. Thank you.', 'restrict-user-access'), '<b><a target="_blank" href="https://wordpress.org/support/plugin/restrict-user-access/reviews/?rate=5#new-post">', '5★', '</a></b>');
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Display extra options for condition group
     *
     * @since  0.15
     * @param  string  $post_type
     * @return void
     */
    public function render_condition_options($post_type)
    {
        if ($post_type == RUA_App::TYPE_RESTRICT) {
            echo '<li class="js-rua-drip-option">';
            echo '<label>' . __('Unlock Time for new members', 'restrict-user-access');
            echo '<div class="wpca-pull-right"><input class="small-text" data-vm="value:integer(_ca_opt_drip)" type="number" min="0" step="1" /> ' . __('days');
            echo '</div></label>';
            echo '</li>';
        }
    }

    /**
     * @param string|WPCAMeta $setting
     * @param string $class
     * @return void
     */
    public static function form_field($setting, $class = '')
    {
        if (!($setting instanceof WPCAMeta)) {
            $setting = RUA_App::instance()->level_manager->metadata()->get($setting);
        }

        $current = $setting->get_data(get_the_ID(), true, $setting->get_input_type() != 'multi');
        $type = $setting->get_input_type();

        if ($type == 'checkbox') {
            $class .= ' cae-toggle';
        }

        echo '<label class="' . $class . '">';
        switch ($setting->get_input_type()) {
            case 'select':
                echo '<select name="' . $setting->get_id() . '" class="js-rua-' . $setting->get_id() . ' rua-input-md">' . "\n";
                foreach ($setting->get_input_list() as $key => $value) {
                    echo '<option value="' . $key . '"' . selected($current, $key, false) . '>' . $value . '</option>' . "\n";
                }
                echo '</select>' . "\n";
                break;
            case 'checkbox':
                echo '<input type="checkbox" name="' . $setting->get_id() . '" value="1"' . ($current == 1 ? ' checked="checked"' : '') . ' />';
                echo '<div class="cae-toggle-bar"></div>';
                break;
            case 'multi':
                echo '<div><select style="width:250px;" class="js-rua-' . $setting->get_id() . '" multiple="multiple"  name="' . $setting->get_id() . '[]" data-value="' . implode(',', $current) . '"></select></div>';
                break;
            case 'text':
            default:
                echo '<input style="width:200px;" type="text" name="' . $setting->get_id() . '" value="' . $current . '" />' . "\n";
                break;
        }
        echo '</label>';
    }

    /**
     * Save metadata values for restriction
     *
     * @since  0.1
     * @param  int  $post_id
     * @return void
     */
    public function save_post($post_id)
    {
        //TODO: check other nonce instead
        if (!(isset($_POST[WPCACore::NONCE])
            && wp_verify_nonce($_POST[WPCACore::NONCE], WPCACore::PREFIX . $post_id))) {
            return;
        }

        $post_type = $this->get_restrict_type();
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        foreach (RUA_App::instance()->level_manager->metadata() as $field) {
            $field->save($post_id);
        }
    }

    /**
     * Set up admin menu and get current screen
     *
     * @since  0.15
     * @return string
     */
    public function get_screen()
    {
        $post_type_object = get_post_type_object(RUA_App::TYPE_RESTRICT);
        return add_submenu_page(
            RUA_App::BASE_SCREEN,
            $post_type_object->labels->add_new_item,
            $post_type_object->labels->add_new,
            $post_type_object->cap->edit_posts,
            RUA_App::BASE_SCREEN . '-level',
            [$this,'render_screen']
        );
    }

    /**
     * Authorize user for screen
     *
     * @since  0.15
     * @return boolean
     */
    public function authorize_user()
    {
        return true;
    }

    /**
     * Prepare screen load
     *
     * @since  0.15
     * @return void
     */
    public function prepare_screen()
    {
        global $post, $title, $active_post_lock;

        $post_type_object = $this->get_restrict_type();
        $post_id = isset($_REQUEST['post']) ? $_REQUEST['post'] : 0;

        //process actions
        $this->process_actions($post_id);

        if (is_multisite()) {
            add_action('admin_footer', '_admin_notice_post_locked');
        } else {
            $check_users = get_users(['fields' => 'ID', 'number' => 2]);
            if (count($check_users) > 1) {
                add_action('admin_footer', '_admin_notice_post_locked');
            }
            unset($check_users);
        }

        /**
         * Edit mode
         */
        if ($post_id) {
            $post = get_post($post_id, OBJECT, 'edit');

            if (!$post) {
                wp_die(__('The level no longer exists.'));
            }
            if (!current_user_can($post_type_object->cap->edit_post, $post_id)) {
                wp_die(__('You are not allowed to edit this level.'));
            }
            if ('trash' == $post->post_status) {
                wp_die(__('You cannot edit this level because it is in the Trash. Please restore it and try again.'));
            }

            if (!empty($_GET['get-post-lock'])) {
                check_admin_referer('lock-post_' . $post_id);
                wp_set_post_lock($post_id);
                wp_redirect(get_edit_post_link($post_id, 'url'));
                exit();
            }

            if (!wp_check_post_lock($post->ID)) {
                $active_post_lock = wp_set_post_lock($post->ID);
            }

            $title = $post_type_object->labels->edit_item;

        /**
         * New Mode
         */
        } else {
            if (!current_user_can($post_type_object->cap->edit_posts) || !current_user_can($post_type_object->cap->create_posts)) {
                wp_die(
                    '<p>' . __('You are not allowed to create levels.', 'restrict-user-access') . '</p>',
                    403
                );
            }

            $post = get_default_post_to_edit(RUA_App::TYPE_RESTRICT, true);

            $title = $post_type_object->labels->add_new_item;
        }

        do_action('rua/admin/add_meta_boxes', $post);
        add_action('in_admin_header', [$this,'render_header']);
    }

    public function render_header()
    {
        global $title, $post;

        if ($post->post_status == 'auto-draft') {
            if (isset($_REQUEST['post'])) {
                $post->post_title = '';
            }
            $button = get_submit_button(__('Create'), 'primary button-large', 'publish', false, [
                'form' => 'post'
            ]);
        } else {
            $button = get_submit_button(__('Save'), 'primary button-large', 'save', false, [
                'form' => 'post'
            ]);
        }

        echo '<div class="rua-header">';
        echo '<h1>';
        echo esc_html($title);
        echo '</h1>';
        echo '<div id="titlediv">';
        echo '<input form="post" type="text" name="post_title" size="20" value="' . esc_attr($post->post_title) . '" id="title" spellcheck="true" autocomplete="off" placeholder="' . esc_attr__('Add title') . '" />';
        echo '</div>';

        echo '<div class="rua-header-actions">';
        echo $button;
        echo '</div>';
        echo '</div>';
    }

    /**
     * @since  1.1
     * @return string
     */
    private function get_request_action()
    {
        if (isset($_POST['s']) && strlen($_POST['s'])) {
            return 'search';
        }

        if (isset($_POST['deletepost'])) {
            return 'delete';
        }

        if (isset($_REQUEST['action_rua']) && $_REQUEST['action_rua'] != -1) {
            return $_REQUEST['action_rua'];
        }

        return isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    }

    /**
     * Process actions
     *
     * @since  0.15
     * @param  int  $post_id
     * @return void
     */
    public function process_actions($post_id)
    {
        $action = $this->get_request_action();

        if (!($action && $post_id)) {
            return;
        }

        $sendback = wp_get_referer();
        $sendback = remove_query_arg(
            ['s', 'message', 'action','action2','trashed', 'untrashed', 'deleted', 'ids'],
            $sendback
        );
        if (!empty($_REQUEST['_rua_section']) && $_REQUEST['_rua_section'][0] === '#') {
            $sendback .= $_REQUEST['_rua_section'];
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_die(__('The level no longer exists.', 'restrict-user-access'));
        }

        $post_type_object = $this->get_restrict_type();

        switch ($action) {
            case 'editpost':
                check_admin_referer('update-post_' . $post_id);

                $post_id = $this->update_level();

                // Session cookie flag that the post was saved
                if (isset($_COOKIE['wp-saving-post']) && $_COOKIE['wp-saving-post'] === $post_id . '-check') {
                    setcookie('wp-saving-post', $post_id . '-saved', time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl());
                }

                $users = isset($_REQUEST['users']) ? $_REQUEST['users'] : null;
                if ($post_id && $users) {
                    foreach ($users as $user) {
                        rua_get_user((int)$user)->add_level($post_id);
                    }
                }

                if (isset($_POST['original_post_status']) && $_POST['original_post_status'] != 'publish') {
                    $message = 2;
                } else {
                    $message = 1;
                }

                $sendback = add_query_arg([
                    'post'    => $post_id,
                    'message' => $message,
                    'page'    => 'wprua-level'
                ], $sendback);
                wp_safe_redirect($sendback);
                exit();
            case 'trash':
                check_admin_referer('trash-post_' . $post_id);

                if (!current_user_can($post_type_object->cap->delete_post, $post_id)) {
                    wp_die(__('You are not allowed to move this level to the Trash.', 'restrict-user-access'));
                }

                if ($user_id = wp_check_post_lock($post_id)) {
                    $user = get_userdata($user_id);
                    wp_die(sprintf(__('You cannot move this level to the Trash. %s is currently editing.', 'restrict-user-access'), $user->display_name));
                }

                if (!wp_trash_post($post_id)) {
                    wp_die(__('Error in moving to Trash.'));
                }

                $sendback = remove_query_arg('post', $sendback);

                wp_safe_redirect(add_query_arg(
                    [
                        'page'    => 'wprua',
                        'trashed' => 1,
                        'ids'     => $post_id
                    ],
                    $sendback
                ));
                exit();
            case 'untrash':
                check_admin_referer('untrash-post_' . $post_id);

                if (!current_user_can($post_type_object->cap->delete_post, $post_id)) {
                    wp_die(__('You are not allowed to restore this level from the Trash.', 'restrict-user-access'));
                }

                if (!wp_untrash_post($post_id)) {
                    wp_die(__('Error in restoring from Trash.'));
                }

                wp_safe_redirect(add_query_arg('untrashed', 1, $sendback));
                exit();
            case 'delete':
                check_admin_referer('delete-post_' . $post_id);

                if (!current_user_can($post_type_object->cap->delete_post, $post_id)) {
                    wp_die(__('You are not allowed to delete this level.', 'restrict-user-access'));
                }

                if (!wp_delete_post($post_id, true)) {
                    wp_die(__('Error in deleting.'));
                }

                $sendback = remove_query_arg('post', $sendback);
                wp_safe_redirect(add_query_arg([
                    'page'    => 'wprua',
                    'deleted' => 1
                ], $sendback));
                exit();
            case 'remove_user':
                check_admin_referer('update-post_' . $post_id);

                if (isset($_REQUEST['user'])) {
                    $users = is_array($_REQUEST['user']) ? $_REQUEST['user'] : [$_REQUEST['user']];
                    $post_id = (int) (isset($_REQUEST['post']) ? $_REQUEST['post'] : $_REQUEST['post_ID']);
                    wp_defer_comment_counting(true);
                    foreach ($users as $user_id) {
                        rua_get_user((int)$user_id)->remove_level($post_id);
                    }
                    wp_defer_comment_counting(false);
                }

                if (!isset($_REQUEST['_rua_section'])) {
                    $sendback .= '#top#section-members';
                }
                wp_safe_redirect($sendback);
                exit;
            case 'search':
                $sendback = add_query_arg([
                    'post' => $post_id,
                    'page' => 'wprua-level',
                    's'    => $_POST['s']
                ], $sendback);
                wp_safe_redirect($sendback);
                exit;
            default:
                do_action('rua/admin/action', $action, $post);
                break;
        }
    }

    private function handle_action_message(WP_Post $post)
    {
        $message_number = isset($_GET['message']) ? absint($_GET['message']) : null;
        if ($message_number === null) {
            return;
        }

        $messages = [
            1 => __('Access level updated.', 'restrict-user-access'),
            2 => __('Access level activated.', 'restrict-user-access'),
            3 => sprintf(
                __('Access level scheduled for: <strong>%1$s</strong>.', 'restrict-user-access'),
                // translators: Publish box date format, see http://php.net/date
                date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date))
            ),
            4 => __('Access level draft updated.', 'restrict-user-access'),
        ];

        if (isset($messages[$message_number])) {
            echo '<div id="message" class="updated notice notice-success is-dismissible"><p>' . $messages[$message_number] . '</p></div>';
        }
    }

    /**
     * Render screen
     *
     * @since  0.15
     * @return void
     */
    public function render_screen()
    {
        global $post, $active_post_lock;

        echo '<div class="wrap">';
        echo '<hr class="wp-header-end">';

        $this->handle_action_message($post);

        echo '<form name="post" action="admin.php?page=wprua-level" method="post" id="post">';
        wp_nonce_field('update-post_' . $post->ID);
        echo '<input type="hidden" id="user-id" name="user_ID" value="' . get_current_user_id() . '" />';
        echo '<input type="hidden" id="_rua_section" name="_rua_section" value="' . (isset($_POST['_rua_section']) ? esc_attr($_POST['_rua_section']) : '') . '" />';
        echo '<input type="hidden" id="hiddenaction" name="action" value="editpost" />';
        echo '<input type="hidden" id="post_author" name="post_author" value="' . esc_attr($post->post_author) . '" />';
        echo '<input type="hidden" id="original_post_status" name="original_post_status" value="' . esc_attr($post->post_status) . '" />';
        echo '<input type="hidden" id="post_ID" name="post" value="' . esc_attr($post->ID) . '" />';
        if (!empty($active_post_lock)) {
            echo '<input type="hidden" id="active_post_lock" value="' . esc_attr(implode(':', $active_post_lock)) . '" />';
        }

        if ($post->post_status != 'draft') {
            wp_original_referer_field(true, 'previous');
        }
        if ($post->post_status == 'auto-draft') {
            echo "<input type='hidden' id='auto_draft' name='auto_draft' value='1' />";
        }

        echo '<div id="poststuff">';
        echo '<div id="post-body" class="metabox-holder rua-metabox-holder columns-1">';
        $this->render_section_nav($post);
        echo '</div>';
        echo '<br class="clear" />';
        echo '</div></form></div>';
    }

    /**
     * @param WP_Post $post
     * @return void
     */
    private function render_section_nav(WP_Post $post)
    {
        $nav_tabs = [
            'conditions'   => __('Access Conditions', 'restrict-user-access'),
            'members'      => __('Members', 'restrict-user-access'),
            'capabilities' => __('Capabilities', 'restrict-user-access'),
            'options'      => __('Options', 'restrict-user-access')
        ];
        $nav_tabs = apply_filters('rua/admin/nav-tabs', $nav_tabs);

        echo '<div id="post-body-content">';
        echo '<h2 class="nav-tab-wrapper js-rua-tabs hide-if-no-js " style="padding-bottom:0;">';
        foreach ($nav_tabs as $id => $label) {
            echo '<a class="js-nav-link nav-tab" href="#top#section-' . $id . '">' . $label . '</a>';
        }
        echo '</h2>';
        echo '</div>';
        $this->render_sections($nav_tabs, $post);
    }

    /**
     * Render meta box sections
     *
     * @since  0.15
     * @param  array    $tabs
     * @param  WP_Post  $post
     * @param  string   $post_type
     * @return void
     */
    public function render_sections($tabs, $post)
    {
        echo '<div id="postbox-container-1" class="postbox-container">';
        do_meta_boxes(RUA_App::BASE_SCREEN . '-level', 'side', $post);
        echo '</div>';
        echo '<div id="postbox-container-2" class="postbox-container">';
        foreach ($tabs as $id => $label) {
            $name = 'section-' . $id;
            echo '<div id="' . $name . '" class="rua-section">';
            do_meta_boxes(RUA_App::BASE_SCREEN . '-level', $name, $post);
            echo '</div>';
        }
        //boxes across sections
        do_meta_boxes(RUA_App::BASE_SCREEN . '-level', 'normal', $post);

        echo '</div>';
    }

    /**
     * @since  0.15
     * @return int
     */
    public function update_level()
    {
        global $wpdb;

        $post = get_post((int) $_POST['post']);

        $post_data = [];
        $post_data['post_type'] = RUA_App::TYPE_RESTRICT;
        $post_data['ID'] = $post->ID;
        $post_data['post_title'] = $_POST['post_title'];
        $post_data['comment_status'] = 'closed';
        $post_data['ping_status'] = 'closed';
        $post_data['post_author'] = get_current_user_id();
        $post_data['post_parent'] = isset($_POST['parent_id']) ? $_POST['parent_id'] : '';
        $post_data['post_status'] = 'publish';
        $post_data['post_name'] = isset($_POST['post_name']) ? $_POST['post_name'] : '';

        $ptype = get_post_type_object($post_data['post_type']);

        if (!current_user_can($ptype->cap->edit_post, $post->ID)) {
            wp_die(__('You are not allowed to edit this level.', 'restrict-user-access'));
        } elseif (!current_user_can($ptype->cap->create_posts)) {
            return new WP_Error('edit_others_posts', __('You are not allowed to create levels.', 'restrict-user-access'));
        } elseif ($post_data['post_author'] != $_POST['post_author']
             && !current_user_can($ptype->cap->edit_others_posts)) {
            return new WP_Error('edit_others_posts', __('You are not allowed to edit this level.', 'restrict-user-access'));
        }

        update_post_meta($post->ID, '_edit_last', $post_data['post_author']);
        wp_update_post($post_data);
        wp_set_post_lock($post->ID);

        return $post->ID;
    }

    /**
     * Register and enqueue scripts styles
     * for screen
     *
     * @since 0.15
     */
    public function add_scripts_styles()
    {
        wp_enqueue_script('wp-a11y');

        if (wp_is_mobile()) {
            wp_enqueue_script('jquery-touch-punch');
        }

        WPCACore::enqueue_scripts_styles(RUA_App::TYPE_RESTRICT);

        $this->enqueue_script('rua/admin/edit', 'edit', ['select2', 'jquery'], '', true);
        wp_localize_script('rua/admin/edit', 'RUA', [
            'copy'  => __('Copy to clipboard', 'restrict-user-access'),
            'nonce' => wp_create_nonce('rua/admin/edit')
        ]);

        //badgeos compat
        //todo: check that developers respond with a fix soon
        wp_register_script('badgeos-select2', '');
        wp_register_style('badgeos-select2-css', '');

        add_thickbox();
    }
}
