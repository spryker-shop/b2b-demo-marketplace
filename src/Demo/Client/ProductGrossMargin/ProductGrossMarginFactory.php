<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\ProductGrossMargin;

use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\PriceProductStorage\PriceProductStorageClientInterface;

class ProductGrossMarginFactory extends AbstractFactory
{
    public function getPriceProductStorageClient(): PriceProductStorageClientInterface
    {
        return $this->getProvidedDependency(ProductGrossMarginDependencyProvider::CLIENT_PRICE_PRODUCT_STORAGE);
    }
}
