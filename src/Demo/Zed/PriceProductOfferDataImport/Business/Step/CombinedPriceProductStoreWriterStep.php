<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferDataImport\Business\Step;

use Demo\Zed\PriceProductOfferDataImport\Business\DataSet\CombinedPriceProductOfferDataSetInterface;

class CombinedPriceProductStoreWriterStep extends PriceProductStoreWriterStep
{
    /**
     * @var string
     */
    protected const VALUE_NET = CombinedPriceProductOfferDataSetInterface::VALUE_NET;

    /**
     * @var string
     */
    protected const VALUE_GROSS = CombinedPriceProductOfferDataSetInterface::VALUE_GROSS;

    /**
     * @var string
     */
    protected const VALUE_COST = CombinedPriceProductOfferDataSetInterface::VALUE_COST;
}
