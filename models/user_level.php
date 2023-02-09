<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
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
     * @since 2.1
     * @param RUA_User_Interface $user
     * @param RUA_Level_Interface $level
     */
    public function __construct(RUA_User_Interface $user, RUA_Level_Interface $level)
    {
        $this->user = $user;
        $this->level = $level;
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
        $status = $this->get_meta(self::KEY_STATUS);

        //fallback to calc
        if (is_null($status)) {
            $status = $this->is_expired() ? self::STATUS_EXPIRED : self::STATUS_ACTIVE;
            $this->update_status($status);
        }

        return $status;
    }

    /**
     * @inheritDoc
     */
    public function update_status($status)
    {
        $this->update_meta(self::KEY_STATUS, $status);
        return $this;
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
    public function update_start($start)
    {
        $this->update_meta(self::KEY_START, (int) $start);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get_expiry()
    {
        $expiry = $this->get_meta(self::KEY_EXPIRY);
        if ($expiry) {
            return (int) $expiry;
        }

        //fallback to calc
        $time = $this->get_start();
        $duration = RUA_App::instance()->level_manager->metadata()->get('duration')->get_data($this->level()->get_id());
        if (isset($duration['count'],$duration['unit']) && $time && $duration['count']) {
            $time = strtotime('+' . $duration['count'] . ' ' . $duration['unit'] . ' 23:59', $time);
            $this->update_meta(self::KEY_EXPIRY, $time);
            return $time;
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function update_expiry($expiry)
    {
        $this->update_meta(self::KEY_EXPIRY, (int) $expiry);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function reset_expiry()
    {
        $duration = RUA_App::instance()->level_manager->metadata()->get('duration')->get_data($this->get_level_id());
        if (isset($duration['count'],$duration['unit']) && $duration['count']) {
            $time = strtotime('+' . $duration['count'] . ' ' . $duration['unit'] . ' 23:59', time());
            $this->update_expiry($time);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function is_active()
    {
        return $this->get_status() === self::STATUS_ACTIVE;
    }

    /**
     * @deprecated
     * @return bool
     */
    public function can_add()
    {
        return true;
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
        return $this->user()->get_attribute(RUA_App::META_PREFIX . $key . '_' . $this->get_level_id(), $default_value);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    private function update_meta($key, $value)
    {
        $user_id = $this->get_user_id();
        return (bool)update_user_meta($user_id, RUA_App::META_PREFIX . $key . '_' . $this->get_level_id(), $value);
    }
}
