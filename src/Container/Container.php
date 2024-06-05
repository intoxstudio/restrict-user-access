<?php
namespace RestrictUserAccess\Container;

/**
 * Class Container
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class Container implements ContainerInterface
{
    protected $bindings = [];

    protected $singletons = [];

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new \Exception("Service '$id' not found in the container.");
        }

        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        list($class, $arguments, $singleton) = $this->bindings[$id];

        if ($class instanceof \Closure) {
            $concrete = $class($this);
        } else {
            $argument_instances = [];
            foreach ($arguments as $argument) {
                $argument_instances[] = $this->get($argument);
            }
            $concrete = new $class(...$argument_instances);
        }

        if ($singleton) {
            $this->singletons[$id] = $concrete;
        }

        return $concrete;
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        return isset($this->bindings[$id]);
    }

    /**
     * @inheritDoc
     */
    public function set($id, $concrete = null, $arguments = [], $shared = false)
    {
        if ($concrete === null) {
            $concrete = $id;
        }

        $this->bindings[$id] = [$concrete, $arguments, $shared];
    }

    /**
     * @inheritDoc
     */
    public function singleton($id, $concrete = null, $arguments = [])
    {
        $this->set($id, $concrete, $arguments, true);
    }
}
