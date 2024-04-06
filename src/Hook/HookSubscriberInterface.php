<?php
namespace RestrictUserAccess\Hook;

/**
 * Interface HookSubscriberInterface
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
interface HookSubscriberInterface
{
    /**
     * @param HookService $service
     * @return void|array
     */
    public function subscribe(HookService $service);
}
