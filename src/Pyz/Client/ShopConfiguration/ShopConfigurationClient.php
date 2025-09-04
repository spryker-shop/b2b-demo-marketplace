<?php

declare(strict_types=1);

namespace Pyz\Client\ShopConfiguration;

use Pyz\Shared\ShopConfiguration\ShopConfigurationKeyBuilder;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Pyz\Client\ShopConfiguration\ShopConfigurationFactory getFactory()
 */
class ShopConfigurationClient extends AbstractClient implements ShopConfigurationClientInterface
{
    /**
     * @param string|null $locale
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(?string $locale = null): array
    {
        $storeTransfer = $this->getFactory()->getStoreClient()->getCurrentStore();
        $storeName = $storeTransfer->getName();

        $keyBuilder = new ShopConfigurationKeyBuilder();
        $key = $keyBuilder->buildStoreLocaleKey($storeName, $locale);

        try {
            $data = $this->getFactory()->getStorageClient()->get($key);

            if ($data && isset($data['configurations'])) {
                return $data['configurations'];
            }
        } catch (\Exception $e) {
            // Log error and return empty array
            error_log(sprintf('Failed to load shop configuration: %s', $e->getMessage()));
        }

        return [];
    }

    /**
     * @param string $configKey
     * @param mixed $default
     * @param string|null $locale
     *
     * @return mixed
     */
    public function get(string $configKey, $default = null, ?string $locale = null)
    {
        $configuration = $this->getConfiguration($locale);

        return $configuration[$configKey] ?? $default;
    }

    /**
     * @param string $configKey
     * @param string|null $locale
     *
     * @return bool
     */
    public function has(string $configKey, ?string $locale = null): bool
    {
        $configuration = $this->getConfiguration($locale);

        return array_key_exists($configKey, $configuration);
    }

    /**
     * @param string $module
     * @param string|null $locale
     *
     * @return array<string, mixed>
     */
    public function getModuleConfiguration(string $module, ?string $locale = null): array
    {
        $configuration = $this->getConfiguration($locale);
        $moduleConfig = [];

        foreach ($configuration as $key => $value) {
            if (strpos($key, $module . '.') === 0) {
                $moduleConfig[$key] = $value;
            }
        }

        return $moduleConfig;
    }

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return bool
     */
    public function isConfigurationAvailable(string $store, ?string $locale = null): bool
    {
        $keyBuilder = new ShopConfigurationKeyBuilder();
        $key = $keyBuilder->buildStoreLocaleKey($store, $locale);

        try {
            $data = $this->getFactory()->getStorageClient()->get($key);
            return $data !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getConfig(string $key): string|int|array|bool|null
    {
        $tenantStorageReader = new Reader\StoreConfigStorageReader(
            \Spryker\Client\Kernel\Locator::getInstance()->synchronization()->service(),
            \Spryker\Client\Kernel\Locator::getInstance()->storage()->client(),
            \Spryker\Client\Kernel\Locator::getInstance()->tenantBehavior()->client(),
            \Spryker\Client\Kernel\Locator::getInstance()->store()->client(),
        );

        return $tenantStorageReader->getConfig($key);
    }

    public function resolveDomainByHost(string $host): ?array
    {
        $tenantStorageReader = new Reader\StoreDomainStorageReader(
            \Spryker\Client\Kernel\Locator::getInstance()->synchronization()->service(),
            \Spryker\Client\Kernel\Locator::getInstance()->storage()->client(),
            \Spryker\Client\Kernel\Locator::getInstance()->tenantBehavior()->client(),
            \Spryker\Client\Kernel\Locator::getInstance()->store()->client(),
        );

        return $tenantStorageReader->getConfig($host);
    }
}
