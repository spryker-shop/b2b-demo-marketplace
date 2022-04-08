<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\MerchantProductOfferDataImport\Business\Model\Step;

use Pyz\Zed\MerchantProductOfferDataImport\Business\Model\DataSet\CombinedMerchantProductOfferDataSetInterface;
use Spryker\Zed\MerchantProductOfferDataImport\Business\Model\Step\MerchantProductOfferWriterStep;

class CombinedMerchantProductOfferWriterStep extends MerchantProductOfferWriterStep
{
    /**
     * @var string
     */
    protected const PRODUCT_OFFER_REFERENCE = CombinedMerchantProductOfferDataSetInterface::PRODUCT_OFFER_REFERENCE;

    /**
     * @var string
     */
    protected const CONCRETE_SKU = CombinedMerchantProductOfferDataSetInterface::CONCRETE_SKU;

    /**
     * @var string
     */
    protected const MERCHANT_SKU = CombinedMerchantProductOfferDataSetInterface::MERCHANT_SKU;

    /**
     * @var string
     */
    protected const IS_ACTIVE = CombinedMerchantProductOfferDataSetInterface::IS_ACTIVE;

    /**
     * @var string
     */
    protected const APPROVAL_STATUS = CombinedMerchantProductOfferDataSetInterface::APPROVAL_STATUS;

    /**
     * @var string
     */
    protected const MERCHANT_REFERENCE = CombinedMerchantProductOfferDataSetInterface::MERCHANT_REFERENCE;
}
