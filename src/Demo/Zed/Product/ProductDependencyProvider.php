<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\Product;

use Pyz\Zed\Product\ProductDependencyProvider as PyzProductDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\Product\ProductCategoryProductAbstractAfterUpdatePlugin;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\Product\ProductCategoryProductAbstractPostCreatePlugin;

class ProductDependencyProvider extends PyzProductDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductAbstractPostCreatePluginInterface>
     */
    protected function getProductAbstractPostCreatePlugins(): array
    {
        return array_merge(parent::getProductAbstractPostCreatePlugins(), [
            new ProductCategoryProductAbstractPostCreatePlugin(),
        ]);
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\Product\Dependency\Plugin\ProductAbstractPluginUpdateInterface>
     */
    protected function getProductAbstractAfterUpdatePlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return array_merge(parent::getProductAbstractAfterUpdatePlugins($container), [
            new ProductCategoryProductAbstractAfterUpdatePlugin(),
        ]);
    }
}
