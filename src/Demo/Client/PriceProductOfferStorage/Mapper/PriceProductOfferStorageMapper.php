<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\PriceProductOfferStorage\Mapper;

use Generated\Shared\Transfer\PriceProductOfferStorageTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Client\PriceProductOfferStorage\Mapper\PriceProductOfferStorageMapper as SprykerPriceProductOfferStorageMapper;

class PriceProductOfferStorageMapper extends SprykerPriceProductOfferStorageMapper
{
    /**
     * @param array<mixed> $priceProductOffer
     */
    public function mapPriceProductOfferStorageDataToPriceProductTransfer(
        array $priceProductOffer,
        PriceProductTransfer $priceProductTransfer,
    ): PriceProductTransfer {
        $priceProductTransfer = parent::mapPriceProductOfferStorageDataToPriceProductTransfer(
            $priceProductOffer,
            $priceProductTransfer,
        );

        $priceProductOfferStorageTransfer = (new PriceProductOfferStorageTransfer())->fromArray($priceProductOffer);

        $priceProductTransfer->getMoneyValueOrFail()->setCostAmount(
            $priceProductOfferStorageTransfer->getCostPrice(),
        );

        return $priceProductTransfer;
    }
}
