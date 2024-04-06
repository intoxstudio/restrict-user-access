<?php
namespace RestrictUserAccess\Level;

use RestrictUserAccess\Hook\HookProviderTrait;
use RestrictUserAccess\Level\Repository\LevelRepository;
use RestrictUserAccess\Level\Repository\LevelRepositoryInterface;
use RestrictUserAccess\Provider\AbstractProvider;
use RestrictUserAccess\Provider\ProviderInterface;

/**
 * Class LevelProvider
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class LevelProvider extends AbstractProvider implements
    ProviderInterface
{
    use HookProviderTrait;

    public function register()
    {
        $this->app->set(LevelRepositoryInterface::class, LevelRepository::class);
        $this->app->set(PostType::class);
    }

    public function boot()
    {
        $this->registerHooks([
            PostType::class,
        ]);
    }
}
