<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class RUA_Members_List extends WP_List_Table
{
    /**
     * @var int
     */
    private $level_id;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'member',
            'plural'   => 'members',
            'ajax'     => false,
            'screen'   => RUA_App::TYPE_RESTRICT . '_members'
        ]);
        $this->level_id = get_the_ID();
    }

    /**
     * Text for no members
     *
     * @since  0.4
     * @return void
     */
    public function no_items()
    {
        _e('No members found.', 'restrict-user-access');
    }

    /**
     * Table columns with titles
     *
     * @since  0.4
     * @return array
     */
    public function get_columns()
    {
        return [
            'cb'           => '<input type="checkbox" />',
            'user_login'   => __('Username'),
            'user_email'   => __('E-mail'),
            'status'       => __('Status', 'restrict-user-access'),
            'member_start' => __('Member Since'),
            'member_end'   => __('Expiration'),
        ];
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        return [];
    }

    /**
     * Primary column used for responsive view
     *
     * @since  0.4
     * @return string
     */
    protected function get_default_primary_column_name()
    {
        return 'user_login';
    }

    /**
     * Default fallback for column render
     *
     * @since  0.4
     * @param  WP_Level_Membership_Interface  $membership
     * @param  string  $column_name
     * @return mixed
     */
    protected function column_default($membership, $column_name)
    {
        $attribute = $membership->user()->get_attribute($column_name);
        echo '<a href="mailto:' . $attribute . '">' . $attribute . '</a>';
    }

    /**
     * Render checkbox column
     *
     * @since  0.4
     * @param  RUA_User_Level_Interface  $membership
     * @return string
     */
    protected function column_cb($membership)
    {
        printf(
            '<input type="checkbox" name="user[]" value="%s" />',
            $membership->get_user_id()
        );
    }

    /**
     * Render user_login column
     *
     * @since  0.4
     * @param  RUA_User_Level_Interface  $membership
     * @return string
     */
    protected function column_user_login(RUA_User_Level_Interface $membership)
    {
        $title = '<strong>' . $membership->user()->get_attribute('user_login') . '</strong>';
        $admin_url = admin_url(sprintf(
            'admin.php?page=wprua-level&post=%s&user=%s',
            $_REQUEST['post'],
            $membership->get_user_id()
        ));
        $actions = [
            'delete' => '<a href="' . wp_nonce_url($admin_url . '&action=remove_user', 'update-post_' . $_REQUEST['post']) . '">' . __('Remove') . '</a>'
        ];
        $actions = apply_filters('rua/member-list/actions', $actions, $membership);
        echo $title . $this->row_actions($actions);
    }

    /**
     * Render expiry date column
     *
     * @since  0.5
     * @param  RUA_User_Level_Interface  $membership
     * @return string
     */
    protected function column_status(RUA_User_Level_Interface $membership)
    {
        $status = $membership->get_status();
        switch ($status) {
            case RUA_User_Level::STATUS_ACTIVE:
                echo '<span class="rua-badge rua-badge-success"><strong>' . __('Active') . '</strong></span>';
                break;
            case RUA_User_Level::STATUS_EXPIRED:
                echo '<span class="rua-badge rua-badge-danger"><strong>' . __('Expired') . '</strong></span>';
                break;
            default:
                echo $status;
        }
    }

    /**
     * @since 1.2
     * @param RUA_User_Level_Interface $membership
     *
     * @return void
     */
    protected function column_member_start(RUA_User_Level_Interface $membership)
    {
        $time = $membership->get_start();
        if ($time) {
            $m_time = date_i18n('Y-m-d', $time);

            $t_time = date_i18n('Y-m-d H:i:s T', $time);

            $time_diff = time() - $time;

            if ($time_diff >= 0 && $time_diff <= MONTH_IN_SECONDS) {
                $h_time = sprintf(__('%s ago'), human_time_diff($time));
            } else {
                $h_time = $m_time;
            }

            echo '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
        }
    }

    /**
     * @since 1.2
     * @param RUA_User_Level_Interface $membership
     *
     * @return void
     */
    protected function column_member_end(RUA_User_Level_Interface $membership)
    {
        $expiry = $membership->get_expiry();
        if ($expiry == 0) {
            echo __('Lifetime', 'restrict-user-access');
        } else {
            $t_time = date_i18n('Y-m-d H:i:s T', $expiry);
            echo '<abbr title="' . $t_time . '">' . sprintf(__('%s from now', 'restrict-user-access'), human_time_diff($expiry)) . '</abbr>';
        }
    }

    /**
     * Bulk actions
     *
     * @since  0.4
     * @return array
     */
    public function get_bulk_actions()
    {
        return [
            'remove_user' => __('Remove', 'restrict-user-access')
        ];
    }

    /**
     * Copied from {@see WP_List_Table} to
     * change "action2" to "action_rua" because of
     * https://core.trac.wordpress.org/ticket/46872
     *
     * @inheritDoc
     */
    protected function bulk_actions($which = '')
    {
        if (is_null($this->_actions)) {
            $this->_actions = $this->get_bulk_actions();

            /**
             * Filters the items in the bulk actions menu of the list table.
             *
             * The dynamic portion of the hook name, `$this->screen->id`, refers
             * to the ID of the current screen.
             *
             * @since 3.1.0
             * @since 5.6.0 A bulk action can now contain an array of options in order to create an optgroup.
             *
             * @param array $actions An array of the available bulk actions.
             */
            $this->_actions = apply_filters("bulk_actions-{$this->screen->id}", $this->_actions); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        }
        $two = '_rua';

        if (empty($this->_actions)) {
            return;
        }

        echo '<label for="bulk-action-selector-' . esc_attr($which) . '" class="screen-reader-text">' . __('Select bulk action') . '</label>';
        echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr($which) . "\">\n";
        echo '<option value="-1">' . __('Bulk actions') . "</option>\n";

        foreach ($this->_actions as $key => $value) {
            if (is_array($value)) {
                echo "\t" . '<optgroup label="' . esc_attr($key) . '">' . "\n";

                foreach ($value as $name => $title) {
                    $class = ('edit' === $name) ? ' class="hide-if-no-js"' : '';

                    echo "\t\t" . '<option value="' . esc_attr($name) . '"' . $class . '>' . $title . "</option>\n";
                }
                echo "\t" . "</optgroup>\n";
            } else {
                $class = ('edit' === $key) ? ' class="hide-if-no-js"' : '';

                echo "\t" . '<option value="' . esc_attr($key) . '"' . $class . '>' . $value . "</option>\n";
            }
        }

        echo "</select>\n";

        submit_button(__('Apply'), 'action', '', false, ['id' => "doaction$two"]);
        echo "\n";
    }

    /**
     * Generate the table navigation above or below the table
     *
     * @since 0.4
     * @param string $which
     */
    public function display_tablenav($which)
    {
        if ($which != 'top') {
            return;
        } ?>
<div class="tablenav <?php echo esc_attr($which); ?>">

    <?php if ($this->has_items()) : ?>
        <div class="alignleft actions bulkactions">
            <?php $this->bulk_actions($which); ?>
        </div>
    <?php
    endif;
        $this->extra_tablenav($which);
        $this->pagination($which); ?>

    <br class="clear" />
</div><?php
    }

    /**
     * Get data and set pagination
     *
     * @since  0.4
     * @return void
     */
    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();

        $per_page = $this->get_items_per_page('members_per_page', 20);
        $current_page = $this->get_pagenum();
        $user_query = new WP_User_Query([
            'meta_key'   => RUA_App::META_PREFIX . 'level',
            'meta_value' => $this->level_id,
            'number'     => $per_page,
            'offset'     => ($current_page - 1) * $per_page
        ]);
        $total_items = (int)$user_query->get_total();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'per_page'    => $per_page
        ]);

        $this->items = [];
        foreach ($user_query->get_results() as $user) {
            $this->items[] = rua_get_user_level($this->level_id, $user);
        }
    }

    /**
     * @since 1.3
     * @param string $url
     * @param string $scheme
     * @param string $orig_scheme
     *
     * @return string
     */
    public function add_url_suffix($url, $scheme, $orig_scheme)
    {
        return $url . '#top#section-members';
    }

    /**
     * Display pagination
     * Adds hashtag to current url
     *
     * @since  0.6
     * @param  string  $which
     * @return void
     */
    public function pagination($which)
    {
        add_filter('set_url_scheme', [$this, 'add_url_suffix'], 10, 3);
        parent::pagination($which);
        remove_filter('set_url_scheme', [$this, 'add_url_suffix'], 10, 3);
    }
}
