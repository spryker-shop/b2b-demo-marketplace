<?php

namespace Go\Zed\TenantBehavior\Communication\Plugin\Application;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;

class TenantProviderBackendGatewayApplicationPlugin implements ApplicationPluginInterface
{
    public const SERVICE_TENANT_REFERENCE = 'SERVICE_TENANT_REFERENCE';

    protected const SERVICE_ZED_REQUEST = 'service_zed_request';
    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::SERVICE_TENANT_REFERENCE, function (ContainerInterface $container) {
            /** @var \Spryker\Shared\ZedRequest\Client\AbstractRequest $zedRequest */
            $zedRequest = $container->get(static::SERVICE_ZED_REQUEST);

            /** @var \Generated\Shared\Transfer\TenantTransfer $tenantTransfer */
            $tenantTransfer = $zedRequest->getMetaTransfer('tenant');

            return $tenantTransfer->getIdentifierOrFail();
        });

        return $container;
    }
}
