<?php

namespace Go\Zed\TenantOnboarding\Communication\Plugin\MerchantPortalApplication;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 */
class TenantProviderMerchantPortalApplicationPlugin extends AbstractPlugin implements ApplicationPluginInterface
{
    /**
     * @var string
     */
    public const SERVICE_TENANT_REFERENCE = 'SERVICE_TENANT_REFERENCE';

    /**
     * @uses \Spryker\Zed\Http\Communication\Plugin\Application\HttpApplicationPlugin::SERVICE_REQUEST_STACK
     *
     * @var string
     */
    protected const SERVICE_REQUEST_STACK = 'request_stack';
    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::SERVICE_TENANT_REFERENCE, function (ContainerInterface $container) {
            $request = $this->getRequest($container);
            $tenantCollectionTransfer = $this->getFacade()->getTenants(
                (new \Generated\Shared\Transfer\TenantCriteriaTransfer())
                    ->setMerchantPortalHost($request->getHost())
            );

            if ($tenantCollectionTransfer->getTenants()->count() === 1) {
                return $tenantCollectionTransfer->getTenants()->offsetGet(0)->getIdentifier();
            }

            return null;
        });

        return $container;
    }

    protected function getRequest(ContainerInterface $container): Request
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
