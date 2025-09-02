<?php

namespace Pyz\Yves\StoreWidget\Widget;

class StoreSwitcherWidget extends \SprykerShop\Yves\StoreWidget\Widget\StoreSwitcherWidget
{
    /**
     * @param string $route
     * @param array<string, mixed> $parameters
     *
     * @return array<string, string>
     */
    protected function generateStoreUrls(string $route, array $parameters): array
    {
        /** @var \Pyz\Client\ShopConfiguration\ShopConfigurationClient $shopConfigurationClient */
        $shopConfigurationClient = \Spryker\Client\Kernel\Locator::getInstance()
            ->shopConfiguration()
            ->client();
        /** @var \Pyz\Client\TenantBehavior\TenantBehaviorClientInterface $tenantBehaviorClient */
        $tenantBehaviorClient = \Spryker\Client\Kernel\Locator::getInstance()
            ->tenantBehavior()
            ->client();
        $tenantId = $tenantBehaviorClient->getCurrentTenantId();

        return array_map(function ($tenantHost) {
            return sprintf('%s://%s', $_SERVER['REQUEST_SCHEME'], $tenantHost);
        }, $shopConfigurationClient->resolveDomainByHost($tenantId));
    }
}
