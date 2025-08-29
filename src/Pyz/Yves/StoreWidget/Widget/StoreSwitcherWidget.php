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
        $storeNames = $this->getFactory()->getStoreStorageClient()->getStoreNames();
        $urls = [];

        $hostname = $_SERVER['HTTP_HOST'] ?? '';
        $explode = explode('_', $hostname, 2);
        $tenantHost = $explode[1] ?? $hostname;
        foreach ($storeNames as $storeName) {
            $urls[$storeName] = sprintf('%s://%s_%s', $_SERVER['REQUEST_SCHEME'], strtolower($storeName), $tenantHost);
        }

        return $urls;
    }
}
