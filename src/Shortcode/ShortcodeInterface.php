<?php

namespace RestrictUserAccess\Shortcode;

/**
 * Interface ShortcodeInterface
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
interface ShortcodeInterface
{
    public function get_names();

    public function get_callback($atts, $content = null);
}
