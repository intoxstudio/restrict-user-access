<?php
namespace RestrictUserAccess\Hook;

/**
 * Trait HookProviderTrait
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
trait HookProviderTrait
{
    /**
     * @param string[] $subscribers
     * @return void
     * @throws \Exception
     */
    public function registerHooks($subscribers)
    {
        if (empty($subscribers)) {
            return;
        }

        $service = $this->app->get(HookService::class);
        foreach ($subscribers as $subscriberName) {
            /** @var HookSubscriberInterface $subscriber */
            $subscriber = $this->app->get($subscriberName);
            $subscriber->subscribe($service);
        }
    }
}
