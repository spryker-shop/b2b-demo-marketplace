<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\Publisher;

use Generated\Shared\Transfer\ShopConfigurationStorageTransfer;
use Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolverInterface;
use Pyz\Shared\ShopConfiguration\ShopConfigurationKeyBuilder;
use Go\Zed\ShopConfiguration\Business\Publisher\ShopConfigurationPublisherInterface;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;

class ShopConfigurationPublisher implements ShopConfigurationPublisherInterface
{
    /**
     * @var \Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolverInterface
     */
    protected EffectiveConfigResolverInterface $effectiveConfigResolver;

    /**
     * @var mixed
     */
    protected $redisClient;

    /**
     * @var \Spryker\Service\Synchronization\SynchronizationServiceInterface
     */
    protected SynchronizationServiceInterface $synchronizationService;

    /**
     * @var \Spryker\Service\UtilEncoding\UtilEncodingServiceInterface
     */
    protected UtilEncodingServiceInterface $utilEncodingService;

    /**
     * @param \Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolverInterface $effectiveConfigResolver
     * @param mixed $redisClient
     * @param \Spryker\Service\Synchronization\SynchronizationServiceInterface $synchronizationService
     * @param \Spryker\Service\UtilEncoding\UtilEncodingServiceInterface $utilEncodingService
     */
    public function __construct(
        EffectiveConfigResolverInterface $effectiveConfigResolver,
        $redisClient,
        SynchronizationServiceInterface $synchronizationService,
        UtilEncodingServiceInterface $utilEncodingService
    ) {
        $this->effectiveConfigResolver = $effectiveConfigResolver;
        $this->redisClient = $redisClient;
        $this->synchronizationService = $synchronizationService;
        $this->utilEncodingService = $utilEncodingService;
    }

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return void
     */
    public function publishConfiguration(string $store, ?string $locale = null): void
    {
        $effectiveConfigMap = $this->effectiveConfigResolver->resolveEffectiveConfigurationMap($store, $locale);

        $storageTransfer = (new ShopConfigurationStorageTransfer())
            ->setStore($store)
            ->setLocale($locale)
            ->setConfigurations($effectiveConfigMap)
            ->setTimestamp(time());

        $key = ShopConfigurationKeyBuilder::buildKey($store, $locale);
        $data = $this->utilEncodingService->encodeJson($storageTransfer->toArray());

        try {
            // Try to use Redis client if available
            if (method_exists($this->redisClient, 'set')) {
                $this->redisClient->set($key, $data);
            } else {
                // Fallback: log the data that would be published
                error_log(sprintf('Would publish to Redis key %s: %s', $key, $data));
            }
        } catch (\Exception $e) {
            error_log(sprintf('Failed to publish configuration to Redis: %s', $e->getMessage()));
        }
    }

    /**
     * @param array<string> $stores
     * @param array<string> $locales
     *
     * @return void
     */
    public function publishConfigurationForStoresAndLocales(array $stores, array $locales = []): void
    {
        foreach ($stores as $store) {
            // Publish store-level configuration
            $this->publishConfiguration($store);

            // Publish store+locale configurations
            foreach ($locales as $locale) {
                $this->publishConfiguration($store, $locale);
            }
        }
    }
}
