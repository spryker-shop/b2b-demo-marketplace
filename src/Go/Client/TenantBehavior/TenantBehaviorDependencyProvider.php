<?php

namespace Go\Client\TenantBehavior;

use Spryker\Client\Kernel\Container;

class TenantBehaviorDependencyProvider extends \Spryker\Client\Kernel\AbstractDependencyProvider
{
    /**
     * @var string
     */
    public const SERVICE_TENANT_REFERENCE = 'SERVICE_TENANT_REFERENCE';

    public function provideServiceLayerDependencies(Container $container)
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addTenantService($container);

        return $container;
    }
    protected function addTenantService(Container $container): Container
    {
        $container->set(static::SERVICE_TENANT_REFERENCE, function (Container $container) {
            return $container->getApplicationService(static::SERVICE_TENANT_REFERENCE);
        });

        return $container;
    }
}
