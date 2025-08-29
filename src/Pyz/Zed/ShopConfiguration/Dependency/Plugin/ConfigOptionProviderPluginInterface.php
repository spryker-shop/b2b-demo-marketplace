<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Dependency\Plugin;

use Generated\Shared\Transfer\ShopConfigurationOptionTransfer;

interface ConfigOptionProviderPluginInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\ShopConfigurationOptionTransfer>
     */
    public function provideOptions(): array;
}
