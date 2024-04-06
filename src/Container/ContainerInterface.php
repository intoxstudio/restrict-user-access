<?php
namespace RestrictUserAccess\Container;

/**
 * Interface ContainerInterface
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
interface ContainerInterface
{
    /**
     * @param string $id
     * @return mixed
     */
    public function get($id);

    /**
     * @param string $id
     * @return bool
     */
    public function has($id);

    /**
     * @param string $id
     * @param string|callable|null $concrete
     * @param array $arguments
     * @param bool $shared
     * @return void
     */
    public function set($id, $concrete = null, $arguments = [], $shared = false);

    /**
     * @param string $id
     * @param string|callable|null $concrete
     * @param array $arguments
     * @return void
     */
    public function singleton($id, $concrete = null, $arguments = []);
}
