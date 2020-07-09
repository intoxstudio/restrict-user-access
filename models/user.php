<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2020 by Joachim Jensen
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
    private static $caps_cache = array();

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
        $has_access = in_array('administrator', $this->get_roles());
        return apply_filters('rua/user/global-access', $has_access, $this->wp_user);
    }

    /**
     * @inheritDoc
     */
    public function level_memberships()
    {
        if (is_null($this->level_memberships)) {
            $user_id = $this->get_id();
            $level_ids = array();

            if ($user_id) {
                $level_ids = (array)get_user_meta($user_id, RUA_App::META_PREFIX.'level', false);
            }

            $all_levels = RUA_App::instance()->get_levels();
            $user_roles = array_flip($this->get_roles());
            foreach ($all_levels as $level) {
                $synced_role = get_post_meta($level->ID, RUA_App::META_PREFIX.'role', true);
                if ($synced_role !== '' && isset($user_roles[$synced_role])) {
                    $level_ids[] = $level->ID;
                }
            }

            $this->level_memberships = new RUA_Collection();
            $level_ids = array_unique($level_ids);
            foreach ($level_ids as $level_id) {
                $level_id = (int)$level_id;
                try {
                    $this->level_memberships->put($level_id, rua_get_user_level($level_id, $this));
                } catch (Throwable $e) {
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
        $all_levels = RUA_App::instance()->get_levels();
        $levels = array();
        $user_id = $this->wp_user->ID;

        if ($user_id) {
            $user_levels = get_user_meta($user_id, RUA_App::META_PREFIX.'level', false);
            foreach ($user_levels as $level) {
                //Only get user levels that are active
                if (isset($all_levels[$level]) && $all_levels[$level]->post_status == RUA_App::STATUS_ACTIVE) {
                    if ($include_expired || !$this->is_level_expired($level)) {
                        $levels[] = $level;
                    }
                }
            }
        }

        if ($synced_roles) {
            $user_roles = array_flip($this->get_roles());
            foreach ($all_levels as $level) {
                $synced_role = get_post_meta($level->ID, RUA_App::META_PREFIX.'role', true);
                if ($synced_role !== '' && isset($user_roles[$synced_role])) {
                    $levels[] = $level->ID;
                }
            }
        }

        if ($hierarchical) {
            foreach ($levels as $level) {
                $levels = array_merge($levels, get_post_ancestors((int)$level));
            }
        }
        $levels = array_unique($levels);
        update_postmeta_cache($levels);
        return $levels;
    }

    /**
     * @inheritDoc
     */
    public function add_level($level_id)
    {
        $user_id = $this->wp_user->ID;
        if (!$this->has_level($level_id)) {
            $this->reset_caps_cache();
            $user_level = add_user_meta($user_id, RUA_App::META_PREFIX.'level', $level_id, false);
            if ($user_level) {
                add_user_meta($user_id, RUA_App::META_PREFIX.'level_'.$level_id, time(), true);
                $this->level_memberships()->put($level_id, rua_get_user_level($level_id, $this));
            }
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function remove_level($level_id)
    {
        $user_id = $this->wp_user->ID;
        $this->reset_caps_cache();
        $deleted = delete_user_meta($user_id, RUA_App::META_PREFIX.'level', $level_id);

            delete_user_meta($user_id, RUA_App::META_PREFIX.'level_'.$level_id);
        delete_user_meta($user_id, RUA_App::META_PREFIX.'level_status_'.$level_id);
        delete_user_meta($user_id, RUA_App::META_PREFIX.'level_expiry_'.$level_id);

        if ($deleted) {
            $this->level_memberships()->remove($level_id);
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
        $user_id = $this->wp_user->ID;
        if ($user_id) {
            return (int)get_user_meta($user_id, RUA_App::META_PREFIX.'level_'.$level_id, true);
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function get_level_expiry($level_id)
    {
        $user_id = $this->wp_user->ID;
        if ($user_id) {
            $time = $this->get_level_start($level_id);
            $duration = RUA_App::instance()->level_manager->metadata()->get('duration')->get_data($level_id);
            if (isset($duration['count'],$duration['unit']) && $time && $duration['count']) {
                $time = strtotime('+'.$duration['count'].' '.$duration['unit']. ' 23:59', $time);
                return $time;
            }
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function is_level_expired($level_id)
    {
        $time_expire = $this->get_level_expiry($level_id);
        return $time_expire && time() > $time_expire;
    }

    /**
     * @inheritDoc
     */
    public function get_caps($current_caps = array())
    {
        if (!isset(self::$caps_cache[$this->wp_user->ID])) {
            self::$caps_cache[$this->wp_user->ID] = $current_caps;
            $levels = $this->get_level_ids();
            if ($levels) {
                self::$caps_cache[$this->wp_user->ID] = array_merge(
                    self::$caps_cache[$this->wp_user->ID],
                    //Make sure higher levels have priority
                    //Side-effect: synced levels < normal levels
                    RUA_App::instance()->level_manager->get_levels_caps(array_reverse($levels))
                );
            }
        }
        return self::$caps_cache[$this->wp_user->ID];
    }

    /**
     * @since  1.1
     */
    private function reset_caps_cache()
    {
        unset(self::$caps_cache[$this->wp_user->ID]);
    }

    /**
     * @since  1.1
     * @return array
     */
    private function get_roles()
    {
        if (!$this->wp_user->exists()) {
            return array('0'); //not logged-in pseudo role
        }

        $roles = $this->wp_user->roles;
        $roles[] = '-1'; //logged-in
        return $roles;
    }
}
