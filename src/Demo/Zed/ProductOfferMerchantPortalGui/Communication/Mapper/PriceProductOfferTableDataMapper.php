<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductOfferMerchantPortalGui\Communication\Mapper;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Mapper\PriceProductOfferTableDataMapper as SprykerPriceProductOfferTableDataMapper;

class PriceProductOfferTableDataMapper extends SprykerPriceProductOfferTableDataMapper
{
    /**
     * @uses \Spryker\Shared\PriceProduct\PriceProductConfig::PRICE_TYPE_DEFAULT
     *
     * @var string
     */
    protected const PRICE_TYPE_DEFAULT = 'default';

    /**
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypeTransfers
     *
     * @return array<string, int>
     */
    protected function preparePrices(PriceProductTransfer $priceProductTransfer, array $priceTypeTransfers): array
    {
        $prices = parent::preparePrices($priceProductTransfer, $priceTypeTransfers);

        $priceTypeName = mb_strtolower($priceProductTransfer->getPriceTypeOrFail()->getNameOrFail());
        $costAmount = $priceProductTransfer->getMoneyValueOrFail()->getCostAmount();

        if ($priceTypeName !== static::PRICE_TYPE_DEFAULT || $costAmount === null) {
            return $prices;
        }

        $prices[$this->createCostAmountColumnId($priceTypeName)] = $costAmount;

        return $prices;
    }

    protected function createCostAmountColumnId(string $priceTypeName): string
    {
        return $this->columnIdCreator->createPriceKey($priceTypeName, MoneyValueTransfer::COST_AMOUNT);
    }
}
