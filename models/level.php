<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

class RUA_Level implements RUA_Level_Interface
{
    /**
     * @var WP_Post
     */
    private $wp_post;

    /**
     * @since 2.1
     * @param WP_Post $post
     */
    public function __construct(WP_Post $post = null)
    {
        if (is_null($post)) {
            $post = new WP_Post((object)[]);
        }
        $this->wp_post = $post;
    }

    /**
     * @inheritDoc
     */
    public function get_id()
    {
        return $this->wp_post->ID;
    }

    /**
     * @inheritDoc
     */
    public function get_title()
    {
        return $this->wp_post->post_title;
    }

    /**
     * @inheritDoc
     */
    public function exists()
    {
        return (bool) $this->wp_post->ID;
    }
}
