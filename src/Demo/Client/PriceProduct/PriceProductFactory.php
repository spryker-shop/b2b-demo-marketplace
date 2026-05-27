<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\PriceProduct;

use Demo\Client\PriceProduct\ProductPriceResolver\ProductPriceResolver;
use Spryker\Client\PriceProduct\PriceProductFactory as SprykerPriceProductFactory;
use Spryker\Client\PriceProduct\ProductPriceResolver\ProductPriceResolverInterface;

class PriceProductFactory extends SprykerPriceProductFactory
{
    public function createProductPriceResolver(): ProductPriceResolverInterface
    {
        return new ProductPriceResolver(
            $this->getPriceClient(),
            $this->getCurrencyClient(),
            $this->getConfig(),
            $this->getQuoteClient(),
            $this->getPriceProductService(),
            $this->getPriceProductPostResolvePlugins(),
        );
    }
}
