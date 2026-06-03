<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductSchedule\Business\PriceProductSchedule;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductScheduleImportTransfer;
use Spryker\Zed\PriceProductSchedule\Business\PriceProductSchedule\PriceProductScheduleMapper as SprykerPriceProductScheduleMapper;

class PriceProductScheduleMapper extends SprykerPriceProductScheduleMapper
{
    protected function mapMoneyValueTransferFromPriceProductScheduleImportTransfer(
        PriceProductScheduleImportTransfer $priceProductScheduleImportTransfer,
        MoneyValueTransfer $moneyValueTransfer,
    ): MoneyValueTransfer {
        $currencyTransfer = $this->createCurrencyTransfer($priceProductScheduleImportTransfer->getCurrencyCodeOrFail());
        $storeTransfer = $this->createStoreTransfer($priceProductScheduleImportTransfer->getStoreNameOrFail());

        return $moneyValueTransfer
            ->setCurrency($currencyTransfer)
            ->setStore($storeTransfer)
            ->setNetAmount($priceProductScheduleImportTransfer->getNetAmount())
            ->setGrossAmount($priceProductScheduleImportTransfer->getGrossAmount())
            ->setCostAmount($priceProductScheduleImportTransfer->getCostAmount());
    }
}
