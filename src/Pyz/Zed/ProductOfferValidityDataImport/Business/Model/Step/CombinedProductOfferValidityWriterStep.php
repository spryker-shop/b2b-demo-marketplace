<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductOfferValidityDataImport\Business\Model\Step;

use Pyz\Zed\ProductOfferValidityDataImport\Business\Model\DataSet\CombinedProductOfferValidityDataSetInterface;
use Spryker\Zed\ProductOfferValidityDataImport\Business\Step\ProductOfferValidityWriterStep;

class CombinedProductOfferValidityWriterStep extends ProductOfferValidityWriterStep
{
    /**
     * @var string
     */
    protected const PRODUCT_VALID_FROM = CombinedProductOfferValidityDataSetInterface::VALID_FROM;

    /**
     * @var string
     */
    protected const PRODUCT_VALID_TO = CombinedProductOfferValidityDataSetInterface::VALID_TO;
}
