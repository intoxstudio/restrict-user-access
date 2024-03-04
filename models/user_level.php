<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

class RUA_User_Level implements RUA_User_Level_Interface
{
    const ENTITY_TYPE = 'rua_member';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';

    /** @var RUA_User_Interface */
    private $user;
    /** @var RUA_Level_Interface  */
    private $level;
    /** @var WP_Comment */
    private $wp_entity;

    /**
     * @param WP_Comment $wp_entity
     */
    public function __construct(WP_Comment $wp_entity)
    {
        $this->wp_entity = $wp_entity;
    }

    public function refresh()
    {
        if ($this->is_active() && $this->is_expired()) {
            $this->update_status(self::STATUS_EXPIRED);
        }
    }

    /**
     * @inheritDoc
     */
    public function get_user_id()
    {
        return (int)$this->wp_entity->user_id;
    }

    /**
     * @inheritDoc
     */
    public function user()
    {
        if (!($this->user instanceof RUA_User_Interface)) {
            $this->user = rua_get_user($this->get_user_id());
        }
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function get_level_id()
    {
        return (int)$this->wp_entity->comment_post_ID;
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
        if (!($this->level instanceof RUA_Level_Interface)) {
            $this->level = rua_get_level($this->get_level_id());
        }
        return $this->level;
    }

    /**
     * @inheritDoc
     */
    public function get_status()
    {
        return $this->wp_entity->comment_approved;
    }

    /**
     * @inheritDoc
     */
    public function update_status($status)
    {
        if ($this->get_status() === $status) {
            return $this;
        }

        global $wpdb;

        $updated = $wpdb->update($wpdb->comments, ['comment_approved' => $status], ['comment_ID' => $this->wp_entity->comment_ID]);
        if (!$updated) {
            return $this;
        }

        clean_comment_cache($this->wp_entity->comment_ID);
        wp_update_comment_count($this->get_level_id());
        $this->wp_entity->comment_approved = $status;

        return $this;
    }

    /**
      * @inheritDoc
      */
    public function get_start()
    {
        return strtotime($this->wp_entity->comment_date_gmt);
    }

    /**
     * @inheritDoc
     */
    public function update_start($start)
    {
        if ($this->get_start() === $start) {
            return $this;
        }

        $date = date_i18n('Y-m-d H:i:s', $start);
        $updated = wp_update_comment([
            'comment_ID'   => $this->wp_entity->comment_ID,
            'comment_date' => $date
        ]);
        if ($updated) {
            $this->wp_entity->comment_date = $date;
            $this->wp_entity->comment_date_gmt = get_gmt_from_date($date);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get_expiry()
    {
        $unixtime = get_comment_meta($this->wp_entity->comment_ID, '_ca_member_expiry', true);
        if (!empty($unixtime)) {
            return (int) $unixtime;
        }

        //fallback to calc
        $time = $this->get_start();
        $duration = RUA_App::instance()->level_manager->metadata()->get('duration')->get_data($this->get_level_id());
        if (isset($duration['count'],$duration['unit']) && $time && $duration['count']) {
            $time = strtotime('+' . $duration['count'] . ' ' . $duration['unit'] . ' 23:59', $time);
            $this->update_expiry($time);
            return $time;
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function update_expiry($expiry)
    {
        update_comment_meta($this->wp_entity->comment_ID, '_ca_member_expiry', $expiry);
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
    public function delete()
    {
        $deleted = wp_delete_comment($this->wp_entity, true);
        wp_update_comment_count($this->wp_entity->comment_post_ID);
        return $deleted;
    }

    /**
     * @return bool
     */
    private function is_expired()
    {
        $time_expire = $this->get_expiry();
        return $time_expire && time() > $time_expire;
    }
}
