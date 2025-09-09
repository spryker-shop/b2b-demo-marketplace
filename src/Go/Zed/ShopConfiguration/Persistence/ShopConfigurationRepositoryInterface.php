<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Persistence;

use Generated\Shared\Transfer\ShopConfigurationValueTransfer;

interface ShopConfigurationRepositoryInterface
{
    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationValueTransfer>
     */
    public function findConfigurationValues(string $store, ?string $locale = null): array;

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return array<string, string> Key-value map where key is config key and value is the JSON value
     */
    public function getEffectiveConfigurationMap(string $store, ?string $locale = null): array;

    /**
     * @param string $configKey
     * @param string $store
     * @param string|null $locale
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationValueTransfer|null
     */
    public function findConfigurationValue(string $configKey, string $store, ?string $locale = null): ?ShopConfigurationValueTransfer;

    /**
     * @return array<\Generated\Shared\Transfer\ShopConfigurationValueTransfer>
     */
    public function findAllConfigurationValues(): array;
}
