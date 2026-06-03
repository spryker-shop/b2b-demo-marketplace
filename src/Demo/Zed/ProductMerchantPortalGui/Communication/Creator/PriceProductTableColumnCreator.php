<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductMerchantPortalGui\Communication\Creator;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\ProductMerchantPortalGui\Communication\Creator\PriceProductTableColumnCreator as SprykerPriceProductTableColumnCreator;

class PriceProductTableColumnCreator extends SprykerPriceProductTableColumnCreator
{
    /**
     * @param \Generated\Shared\Transfer\PriceProductTransfer $priceProductTransfer
     * @param array<string> $propertyPathValues
     *
     * @return string
     */
    public function createColumnIdFromPropertyPath(
        PriceProductTransfer $priceProductTransfer,
        array $propertyPathValues,
    ): string {
        $fieldName = end($propertyPathValues);

        if ($fieldName === MoneyValueTransfer::COST_AMOUNT) {
            return $this->createPriceColumnId(
                $priceProductTransfer->getPriceTypeOrFail()->getNameOrFail(),
                $fieldName,
            );
        }

        return parent::createColumnIdFromPropertyPath($priceProductTransfer, $propertyPathValues);
    }
}
