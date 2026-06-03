<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductSchedule;

use Generated\Shared\Transfer\PriceProductScheduleImportTransfer;
use Spryker\Zed\PriceProductSchedule\PriceProductScheduleConfig as SprykerPriceProductScheduleConfig;

class PriceProductScheduleConfig extends SprykerPriceProductScheduleConfig
{
    /**
     * @var string
     */
    protected const KEY_VALUE_COST = 'value_cost';

    /**
     * @api
     *
     * @return array<string, string>
     */
    public function getImportFileToTransferFieldsMap(): array
    {
        return array_merge(parent::getImportFileToTransferFieldsMap(), [
            static::KEY_VALUE_COST => PriceProductScheduleImportTransfer::COST_AMOUNT,
        ]);
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getFieldsList(): array
    {
        return array_merge(parent::getFieldsList(), [
            static::KEY_VALUE_COST,
        ]);
    }
}
