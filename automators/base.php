<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

abstract class RUA_Member_Automator
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var array
     */
    protected $level_data = [];

    /**
     * @param string $name
     * @param string $title
     */
    public function __construct($name, $title)
    {
        $this->name = $name;
        $this->title = $title;
        $this->add_callback();
    }

    /**
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function get_level_data()
    {
        return $this->level_data;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @param int $level_id
     * @param mixed $value
     * @return void
     */
    public function queue($level_id, $value)
    {
        if(!isset($this->level_data[$level_id])) {
            $this->level_data[$level_id] = [];
        }
        $this->level_data[$level_id][] = $value;
    }

    /**
     * @return bool
     */
    public function can_enable()
    {
        return true;
    }
    
    /**
     * @param mixed $selected_value
     * @return array
     */
    abstract public function get_content($selected_value = null);

    /**
     * @return void
     */
    abstract public function add_callback();

    /**
     * @return string
     */
    abstract public function get_description();
}