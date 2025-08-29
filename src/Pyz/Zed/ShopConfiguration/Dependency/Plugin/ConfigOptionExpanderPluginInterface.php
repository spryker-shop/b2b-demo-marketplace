<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Dependency\Plugin;

use Generated\Shared\Transfer\ShopConfigurationOptionTransfer;

interface ConfigOptionExpanderPluginInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationOptionTransfer> $options
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationOptionTransfer>
     */
    public function expand(array $options): array;
}
