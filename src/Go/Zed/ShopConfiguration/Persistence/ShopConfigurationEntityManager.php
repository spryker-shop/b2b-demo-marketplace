<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Persistence;

use Generated\Shared\Transfer\ShopConfigurationValueTransfer;
use Go\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationPersistenceFactory getFactory()
 */
class ShopConfigurationEntityManager extends AbstractEntityManager implements ShopConfigurationEntityManagerInterface
{
    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationValueTransfer $configurationValueTransfer
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationValueTransfer
     */
    public function saveConfigurationValue(ShopConfigurationValueTransfer $configurationValueTransfer): ShopConfigurationValueTransfer
    {
        $entity = $this->getFactory()->createShopConfigurationQuery()
            ->filterByConfigKey($configurationValueTransfer->getConfigKey())
            ->filterByScopeStore($configurationValueTransfer->getScopeStore())
            ->filterByScopeLocale($configurationValueTransfer->getScopeLocale())
            ->findOneOrCreate();

        $entity->setConfigKey($configurationValueTransfer->getConfigKey());
        $entity->setValueJson($configurationValueTransfer->getValueJson());
        $entity->setScopeStore($configurationValueTransfer->getScopeStore());
        $entity->setScopeLocale($configurationValueTransfer->getScopeLocale());
        $entity->save();

        return $configurationValueTransfer;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationValueTransfer> $configurationValueTransfers
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationValueTransfer>
     */
    public function saveConfigurationValues(array $configurationValueTransfers): array
    {
        foreach ($configurationValueTransfers as $configurationValueTransfer) {
            $this->saveConfigurationValue($configurationValueTransfer);
        }

        return $configurationValueTransfers;
    }

    /**
     * @param string $configKey
     * @param string $store
     * @param string|null $locale
     *
     * @return void
     */
    public function deleteConfigurationValue(string $configKey, string $store, ?string $locale = null): void
    {
        $query = $this->getFactory()->createShopConfigurationQuery()
            ->filterByConfigKey($configKey)
            ->filterByScopeStore($store);

        if ($locale !== null) {
            $query->filterByScopeLocale($locale);
        }

        $query->delete();
    }

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return void
     */
    public function deleteConfigurationsByScope(string $store, ?string $locale = null): void
    {
        $query = $this->getFactory()->createShopConfigurationQuery()
            ->filterByScopeStore($store);

        if ($locale !== null) {
            $query->filterByScopeLocale($locale);
        }

        $query->delete();
    }
}
