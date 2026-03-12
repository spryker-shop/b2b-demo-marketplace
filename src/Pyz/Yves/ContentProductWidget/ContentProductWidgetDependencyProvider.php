<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\ContentProductWidget;

use SprykerShop\Yves\ContentProductWidget\ContentProductWidgetDependencyProvider as SprykerContentProductWidgetDependencyProvider;
use SprykerShop\Yves\ProductCategoryWidget\Plugin\ContentProductWidget\ProductCategoryContentProductAbstractCollectionExpanderPlugin;

class ContentProductWidgetDependencyProvider extends SprykerContentProductWidgetDependencyProvider
{
    protected function getContentProductAbstractCollectionExpanderPlugins(): array
    {
        return [
            new ProductCategoryContentProductAbstractCollectionExpanderPlugin(),
        ];
    }
}
