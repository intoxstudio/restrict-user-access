<?php

namespace RestrictUserAccess\Repository;

/**
 * Interface SettingRepositoryInterface
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
interface SettingRepositoryInterface extends RepositoryInterface
{
    /**
     * @param string $name
     * @param int $default_value
     * @return int
     */
    public function get_int($name, $default_value = 0);

    /**
     * @param string $name
     * @param float $default_value
     * @return float
     */
    public function get_float($name, $default_value = 0.0);

    /**
     * @param string $name
     * @param string $default_value
     * @return string
     */
    public function get_string($name, $default_value = '');

    /**
     * @param string $name
     * @param bool $default_value
     * @return bool
     */
    public function get_bool($name, $default_value = false);
}
