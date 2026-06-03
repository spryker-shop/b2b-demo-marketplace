<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductSchedule\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Orm\Zed\PriceProductSchedule\Persistence\SpyPriceProductSchedule;
use Spryker\Zed\PriceProductSchedule\Persistence\Propel\Mapper\PriceProductScheduleMapper as SprykerPriceProductScheduleMapper;

class PriceProductScheduleMapper extends SprykerPriceProductScheduleMapper
{
    protected function mapPriceProductScheduleEntityToMoneyValueTransfer(
        SpyPriceProductSchedule $priceProductScheduleEntity,
        MoneyValueTransfer $moneyValueTransfer,
    ): MoneyValueTransfer {
        $moneyValueTransfer = parent::mapPriceProductScheduleEntityToMoneyValueTransfer(
            $priceProductScheduleEntity,
            $moneyValueTransfer,
        );

        return $moneyValueTransfer->setCostAmount($priceProductScheduleEntity->getCostPrice());
    }
}
