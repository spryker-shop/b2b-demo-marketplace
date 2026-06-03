<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferGui\Dependency\Facade;

use Spryker\Zed\PriceProductOfferGui\Dependency\Facade\PriceProductOfferGuiToPriceFacadeInterface as SprykerPriceProductOfferGuiToPriceFacadeInterface;

interface PriceProductOfferGuiToPriceFacadeInterface extends SprykerPriceProductOfferGuiToPriceFacadeInterface
{
    public function getCostPriceModeIdentifier(): string;
}
