<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductOfferStockGui;

use Spryker\Zed\ProductOfferReservationGui\Communication\Plugin\ProductOfferStock\ProductOfferReservationProductOfferStockTableExpanderPlugin;
use Spryker\Zed\ProductOfferStockGui\ProductOfferStockGuiDependencyProvider as SprykerProductOfferStockGuiDependencyProvider;

class ProductOfferStockGuiDependencyProvider extends SprykerProductOfferStockGuiDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ProductOfferStockGuiExtension\Dependeency\Plugin\ProductOfferStockTableExpanderPluginInterface>
     */
    protected function getProductOfferStockTableExpanderPlugins(): array
    {
        return [
            new ProductOfferReservationProductOfferStockTableExpanderPlugin(),
        ];
    }
}
