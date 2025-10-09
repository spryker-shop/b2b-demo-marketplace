<?php

namespace Go\Glue\TenantBehavior\Plugin\GlueBackendApiApplication;

use Spryker\Glue\Kernel\AbstractPlugin;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TenantApplicationPlugin extends AbstractPlugin implements ApplicationPluginInterface
{
    public const SERVICE_TENANT_REFERENCE = 'SERVICE_TENANT_REFERENCE';

    /**
     * @uses \Spryker\Yves\Http\Plugin\Application\HttpApplicationPlugin::SERVICE_REQUEST_STACK
     *
     * @var string
     */
    public const SERVICE_REQUEST_STACK = 'request_stack';

    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::SERVICE_TENANT_REFERENCE, function (ContainerInterface $container) {
            return $this->resolve($container);
        });

        return $container;
    }

    protected function resolve(ContainerInterface $container): string
    {
        $request = $this->getRequest($container);
        $tenantReference = $request->headers->get('x-tenant');
        if (!$tenantReference) {
            throw new \RuntimeException('Tenant reference not found. Please provide "x-tenant" header.');
        }

        /** @var \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface $tenantOnboardingFacade */
        $tenantOnboardingFacade = \Spryker\Zed\Kernel\Locator::getInstance()
            ->tenantOnboarding()
            ->facade();
        $tenantTransfer = $tenantOnboardingFacade->findTenantByIdentifier($tenantReference);

        if (!$tenantTransfer) {
            throw new \RuntimeException('Provided tenant does not exist. Please provide correct "x-tenant" header.');
        }

        return $tenantTransfer->getIdentifier();
    }

    protected function getRequest(ContainerInterface $container): \Symfony\Component\HttpFoundation\Request
    {
        $requestStack = $container->get(static::SERVICE_REQUEST_STACK);

        if ($requestStack->getCurrentRequest() === null) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::createFromGlobals());
        }

        /** @var \Symfony\Component\HttpFoundation\Request $currentRequest */
        return $requestStack->getCurrentRequest();
    }
}
