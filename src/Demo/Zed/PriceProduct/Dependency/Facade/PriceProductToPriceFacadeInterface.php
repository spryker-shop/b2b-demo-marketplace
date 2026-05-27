<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Dependency\Facade;

use Spryker\Zed\PriceProduct\Dependency\Facade\PriceProductToPriceFacadeInterface as SprykerPriceProductToPriceFacadeInterface;

interface PriceProductToPriceFacadeInterface extends SprykerPriceProductToPriceFacadeInterface
{
    public function getCostPriceModeIdentifier(): string;
}
