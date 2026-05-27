<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\ProductStorage;

use Demo\Client\ProductGrossMargin\Plugin\ProductStorage\ProductViewGrossMarginExpanderPlugin;
use Pyz\Client\ProductStorage\ProductStorageDependencyProvider as PyzProductStorageDependencyProvider;
use Spryker\Client\PriceProductStorage\Plugin\ProductViewPriceExpanderPlugin;
use Spryker\Client\ProductStorageExtension\Dependency\Plugin\ProductViewExpanderPluginInterface;

class ProductStorageDependencyProvider extends PyzProductStorageDependencyProvider
{
    /**
     * @return array<\Spryker\Client\ProductStorageExtension\Dependency\Plugin\ProductViewExpanderPluginInterface>
     */
    protected function getProductViewExpanderPlugins(): array
    {
        $plugins = parent::getProductViewExpanderPlugins();

        return $this->insertAfter($plugins, ProductViewPriceExpanderPlugin::class, new ProductViewGrossMarginExpanderPlugin());
    }

    /**
     * @param array<\Spryker\Client\ProductStorageExtension\Dependency\Plugin\ProductViewExpanderPluginInterface> $plugins
     * @param string $afterClass
     * @param \Spryker\Client\ProductStorageExtension\Dependency\Plugin\ProductViewExpanderPluginInterface $newPlugin
     *
     * @return array<\Spryker\Client\ProductStorageExtension\Dependency\Plugin\ProductViewExpanderPluginInterface>
     */
    private function insertAfter(array $plugins, string $afterClass, ProductViewExpanderPluginInterface $newPlugin): array
    {
        foreach ($plugins as $index => $plugin) {
            if ($plugin::class === $afterClass) {
                array_splice($plugins, $index + 1, 0, [$newPlugin]);

                return $plugins;
            }
        }

        $plugins[] = $newPlugin;

        return $plugins;
    }
}
