<?php

namespace RestrictUserAccess\Shortcode;

/**
 * Class ShortcodeService
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class ShortcodeService
{
    /**
     * @param ShortcodeInterface $shortcode
     * @return void
     */
    public function register(ShortcodeInterface $shortcode)
    {
        foreach ($shortcode->get_names() as $name) {
            add_shortcode($name, [$shortcode, 'get_callback']);
        }
    }
}
