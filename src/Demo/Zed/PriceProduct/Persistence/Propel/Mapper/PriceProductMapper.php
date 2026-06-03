<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStore;
use Spryker\Zed\PriceProduct\Persistence\Propel\Mapper\PriceProductMapper as SprykerPriceProductMapper;

class PriceProductMapper extends SprykerPriceProductMapper
{
    /**
     * @param \Orm\Zed\PriceProduct\Persistence\SpyPriceProductStore $priceProductStoreEntity
     * @param array<string, mixed> $priceProductStoreEntityData
     *
     * @return \Generated\Shared\Transfer\MoneyValueTransfer
     */
    protected function createMoneyValueTransfer(
        SpyPriceProductStore $priceProductStoreEntity,
        array $priceProductStoreEntityData,
    ): MoneyValueTransfer {
        $moneyValueTransfer = parent::createMoneyValueTransfer($priceProductStoreEntity, $priceProductStoreEntityData);
        $moneyValueTransfer->setCostAmount($priceProductStoreEntity->getCostPrice());

        return $moneyValueTransfer;
    }
}
