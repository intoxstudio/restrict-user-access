<?php

namespace RestrictUserAccess\Support;

/**
 * Trait WPDBAwareTrait
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
trait WPDBAwareTrait
{
    /**
     * @return \wpdb
     */
    public function wpdb()
    {
        global $wpdb;
        return $wpdb;
    }
}
