<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferDataImport\Business\DataSet;

use Pyz\Zed\PriceProductOfferDataImport\Business\Model\DataSet\CombinedPriceProductOfferDataSetInterface as PyzCombinedPriceProductOfferDataSetInterface;

interface CombinedPriceProductOfferDataSetInterface extends PyzCombinedPriceProductOfferDataSetInterface
{
    /**
     * @var string
     */
    public const VALUE_COST = 'price_product_offer.value_cost';
}
