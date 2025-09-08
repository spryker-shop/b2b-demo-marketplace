<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Persistence;

use Generated\Shared\Transfer\ShopConfigurationValueTransfer;
use Orm\Zed\ShopConfiguration\Persistence\SpyShopConfigurationQuery;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationPersistenceFactory getFactory()
 */
class ShopConfigurationRepository extends AbstractRepository implements ShopConfigurationRepositoryInterface
{
    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationValueTransfer>
     */
    public function findConfigurationValues(string $store, ?string $locale = null): array
    {
        $query = $this->getFactory()->createShopConfigurationQuery()
            ->filterByScopeStore($store);

        if ($locale !== null) {
            $query->filterByScopeLocale($locale);
        }

        $entities = $query->find();
        $transfers = [];

        foreach ($entities as $entity) {
            $transfer = new ShopConfigurationValueTransfer();
            $transfer->setConfigKey($entity->getConfigKey());
            $transfer->setValueJson($entity->getValueJson());
            $transfer->setScopeStore($entity->getScopeStore());
            $transfer->setScopeLocale($entity->getScopeLocale());

            $transfers[] = $transfer;
        }

        return $transfers;
    }

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return array<string, string> Key-value map where key is config key and value is the JSON value
     */
    public function getEffectiveConfigurationMap(string $store, ?string $locale = null): array
    {
        $query = $this->getFactory()->createShopConfigurationQuery()
            ->filterByScopeStore($store);

        if ($locale !== null) {
            $query->_and()
                ->condition('localeSpecific', 'spy_shop_configuration.scope_locale = ?', $locale)
                ->condition('localeNull', 'spy_shop_configuration.scope_locale IS NULL')
                ->where(['localeSpecific', 'localeNull'], 'OR');
        }

        $query->orderBy('spy_shop_configuration.scope_locale', 'ASC'); // NULLs first, then specific locales

        $map = [];
        $entities = $query->find();

        foreach ($entities as $entity) {
            $key = $entity->getConfigKey();

            // Prefer locale-specific values over store-level defaults
            if (!array_key_exists($key, $map) ||
                ($locale !== null && $entity->getScopeLocale() === $locale)) {
                $map[$key] = $entity->getValueJson();
            }
        }

        return $map;
    }

    /**
     * @param string $configKey
     * @param string $store
     * @param string|null $locale
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationValueTransfer|null
     */
    public function findConfigurationValue(string $configKey, string $store, ?string $locale = null): ?ShopConfigurationValueTransfer
    {
        $query = $this->getFactory()->createShopConfigurationQuery()
            ->filterByConfigKey($configKey)
            ->filterByScopeStore($store);

        if ($locale !== null) {
            $query->filterByScopeLocale($locale);
        }

        $entity = $query->findOne();

        if ($entity === null) {
            return null;
        }

        $transfer = new ShopConfigurationValueTransfer();
        $transfer->setConfigKey($entity->getConfigKey());
        $transfer->setValueJson($entity->getValueJson());
        $transfer->setScopeStore($entity->getScopeStore());
        $transfer->setScopeLocale($entity->getScopeLocale());

        return $transfer;
    }

    /**
     * @return array<\Generated\Shared\Transfer\ShopConfigurationValueTransfer>
     */
    public function findAllConfigurationValues(): array
    {
        $entities = $this->getFactory()->createShopConfigurationQuery()->find();
        $transfers = [];

        foreach ($entities as $entity) {
            $transfer = new ShopConfigurationValueTransfer();
            $transfer->setConfigKey($entity->getConfigKey());
            $transfer->setValueJson($entity->getValueJson());
            $transfer->setScopeStore($entity->getScopeStore());
            $transfer->setScopeLocale($entity->getScopeLocale());

            $transfers[] = $transfer;
        }

        return $transfers;
    }

    /**
     * @inheritDoc
     */
    public function getValuesMapForStoreLocale(string $store, ?string $locale = null): array
    {
        // Prefer locale-specific values, then fallback to store-level values
        $query = $this->getFactory()->createShopConfigurationQuery()
            ->filterByScopeStore($store)
            ->_and()
            ->condition('localeSpecific', 'spy_shop_configuration.scope_locale = ?', $locale)
            ->condition('localeNull', 'spy_shop_configuration.scope_locale IS NULL')
            ->where(['localeSpecific', 'localeNull'], 'OR')
            ->orderBy('spy_shop_configuration.scope_locale');

        /** @var array<string, mixed> $map */
        $map = [];

        foreach ($query->find() as $entity) {
            $key = $entity->getConfigKey();

            // Only set if not already set by locale-specific record
            // Ensure we prefer locale-specific: if requested locale is not null and entity has that locale, override.
            if (!array_key_exists($key, $map) || ($locale !== null && $entity->getScopeLocale() === $locale)) {
                $value = $entity->getValueJson();
                $decoded = json_decode($value, true);
                $map[$key] = $decoded === null && $value !== 'null' ? $value : $decoded;
            }
        }

        return $map;
    }
}
