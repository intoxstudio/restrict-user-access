<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

class RUA_Collection implements IteratorAggregate, Countable
{
    /**
     * @var array
     */
    private $items;

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    /**
     * @since 2.1
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
     * @since 2.1
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
     * @since 2.1
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
     * @since 2.1
     *
     * @return mixed|null
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * @since 2.1
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * @since 2.1
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
     * @since 2.1
     *
     * @return  array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * @since 2.1
     * @param callable $callback
     *
     * @return static
     */
    public function filter($callback)
    {
        if (!is_callable($callback)) {
            return $this;
        }

        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return count($this->items);
    }

    /**
     * @since 2.2
     *
     * @return bool
     */
    public function is_empty()
    {
        return empty($this->items);
    }

    /**
     * @inheritDoc
     * @ignore
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}
