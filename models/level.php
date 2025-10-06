<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

class RUA_Level implements RUA_Level_Interface
{
    const STATUS_ACTIVE = 'publish';
    const STATUS_INACTIVE = 'draft';
    const STATUS_SCHEDULED = 'future';

    /**
     * @var WP_Post
     */
    private $wp_entity;

    /**
     * @since 2.1
     * @param WP_Post|null $post
     */
    public function __construct(?WP_Post $post = null)
    {
        if (is_null($post)) {
            $post = new WP_Post((object)[]);
        }
        $this->wp_entity = $post;
    }

    /**
     * @inheritDoc
     */
    public function get_id()
    {
        return $this->wp_entity->ID;
    }

    /**
     * @inheritDoc
     */
    public function get_title()
    {
        return $this->wp_entity->post_title;
    }

    /**
     * @inheritDoc
     */
    public function exists()
    {
        return (bool) $this->wp_entity->ID;
    }

    /**
     * @inheritDoc
     */
    public function get_status()
    {
        return $this->wp_entity->post_status;
    }

    /**
     * @inheritDoc
     */
    public function is_active()
    {
        return $this->get_status() === self::STATUS_ACTIVE;
    }
}
