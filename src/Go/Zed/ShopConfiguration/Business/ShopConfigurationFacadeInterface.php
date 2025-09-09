<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business;

use Generated\Shared\Transfer\ShopConfigurationCollectionTransfer;
use Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer;

interface ShopConfigurationFacadeInterface
{
    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    public function getEffectiveConfiguration(string $store, ?string $locale = null): ShopConfigurationCollectionTransfer;

    /**
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    public function getDefaultConfiguration(): ShopConfigurationCollectionTransfer;

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer $saveRequestTransfer
     *
     * @return void
     */
    public function saveConfiguration(ShopConfigurationSaveRequestTransfer $saveRequestTransfer): void;

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return void
     */
    public function publishConfiguration(string $store, ?string $locale = null): void;

    /**
     * @param array<string> $stores
     * @param array<string> $locales
     *
     * @return void
     */
    public function publishConfigurationForStoresAndLocales(array $stores, array $locales = []): void;

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer $saveRequestTransfer
     *
     * @return void
     */
    public function saveAndPublishConfiguration(ShopConfigurationSaveRequestTransfer $saveRequestTransfer): void;

    /**
     * @return void
     */
    public function rebuildConfigurationFromFiles(): void;

    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationSectionTransfer> $sections
     *
     * @return array<string> Array of validation error messages
     */
    public function validateConfiguration(array $sections): array;
}
