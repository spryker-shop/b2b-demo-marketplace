<?php

namespace Pyz\Client\TenantBehavior;

use Spryker\Client\Kernel\Container;

class TenantBehaviorDependencyProvider extends \Spryker\Client\Kernel\AbstractDependencyProvider
{
    /**
     * @var string
     */
    public const SERVICE_TENANT_ID = 'SERVICE_TENANT_ID';

    public function provideServiceLayerDependencies(Container $container)
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addTenantService($container);

        return $container;
    }
    protected function addTenantService(Container $container): Container
    {
        $container->set(static::SERVICE_TENANT_ID, function (Container $container) {
            return $container->getApplicationService(static::SERVICE_TENANT_ID);
        });

        return $container;
    }
}
