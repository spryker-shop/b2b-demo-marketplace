<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\ProductOfferStockDataImport\Business\Model\Step;

use Pyz\Zed\ProductOfferStockDataImport\Business\Model\DataSet\CombinedProductOfferStockDataSetInterface;
use Spryker\Zed\ProductOfferStockDataImport\Business\Step\ProductOfferReferenceToIdProductOfferStep;

class CombinedProductOfferReferenceToIdProductOfferStep extends ProductOfferReferenceToIdProductOfferStep
{
    /**
     * @var string
     */
    protected const PRODUCT_OFFER_REFERENCE = CombinedProductOfferStockDataSetInterface::PRODUCT_OFFER_REFERENCE;
}
