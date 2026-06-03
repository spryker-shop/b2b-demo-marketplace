<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Business\Model\Product;

use Spryker\Zed\PriceProduct\Business\Model\Product\PriceProductMapperInterface as SprykerPriceProductMapperInterface;

interface PriceProductMapperInterface extends SprykerPriceProductMapperInterface
{
    public function getCostPriceModeIdentifier(): string;
}
