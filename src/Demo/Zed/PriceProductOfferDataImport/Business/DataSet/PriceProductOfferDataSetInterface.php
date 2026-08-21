<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferDataImport\Business\DataSet;

use Spryker\Zed\PriceProductOfferDataImport\Business\DataSet\PriceProductOfferDataSetInterface as SprykerPriceProductOfferDataSetInterface;

interface PriceProductOfferDataSetInterface extends SprykerPriceProductOfferDataSetInterface
{
    /**
     * @var string
     */
    public const VALUE_COST = 'value_cost';
}
