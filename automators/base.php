<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

abstract class RUA_Member_Automator extends \RestrictUserAccess\Membership\Automator\AbstractAutomator
{
    const TYPE_TRIGGER = 'trigger';
    const TYPE_TRAIT = 'trait';

    /**
     * @param string $title
     */
    public function __construct($title)
    {
        _deprecated_class(__CLASS__, '2.7', 'AbstractAutomator');
        //backwards compat
        $args = func_get_args();
        if (count($args) == 2) {
            $this->name = $args[0];
            $title = $args[1];
        }

        parent::__construct($title);
    }

    /**
     * @param mixed $selected_value
     * @return string|null
     */
    public function get_content_title($selected_value)
    {
        //backwards compatibility
        if (!method_exists($this, 'get_content')) {
            throw new Exception('Automator must implement get_content_title()');
        }
        return $this->get_content($selected_value);
    }

    /**
     * @param string|null $term
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function search_content($term, $page, $limit)
    {
        //backwards compatibility
        if (!method_exists($this, 'get_content')) {
            throw new Exception('Automator must implement get_content()');
        }
        return $this->get_content();
    }
}
