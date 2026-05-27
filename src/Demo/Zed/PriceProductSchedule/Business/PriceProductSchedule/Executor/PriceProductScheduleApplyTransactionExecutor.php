<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductSchedule\Business\PriceProductSchedule\Executor;

use Generated\Shared\Transfer\PriceProductScheduleTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\PriceProductSchedule\Business\PriceProductSchedule\Executor\PriceProductScheduleApplyTransactionExecutor as SprykerPriceProductScheduleApplyTransactionExecutor;

class PriceProductScheduleApplyTransactionExecutor extends SprykerPriceProductScheduleApplyTransactionExecutor
{
    protected function preparePriceProductTransferForPersist(
        PriceProductScheduleTransfer $priceProductScheduleTransfer,
    ): PriceProductTransfer {
        $priceProductTransfer = $priceProductScheduleTransfer->getPriceProductOrFail();

        $moneyValueTransfer = $priceProductTransfer->getMoneyValueOrFail();

        $priceProductTransferForPersist = $this->getPriceProductForPersist(
            $priceProductTransfer,
            $priceProductScheduleTransfer->getStore(),
        );

        $priceProductTransferForPersist->getMoneyValueOrFail()
            ->setGrossAmount($moneyValueTransfer->getGrossAmount())
            ->setNetAmount($moneyValueTransfer->getNetAmount())
            ->setCostAmount($moneyValueTransfer->getCostAmount());

        return $priceProductTransferForPersist;
    }
}
