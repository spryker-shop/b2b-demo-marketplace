<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PriceProductOfferDataImport\Business\Model\Step;

use Pyz\Zed\PriceProductOfferDataImport\Business\Model\DataSet\CombinedPriceProductOfferDataSetInterface;
use Spryker\Zed\PriceProductOfferDataImport\Business\Step\ProductOfferReferenceToProductOfferDataStep;

class CombinedProductOfferReferenceToProductOfferDataStep extends ProductOfferReferenceToProductOfferDataStep
{
    /**
     * @var string
     */
    protected const PRODUCT_OFFER_REFERENCE = CombinedPriceProductOfferDataSetInterface::PRODUCT_OFFER_REFERENCE;
}
