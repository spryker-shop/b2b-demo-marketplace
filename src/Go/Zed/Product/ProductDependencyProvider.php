<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\Product;

use Pyz\Zed\Product\ProductDependencyProvider as PyzProductDependencyProvider;
use Spryker\Zed\TaxProductConnector\Communication\Plugin\Product\TaxSetProductAbstractCollectionExpanderPlugin;

class ProductDependencyProvider extends PyzProductDependencyProvider
{
    public function getProductAbstractCollectionExpanderPlugins(): array
    {
        $list = parent::getProductAbstractCollectionExpanderPlugins();

        $list[] = new TaxSetProductAbstractCollectionExpanderPlugin();

        return $list;
    }

    public function getProductConcreteExpanderPlugins(): array
    {
        return parent::getProductConcreteExpanderPlugins();
    }
}
