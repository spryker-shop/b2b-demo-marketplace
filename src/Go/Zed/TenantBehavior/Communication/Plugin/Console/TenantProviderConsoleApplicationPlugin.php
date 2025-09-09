<?php

namespace Go\Zed\TenantBehavior\Communication\Plugin\Console;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;

class TenantProviderConsoleApplicationPlugin implements ApplicationPluginInterface
{
    /**
     * @var string
     */
    public const SERVICE_TENANT_REFERENCE = 'SERVICE_TENANT_REFERENCE';
    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::SERVICE_TENANT_REFERENCE, function (ContainerInterface $container) {
            return getenv('SPRYKER_TENANT_IDENTIFIER') ?: null;
        });

        return $container;
    }
}
