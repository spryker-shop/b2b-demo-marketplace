<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOffer\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStore;
use Spryker\Zed\PriceProductOffer\Persistence\Propel\Mapper\PriceProductOfferMapper as SprykerPriceProductOfferMapper;

class PriceProductOfferMapper extends SprykerPriceProductOfferMapper
{
    protected function mapPriceProductStoreEntityToMoneyValueTransfer(
        SpyPriceProductStore $priceProductStoreEntity,
        MoneyValueTransfer $moneyValueTransfer,
    ): MoneyValueTransfer {
        $moneyValueTransfer = parent::mapPriceProductStoreEntityToMoneyValueTransfer($priceProductStoreEntity, $moneyValueTransfer);
        $moneyValueTransfer->setCostAmount($priceProductStoreEntity->getCostPrice());

        return $moneyValueTransfer;
    }
}
