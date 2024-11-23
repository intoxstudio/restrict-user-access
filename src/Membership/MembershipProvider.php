<?php

namespace RestrictUserAccess\Membership;

use RestrictUserAccess\Hook\HookProviderTrait;
use RestrictUserAccess\Membership\Automator\AutomatorService;
use RestrictUserAccess\Provider\AbstractProvider;
use RestrictUserAccess\Provider\ProviderInterface;

/**
 * Class MembershipProvider
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class MembershipProvider extends AbstractProvider implements
    ProviderInterface
{
    use HookProviderTrait;

    public function register()
    {
        $this->app->singleton(AutomatorService::class);
        $this->app->set(QueryFilters::class);
    }

    public function boot()
    {
        $this->registerHooks([
            AutomatorService::class,
            QueryFilters::class,
        ]);
    }
}
