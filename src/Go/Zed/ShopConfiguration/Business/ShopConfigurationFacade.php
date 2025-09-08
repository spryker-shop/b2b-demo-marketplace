<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business;

use Generated\Shared\Transfer\ShopConfigurationCollectionTransfer;
use Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer;
use Go\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Go\Zed\ShopConfiguration\Business\ShopConfigurationBusinessFactory getFactory()
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationRepositoryInterface getRepository()
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface getEntityManager()
 */
class ShopConfigurationFacade extends AbstractFacade implements ShopConfigurationFacadeInterface
{
    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    public function getEffectiveConfiguration(string $store, ?string $locale = null): ShopConfigurationCollectionTransfer
    {
        return $this->getFactory()
            ->createEffectiveConfigResolver()
            ->resolveEffectiveConfiguration($store, $locale);
    }

    /**
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    public function getDefaultConfiguration(): ShopConfigurationCollectionTransfer
    {
        return $this->getFactory()
            ->createEffectiveConfigResolver()
            ->resolveDefaultConfiguration();
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer $saveRequestTransfer
     *
     * @return void
     */
    public function saveConfiguration(ShopConfigurationSaveRequestTransfer $saveRequestTransfer): void
    {
        $this->getFactory()
            ->createShopConfigurationWriter()
            ->saveConfiguration($saveRequestTransfer);
    }

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return void
     */
    public function publishConfiguration(string $store, ?string $locale = null): void
    {
        $this->getFactory()
            ->createShopConfigurationPublisher()
            ->publishConfiguration($store, $locale);
    }

    /**
     * @param array<string> $stores
     * @param array<string> $locales
     *
     * @return void
     */
    public function publishConfigurationForStoresAndLocales(array $stores, array $locales = []): void
    {
        $this->getFactory()
            ->createShopConfigurationPublisher()
            ->publishConfigurationForStoresAndLocales($stores, $locales);
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer $saveRequestTransfer
     *
     * @return void
     */
    public function saveAndPublishConfiguration(ShopConfigurationSaveRequestTransfer $saveRequestTransfer): void
    {
        $this->saveConfiguration($saveRequestTransfer);
        $this->publishConfiguration($saveRequestTransfer->getStore(), $saveRequestTransfer->getLocale());
    }

    /**
     * @return void
     */
    public function rebuildConfigurationFromFiles(): void
    {
        // This could clear all database overrides and rebuild from files
        // For now, just trigger re-reading of files
        $this->getFactory()->createConfigFileReader()->readAllConfigurationFiles();
    }

    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationSectionTransfer> $sections
     *
     * @return array<string>
     */
    public function validateConfiguration(array $sections): array
    {
        return $this->getFactory()
            ->createConfigValidator()
            ->validateConfiguration($sections);
    }
}
