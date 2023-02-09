<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

class RUA_User implements RUA_User_Interface
{
    /**
     * @var WP_User
     */
    private $wp_user;

    /**
     * @var RUA_Collection<RUA_User_Level>|RUA_User_Level[]|null
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
            $user_id = $this->get_id();
            $level_ids = [];

            if ($user_id) {
                $level_ids = (array)get_user_meta($user_id, RUA_App::META_PREFIX . 'level', false);
            }

            $this->level_memberships = new RUA_Collection();
            $level_ids = array_unique($level_ids);
            foreach ($level_ids as $level_id) {
                $level_id = (int)$level_id;
                try {
                    $this->level_memberships->put($level_id, rua_get_user_level($level_id, $this));
                } catch (Exception $e) {
                }
            }
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
        if($this->level_memberships()->has($level_id)) {
            /** @var RUA_User_Level_Interface $user_level */
            $user_level = $this->level_memberships()->get($level_id);
        } else {
            add_user_meta($this->get_id(), RUA_App::META_PREFIX . 'level', $level_id, false);
            $user_level = rua_get_user_level($level_id, $this);
            $user_level->update_start(time());
            $this->level_memberships()->put($level_id, $user_level);
        }

        $user_level->update_status(RUA_User_Level::STATUS_ACTIVE);
        $user_level->reset_expiry();
        $this->reset_caps_cache();
        do_action('rua/user_level/added', $this, $level_id);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function remove_level($level_id)
    {
        $user_id = $this->get_id();
        $this->reset_caps_cache();
        $deleted = delete_user_meta($user_id, RUA_App::META_PREFIX . 'level', $level_id);

        delete_user_meta($user_id, RUA_App::META_PREFIX . 'level_' . $level_id);
        delete_user_meta($user_id, RUA_App::META_PREFIX . 'level_status_' . $level_id);
        delete_user_meta($user_id, RUA_App::META_PREFIX . 'level_expiry_' . $level_id);

        if ($deleted) {
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
