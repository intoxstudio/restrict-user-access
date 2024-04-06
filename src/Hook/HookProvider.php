<?php
namespace RestrictUserAccess\Hook;

use RestrictUserAccess\Module\ContentMode;
use RestrictUserAccess\Module\RestApiContentProtection;
use RestrictUserAccess\Provider\AbstractProvider;
use RestrictUserAccess\Provider\ProviderInterface;

/**
 * Class HookProvider
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class HookProvider extends AbstractProvider implements
    ProviderInterface
{
    use HookProviderTrait;

    public function register()
    {
        $this->app->singleton(HookService::class);
        $this->app->set(ContentMode::class);
        $this->app->set(RestApiContentProtection::class);
    }

    public function boot()
    {
        $this->registerHooks([
            ContentMode::class,
            RestApiContentProtection::class
        ]);
    }
}
