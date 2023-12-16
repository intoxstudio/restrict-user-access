<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2023 by Joachim Jensen
 */

class RUA_User implements RUA_User_Interface
{
    /**
     * @var WP_User
     */
    private $wp_user;

    /**
     * @var RUA_Collection<RUA_User_Level_Interface>|RUA_User_Level_Interface[]|null
     */
    private $level_memberships;

    /**
     * @var array
     */
    private static $caps_cache = [];

    /**
     * @param WP_User $user
     */
    public function __construct(WP_User $user)
    {
        $this->wp_user = $user;
    }

    /**
     * @inheritDoc
     */
    public function get_id()
    {
        return $this->wp_user->ID;
    }

    /**
     * @inheritDoc
     */
    public function get_attribute($name, $default_value = null)
    {
        if ($this->wp_user->has_prop($name)) {
            return $this->wp_user->get($name);
        }
        return $default_value;
    }

    /**
     * @inheritDoc
     */
    public function has_global_access()
    {
        $has_access = in_array('administrator', $this->wp_user->roles);
        return apply_filters('rua/user/global-access', $has_access, $this->wp_user);
    }

    /**
     * @inheritDoc
     */
    public function level_memberships()
    {
        if (is_null($this->level_memberships)) {
            $this->level_memberships = rua_get_user_levels($this);
        }
        return $this->level_memberships;
    }

    /**
     * @since  1.1
     * @param  bool $hierarchical - include inherited levels
     * @param  bool $synced_roles - include levels synced with role
     * @param  bool $include_expired
     * @return array
     */
    public function get_level_ids(
        $hierarchical = true,
        $synced_roles = true,
        $include_expired = false
    ) {
        if (!$hierarchical || !$synced_roles || $include_expired) {
            _deprecated_argument(__FUNCTION__, '2.1');
        }

        $level_ids = [];
        foreach ($this->level_memberships() as $membership) {
            if ($membership->is_active()) {
                $level_ids[] = $membership->get_level_id();
            }
        }

        $level_ids = apply_filters('rua/user_levels', $level_ids, $this);

        foreach ($level_ids as $level_id) {
            $level_ids = array_merge($level_ids, RUA_App::instance()->get_level_extends($level_id));
        }

        return $level_ids;
    }

    /**
     * @inheritDoc
     */
    public function add_level($level_id)
    {
        if ($this->level_memberships()->has($level_id)) {
            /** @var RUA_User_Level_Interface $user_level */
            $user_level = $this->level_memberships()->get($level_id);
            $user_level->update_status(RUA_User_Level::STATUS_ACTIVE);
            $event = 'extended';
        } else {
            $user_level = new RUA_User_Level(get_comment(wp_insert_comment([
                'comment_approved' => RUA_User_Level::STATUS_ACTIVE,
                'comment_type'     => 'rua_member',
                'user_id'          => $this->get_id(),
                'comment_post_ID'  => $level_id,
                'comment_meta'     => [],
            ])));
            wp_update_comment_count($level_id);
            $this->level_memberships()->put($level_id, $user_level);
            $event = 'added';
        }

        $user_level->reset_expiry();
        $this->reset_caps_cache();
        do_action('rua/user_level/' . $event, $this, $level_id);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function remove_level($level_id)
    {
        $level = $this->level_memberships()->get($level_id);

        if (!($level instanceof RUA_User_Level_Interface)) {
            return false;
        }

        $deleted = $level->delete();
        if ($deleted) {
            $this->reset_caps_cache();
            $this->level_memberships()->remove($level_id);
            do_action('rua/user_level/removed', $this, $level_id);
        }
        return $deleted;
    }

    /**
     * @inheritDoc
     */
    public function has_level($level_id)
    {
        $memberships = $this->level_memberships();
        return $memberships->has($level_id) && $memberships->get($level_id)->is_active();
    }

    /**
     * @inheritDoc
     */
    public function get_level_start($level_id)
    {
        _deprecated_function(__FUNCTION__, '2.1', 'level_memberships()->get($level_id)->get_start()');
        if (!$this->has_level($level_id)) {
            return 0;
        }
        return $this->level_memberships()->get($level_id)->get_start();
    }

    /**
     * @inheritDoc
     */
    public function get_level_expiry($level_id)
    {
        _deprecated_function(__FUNCTION__, '2.1', 'level_memberships()->get($level_id)->get_expiry()');
        if (!$this->has_level($level_id)) {
            return 0;
        }
        return $this->level_memberships()->get($level_id)->get_expiry();
    }

    /**
     * @inheritDoc
     */
    public function is_level_expired($level_id)
    {
        _deprecated_function(__FUNCTION__, '2.1', '!level_memberships()->get($level_id)->is_active()');
        if (!$this->has_level($level_id)) {
            return true;
        }
        return !$this->level_memberships()->get($level_id)->is_active();
    }

    /**
     * @inheritDoc
     */
    public function get_caps($current_caps = [])
    {
        if (!isset(self::$caps_cache[$this->get_id()])) {
            self::$caps_cache[$this->get_id()] = $current_caps;

            if (!$this->has_global_access()) {
                $levels = $this->get_level_ids();
                if ($levels) {
                    self::$caps_cache[$this->get_id()] = array_merge(
                        self::$caps_cache[$this->get_id()],
                        //Make sure higher levels have priority
                        //Side-effect: synced levels < normal levels
                        RUA_App::instance()->level_manager->get_levels_caps(array_reverse($levels))
                    );
                }
            }
        }
        return self::$caps_cache[$this->get_id()];
    }

    /**
     * @since  1.1
     */
    private function reset_caps_cache()
    {
        unset(self::$caps_cache[$this->get_id()]);
    }
}
