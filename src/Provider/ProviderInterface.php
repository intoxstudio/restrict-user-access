<?php
namespace RestrictUserAccess\Provider;

/**
 * Interface ProviderInterface
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
interface ProviderInterface
{
    /**
     * @return void
     */
    public function register();

    /**
     * @return void
     */
    public function boot();
}
