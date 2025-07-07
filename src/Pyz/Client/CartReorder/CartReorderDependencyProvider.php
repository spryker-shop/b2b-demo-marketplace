<?php

namespace Pyz\Client\CartReorder;

use Spryker\Client\CartReorder\CartReorderDependencyProvider as SprykerCartReorderDependencyProvider;
use Spryker\Client\Quote\Plugin\CartReorder\ResetItemsSessionCartReorderQuoteProviderStrategyPlugin;

class CartReorderDependencyProvider extends SprykerCartReorderDependencyProvider
{
    /**
     * @return list<\Spryker\Client\CartReorderExtension\Dependency\Plugin\CartReorderQuoteProviderStrategyPluginInterface>
     */
    protected function getCartReorderQuoteProviderStrategyPlugins(): array
    {
        return [
            new ResetItemsSessionCartReorderQuoteProviderStrategyPlugin(),
        ];
    }
}
