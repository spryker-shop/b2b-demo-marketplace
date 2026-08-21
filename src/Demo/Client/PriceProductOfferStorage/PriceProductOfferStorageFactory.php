<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\PriceProductOfferStorage;

use Demo\Client\PriceProductOfferStorage\Mapper\PriceProductOfferStorageMapper;
use Spryker\Client\PriceProductOfferStorage\Mapper\PriceProductOfferStorageMapperInterface;
use Spryker\Client\PriceProductOfferStorage\PriceProductOfferStorageFactory as SprykerPriceProductOfferStorageFactory;

class PriceProductOfferStorageFactory extends SprykerPriceProductOfferStorageFactory
{
    public function createPriceProductMapper(): PriceProductOfferStorageMapperInterface
    {
        return new PriceProductOfferStorageMapper();
    }
}
