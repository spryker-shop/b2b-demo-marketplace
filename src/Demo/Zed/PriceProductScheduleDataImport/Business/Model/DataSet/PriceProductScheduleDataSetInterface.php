<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductScheduleDataImport\Business\Model\DataSet;

use Spryker\Zed\PriceProductScheduleDataImport\Business\Model\DataSet\PriceProductScheduleDataSetInterface as SprykerPriceProductScheduleDataSetInterface;

interface PriceProductScheduleDataSetInterface extends SprykerPriceProductScheduleDataSetInterface
{
    /**
     * @var string
     */
    public const KEY_PRICE_COST = 'value_cost';
}
