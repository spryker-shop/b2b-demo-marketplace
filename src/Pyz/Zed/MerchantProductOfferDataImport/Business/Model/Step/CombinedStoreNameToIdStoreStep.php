<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\MerchantProductOfferDataImport\Business\Model\Step;

use Pyz\Zed\MerchantProductOfferDataImport\Business\Model\DataSet\CombinedMerchantProductOfferDataSetInterface;
use Spryker\Zed\MerchantProductOfferDataImport\Business\Model\Step\StoreNameToIdStoreStep;

class CombinedStoreNameToIdStoreStep extends StoreNameToIdStoreStep
{
    /**
     * @var string
     */
    protected const STORE_NAME = CombinedMerchantProductOfferDataSetInterface::STORE_NAME;
}
