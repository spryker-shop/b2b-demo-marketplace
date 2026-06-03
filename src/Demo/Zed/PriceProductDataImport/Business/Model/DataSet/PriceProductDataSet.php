<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductDataImport\Business\Model\DataSet;

use Spryker\Zed\PriceProductDataImport\Business\Model\DataSet\PriceProductDataSet as SprykerPriceProductDataSet;

interface PriceProductDataSet extends SprykerPriceProductDataSet
{
    /**
     * @var string
     */
    public const KEY_PRICE_COST = 'value_cost';
}
