<?php

namespace Go\Zed\TenantBehavior\Communication\Plugin\Application;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;
use Spryker\Zed\User\Business\UserFacade;

class TenantProviderBackofficeApplicationPlugin implements ApplicationPluginInterface
{
    /**
     * @var string
     */
    public const SERVICE_TENANT_REFERENCE = 'SERVICE_TENANT_REFERENCE';
    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::SERVICE_TENANT_REFERENCE, function (ContainerInterface $container) {
            $userFacade = new UserFacade();

            return $userFacade->hasCurrentUser()
                ? $userFacade->getCurrentUser()->getTenantReference()
                : null;
        });

        return $container;
    }
}
