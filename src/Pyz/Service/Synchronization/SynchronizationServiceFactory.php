<?php

namespace Pyz\Service\Synchronization;

use Pyz\Client\TenantBehavior\TenantBehaviorClient;
use Pyz\Service\Synchronization\Plugin\DefaultKeyGeneratorPlugin;

class SynchronizationServiceFactory extends \Spryker\Service\Synchronization\SynchronizationServiceFactory
{
    /**
     * @return \Spryker\Service\Synchronization\Plugin\DefaultKeyGeneratorPlugin
     */
    protected function createDefaultKeyGeneratorPlugin()
    {
        return new DefaultKeyGeneratorPlugin(
            $this->createKeyFilter(),
            $this->getConfig(),
            new TenantBehaviorClient(),
        );
    }
}
