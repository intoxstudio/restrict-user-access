<?php

namespace RestrictUserAccess;

/**
 * Class Autoloader
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class Autoloader
{
    public static function init($path)
    {
        $autoloader = $path . 'vendor/autoload.php';
        if(!is_readable($autoloader)) {
            self::fail();
            return false;
        }

        require $autoloader;
        return true;
    }

    private static function fail()
    {
        add_action(
            'admin_notices',
            function() {
                echo '<div class="notice notice-error">';
                printf('<p><b>%s</b></p>',
                    __( 'Complete your Restrict User Access installation by doing one of the following:', 'restrict-user-access')
                );
                echo '<ul style="list-style: inherit; list-style-position: inside">';
                printf('<li>%s</li>',__('If you cloned via Github, you need to use composer','restrict-user-access'));
                printf('<li><a target="_blank" rel="noopener noreferrer" href="%s">%s</a></li>',
                    'https://wordpress.org/plugins/restrict-user-access/',
                    __('Download and re-install the plugin from wordpress.org','restrict-user-access'),

                );
                echo '</ul>';
                echo '</div>';
            }
        );
    }
}
