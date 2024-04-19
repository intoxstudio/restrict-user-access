<?php
namespace RestrictUserAccess;

use RestrictUserAccess\Container\Container;
use RestrictUserAccess\Container\ContainerInterface;
use RestrictUserAccess\Level\LevelProvider;
use RestrictUserAccess\Membership\MembershipProvider;
use RestrictUserAccess\Provider\ProviderInterface;
use RestrictUserAccess\Support\InstanceTrait;

/**
 * Class Application
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class Application extends Container implements ContainerInterface
{
    use InstanceTrait;

    protected $providers = [
        CoreProvider::class,
        LevelProvider::class,
        MembershipProvider::class
    ];

    public function boot()
    {
        $this->registerProviders();
    }

    protected function registerProviders()
    {
        $registered = [];
        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass($this);
            if ($provider instanceof ProviderInterface) {
                $provider->register();
                $registered[] = $provider;
            }
        }
        foreach ($registered as $provider) {
            $provider->boot();
        }
    }
}
