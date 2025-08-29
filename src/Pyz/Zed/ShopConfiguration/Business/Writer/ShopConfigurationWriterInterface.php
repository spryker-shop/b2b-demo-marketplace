<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business\Writer;

use Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer;

interface ShopConfigurationWriterInterface
{
    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer $saveRequestTransfer
     *
     * @return void
     */
    public function saveConfiguration(ShopConfigurationSaveRequestTransfer $saveRequestTransfer): void;

    /**
     * @param string $store
     * @param string|null $locale
     * @param array<string, mixed> $values
     *
     * @return void
     */
    public function saveConfigurationValues(string $store, ?string $locale, array $values): void;
}
