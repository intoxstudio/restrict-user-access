<?php

namespace RestrictUserAccess\Repository;

/**
 * Class SettingRepository
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class SettingRepository implements SettingRepositoryInterface
{
    public function get_int($name, $default_value = 0)
    {
        return (int) $this->get_option($name, $default_value);
    }

    public function get_float($name, $default_value = 0.0)
    {
        return (float) $this->get_option($name, $default_value);
    }

    public function get_string($name, $default_value = '')
    {
        return (string) $this->get_option($name, $default_value);
    }

    public function get_bool($name, $default_value = false)
    {
        return (bool) $this->get_option($name, $default_value);
    }

    private function get_option($name, $default_value)
    {
        return \get_option($name, $default_value);
    }
}
