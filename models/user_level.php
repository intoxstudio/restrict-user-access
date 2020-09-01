<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2020 by Joachim Jensen
 */

class RUA_User_Level implements RUA_User_Level_Interface
{
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';

    const KEY_STATUS = 'level_status';
    const KEY_START = 'level';
    const KEY_EXPIRY = 'level_expiry';

    /**
     * @var RUA_User_Interface
     */
    private $user;

    /**
     * @var RUA_Level_Interface
     */
    private $level;

    /**
     * @var bool
     */
    private $synced_role;

    /**
     * @since 2.1
     * @param RUA_User_Interface $user
     * @param RUA_Level_Interface $level
     */
    public function __construct(RUA_User_Interface $user, RUA_Level_Interface $level)
    {
        $this->user = $user;
        $this->level = $level;
        $this->synced_role = !empty(get_post_meta($level->get_id(), RUA_App::META_PREFIX.'role', true));
    }

    public function refresh()
    {
        if ($this->is_active() && $this->is_expired()) {
            $this->update_meta(self::KEY_STATUS, self::STATUS_EXPIRED);
        }
    }

    /**
     * @inheritDoc
     */
    public function get_user_id()
    {
        return $this->user()->get_id();
    }

    /**
     * @inheritDoc
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function get_level_id()
    {
        return $this->level()->get_id();
    }

    /**
     * @inheritDoc
     */
    public function get_level_extend_ids()
    {
        return RUA_App::instance()->get_level_extends($this->get_level_id());
    }

    /**
     * @inheritDoc
     */
    public function level()
    {
        return $this->level;
    }

    /**
     * @inheritDoc
     */
    public function get_status()
    {
        if ($this->synced_role) {
            return self::STATUS_ACTIVE;
        }

        $status = $this->get_meta(self::KEY_STATUS);

        //fallback to calc
        if (is_null($status)) {
            $status = $this->is_expired() ? self::STATUS_EXPIRED : self::STATUS_ACTIVE;
            $this->update_meta(self::KEY_STATUS, $status);
        }

        return $status;
    }

    /**
      * @inheritDoc
      */
    public function get_start()
    {
        return (int)$this->get_meta(self::KEY_START, 0);
    }

    /**
     * @inheritDoc
     */
    public function get_expiry()
    {
        if ($this->synced_role) {
            return 0;
        }

        $expiry = $this->get_meta(self::KEY_EXPIRY);
        if ($expiry) {
            return (int) $expiry;
        }

        //fallback to calc
        $time = $this->get_start();
        $duration = RUA_App::instance()->level_manager->metadata()->get('duration')->get_data($this->level()->get_id());
        if (isset($duration['count'],$duration['unit']) && $time && $duration['count']) {
            $time = strtotime('+'.$duration['count'].' '.$duration['unit']. ' 23:59', $time);
            $this->update_meta(self::KEY_EXPIRY, $time);
            return $time;
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function is_active()
    {
        return $this->get_status() === self::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function can_add()
    {
        return $this->get_user_id() && !$this->synced_role;
    }

    /**
     * @return bool
     */
    private function is_expired()
    {
        $time_expire = $this->get_expiry();
        return $time_expire && time() > $time_expire;
    }

    /**
     * @since 1.0
     * @param string $key
     * @param mixed|null $default_value
     *
     * @return mixed|null
     */
    private function get_meta($key, $default_value = null)
    {
        if (!$this->can_add()) {
            return $default_value;
        }
        $user_id = $this->get_user_id();
        return $this->user()->get_attribute(RUA_App::META_PREFIX.$key.'_'.$this->get_level_id(), $default_value);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    private function update_meta($key, $value)
    {
        if (!$this->can_add()) {
            return false;
        }

        $user_id = $this->get_user_id();
        return (bool)update_user_meta($user_id, RUA_App::META_PREFIX.$key.'_'.$this->get_level_id(), $value);
    }
}
