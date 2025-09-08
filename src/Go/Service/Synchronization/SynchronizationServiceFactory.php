<?php

namespace Go\Service\Synchronization;

use Go\Client\TenantBehavior\TenantBehaviorClient;
use Go\Service\Synchronization\Plugin\DefaultKeyGeneratorPlugin;

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
