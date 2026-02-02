<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductManagement;

use Pyz\Zed\ProductManagement\ProductManagementDependencyProvider as PyzProductManagementDependencyProvider;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\ProductManagement\ImageAltTextProductAbstractFormExpanderPlugin;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\ProductManagement\ImageAltTextProductConcreteEditFormExpanderPlugin;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\ProductManagement\ImageAltTextProductConcreteFormExpanderPlugin;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\ProductManagement\ProductCategoryAbstractFormExpanderPlugin;

class ProductManagementDependencyProvider extends PyzProductManagementDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductConcreteEditFormExpanderPluginInterface>
     */
    protected function getProductConcreteEditFormExpanderPlugins(): array
    {
        return array_merge(parent::getProductConcreteEditFormExpanderPlugins(), [
            new ImageAltTextProductConcreteEditFormExpanderPlugin(),
        ]);
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductAbstractFormExpanderPluginInterface>
     */
    protected function getProductAbstractFormExpanderPlugins(): array
    {
        return array_merge(parent::getProductAbstractFormExpanderPlugins(), [
            new ProductCategoryAbstractFormExpanderPlugin(),
            new ImageAltTextProductAbstractFormExpanderPlugin(),
        ]);
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductConcreteFormExpanderPluginInterface>
     */
    protected function getProductConcreteFormExpanderPlugins(): array
    {
        return array_merge(parent::getProductConcreteFormExpanderPlugins(), [
            new ImageAltTextProductConcreteFormExpanderPlugin(),
        ]);
    }
}
