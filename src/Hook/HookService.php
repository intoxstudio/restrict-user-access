<?php
namespace RestrictUserAccess\Hook;

/**
 * Class HookService
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class HookService
{
    /**
     * @param string $name
     * @param callable $callback
     * @param int $priority
     * @param int $args
     * @return void
     */
    public function add_action($name, $callback, $priority = 10, $args = 1)
    {
        add_action($name, $callback, $priority, $args);
    }

    /**
     * @param string $name
     * @param callable $callback
     * @param int $priority
     * @param int $args
     * @return void
     */
    public function add_filter($name, $callback, $priority = 10, $args = 1)
    {
        add_filter($name, $callback, $priority, $args);
    }
}
