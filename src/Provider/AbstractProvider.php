<?php
namespace RestrictUserAccess\Provider;

use RestrictUserAccess\Application;

/**
 * Class AbstractProvider
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
abstract class AbstractProvider implements ProviderInterface
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function boot()
    {
    }
}
