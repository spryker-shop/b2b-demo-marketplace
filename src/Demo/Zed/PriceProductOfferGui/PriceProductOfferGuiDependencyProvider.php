<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferGui;

use Spryker\Zed\Kernel\Container;
use Spryker\Zed\PriceProductOfferGui\PriceProductOfferGuiDependencyProvider as SprykerPriceProductOfferGuiDependencyProvider;

class PriceProductOfferGuiDependencyProvider extends SprykerPriceProductOfferGuiDependencyProvider
{
    protected function addPriceFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRICE, function (Container $container) {
            return $container->getLocator()->price()->facade();
        });

        return $container;
    }
}
