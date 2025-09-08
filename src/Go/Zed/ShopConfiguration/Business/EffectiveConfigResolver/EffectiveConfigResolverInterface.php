<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver;

use Generated\Shared\Transfer\ShopConfigurationCollectionTransfer;

interface EffectiveConfigResolverInterface
{
    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    public function resolveEffectiveConfiguration(string $store, ?string $locale = null): ShopConfigurationCollectionTransfer;

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return array<string, mixed> Key-value map of effective configuration
     */
    public function resolveEffectiveConfigurationMap(string $store, ?string $locale = null): array;

    /**
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    public function resolveDefaultConfiguration(): ShopConfigurationCollectionTransfer;
}
