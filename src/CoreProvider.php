<?php
namespace RestrictUserAccess;

use RestrictUserAccess\Hook\HookProviderTrait;
use RestrictUserAccess\Hook\HookService;
use RestrictUserAccess\Module\AdminAccess;
use RestrictUserAccess\Module\AdminBar;
use RestrictUserAccess\Module\ContentMode;
use RestrictUserAccess\Module\RestApiContentProtection;
use RestrictUserAccess\Provider\AbstractProvider;
use RestrictUserAccess\Provider\ProviderInterface;
use RestrictUserAccess\Repository\SettingRepository;
use RestrictUserAccess\Repository\SettingRepositoryInterface;
use RestrictUserAccess\Shortcode\Restrict;
use RestrictUserAccess\Shortcode\ShortcodeService;
use RestrictUserAccess\Shortcode\UserLevels;

/**
 * Class CoreProvider
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class CoreProvider extends AbstractProvider implements
    ProviderInterface
{
    use HookProviderTrait;

    public function register()
    {
        $this->app->singleton(HookService::class);
        $this->app->singleton(ShortcodeService::class);
        $this->app->singleton(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->set(ContentMode::class, null ,[
            SettingRepositoryInterface::class
        ]);
        $this->app->set(RestApiContentProtection::class, null ,[
            SettingRepositoryInterface::class
        ]);
        $this->app->set(AdminAccess::class);
        $this->app->set(AdminBar::class);
    }

    public function boot()
    {
        $this->registerHooks([
            ContentMode::class,
            RestApiContentProtection::class
        ]);
        $this->app->get(ShortcodeService::class)->register(new Restrict());
        $this->app->get(ShortcodeService::class)->register(new UserLevels());
    }
}
