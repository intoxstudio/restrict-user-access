<?php
namespace RestrictUserAccess\Support;

/**
 * Trait InstanceTrait
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
trait InstanceTrait
{
    protected static $instance;

    /**
     * @return static
     */
    public static function instance()
    {
        if (!(static::$instance instanceof static)) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}
