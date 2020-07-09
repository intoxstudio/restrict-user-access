<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2020 by Joachim Jensen
 */

class RUA_Collection implements IteratorAggregate, Countable
{
    /**
     * @var array
     */
    private $items;

    public function __construct($items = array())
    {
        $this->items = $items;
    }

    /**
     * @param mixed $value
     *
     * @return self
     */
    public function add($value)
    {
        $this->items[] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    public function put($key, $value)
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     *
     * @return self
     */
    public function remove($key)
    {
        unset($this->items[$key]);
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * @param string $key
     * @param mixed|null $default_value
     *
     * @return mixed|null
     */
    public function get($key, $default_value = null)
    {
        return $this->has($key) ? $this->items[$key] : $default_value;
    }

    /**
     * Get all objects in manager
     *
     * @since 1.0
     * @return  array
     */
    public function all()
    {
        return $this->items;
    }

    public function filter($callback)
    {
        if (!is_callable($callback)) {
            return $this;
        }

        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @since 1.0
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}
