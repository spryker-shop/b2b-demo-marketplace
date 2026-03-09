<?php

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
