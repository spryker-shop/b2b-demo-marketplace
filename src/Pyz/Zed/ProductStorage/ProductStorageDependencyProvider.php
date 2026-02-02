<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductStorage;

use Spryker\Zed\ProductStorage\ProductStorageDependencyProvider as SprykerProductStorageDependencyProvider;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\ProductStorage\ProductClassProductConcreteStorageCollectionExpanderPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\ProductStorage\ShipmentTypeProductConcreteStorageCollectionExpanderPlugin;

class ProductStorageDependencyProvider extends SprykerProductStorageDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ProductStorageExtension\Dependency\Plugin\ProductAbstractStorageExpanderPluginInterface>
     */
    protected function getProductAbstractStorageExpanderPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\ProductStorageExtension\Dependency\Plugin\ProductAbstractStorageCollectionFilterPluginInterface>
     */
    protected function getProductAbstractStorageCollectionFilterPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\ProductStorageExtension\Dependency\Plugin\ProductConcreteStorageCollectionFilterPluginInterface>
     */
    protected function getProductConcreteStorageCollectionFilterPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\ProductStorageExtension\Dependency\Plugin\ProductConcreteStorageCollectionExpanderPluginInterface>
     */
    protected function getProductConcreteStorageCollectionExpanderPlugins(): array
    {
        return [new ShipmentTypeProductConcreteStorageCollectionExpanderPlugin(),
            new ProductClassProductConcreteStorageCollectionExpanderPlugin(),
        ];
    }
}
