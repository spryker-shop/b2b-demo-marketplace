<?php

namespace Go\Yves\ShopConfiguration\Plugin\ShopApplication;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Yves\Kernel\AbstractPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TenantStoreResolverApplicationPlugin extends AbstractPlugin implements \Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface
{
    public const SERVICE_TENANT_REFERENCE = 'SERVICE_TENANT_REFERENCE';
    protected const STORE = 'store';

    /**
     * @uses \Spryker\Yves\Http\Plugin\Application\HttpApplicationPlugin::SERVICE_REQUEST_STACK
     *
     * @var string
     */
    public const SERVICE_REQUEST_STACK = 'request_stack';

    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::STORE, function (ContainerInterface $container) {
            return $this->resolve($container, 'store');
        });
        $container->set(static::SERVICE_TENANT_REFERENCE, function (ContainerInterface $container) {
            return $this->resolve($container, 'tenant');
        });

        return $container;
    }

    protected function resolve(ContainerInterface $container, string $parameter): string
    {
        /** @var \Go\Client\ShopConfiguration\ShopConfigurationClient $shopConfigurationClient */
        $shopConfigurationClient = \Spryker\Client\Kernel\Locator::getInstance()
            ->shopConfiguration()
            ->client();
        $request = $this->getRequest($container);
        $host = $request->getHttpHost();
        $storeDomainData = $shopConfigurationClient->resolveDomainByHost($host);
        if ($storeDomainData) {
            if (isset($storeDomainData[$parameter])) {
                return $storeDomainData[$parameter];
            }
            echo 'Parameter not found: ' . $parameter;
            die;
        }

        /** @var \Go\Client\TenantOnboarding\TenantOnboardingClientInterface $tenantOnboardingClient */
        $tenantOnboardingClient = \Spryker\Client\Kernel\Locator::getInstance()
            ->tenantOnboarding()
            ->client();
        $tenantTransfer = $tenantOnboardingClient->findTenantByID($host);

        if ($tenantTransfer) {
            if ($parameter === 'tenant') {
                return (string)$tenantTransfer->getIdentifier();
            }
            if ($parameter === 'store') {
                $storeDomainData = $shopConfigurationClient->resolveDomainByHost($tenantTransfer->getIdentifier());

                if ($storeDomainData) {
                    $defaultDomain = reset($storeDomainData);
                    header(
                        sprintf('Location: %s://%s', $request->getScheme(), $defaultDomain),
                    );
                    die;
                }
            }
        }

        echo 'Domain not found';
        die;
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
