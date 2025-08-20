<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Persistence;

use Generated\Shared\Transfer\ShopConfigurationValueTransfer;

interface ShopConfigurationEntityManagerInterface
{
    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationValueTransfer $configurationValueTransfer
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationValueTransfer
     */
    public function saveConfigurationValue(ShopConfigurationValueTransfer $configurationValueTransfer): ShopConfigurationValueTransfer;

    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationValueTransfer> $configurationValueTransfers
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationValueTransfer>
     */
    public function saveConfigurationValues(array $configurationValueTransfers): array;

    /**
     * @param string $configKey
     * @param string $store
     * @param string|null $locale
     *
     * @return void
     */
    public function deleteConfigurationValue(string $configKey, string $store, ?string $locale = null): void;

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return void
     */
    public function deleteConfigurationsByScope(string $store, ?string $locale = null): void;
}
