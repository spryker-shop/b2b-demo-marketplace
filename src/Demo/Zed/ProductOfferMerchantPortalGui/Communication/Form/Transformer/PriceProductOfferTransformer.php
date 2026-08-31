<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductOfferMerchantPortalGui\Communication\Form\Transformer;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductDimensionTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\PriceTypeTransfer;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Form\Transformer\PriceProductOfferTransformer as SprykerPriceProductOfferTransformer;

class PriceProductOfferTransformer extends SprykerPriceProductOfferTransformer
{
    /**
     * @param array<mixed> $newPriceProductOfferData
     */
    protected function createPriceProductTransfer(
        array $newPriceProductOfferData,
        PriceProductDimensionTransfer $priceProductDimensionTransfer,
        ?CurrencyTransfer $currencyTransfer,
        PriceTypeTransfer $priceTypeTransfer,
    ): ?PriceProductTransfer {
        $priceProductTransfer = parent::createPriceProductTransfer(
            $newPriceProductOfferData,
            $priceProductDimensionTransfer,
            $currencyTransfer,
            $priceTypeTransfer,
        );

        if ($priceProductTransfer === null) {
            return null;
        }

        $costAmountKey = $this->createCostAmountColumnId((string)$priceTypeTransfer->getName());

        $priceProductTransfer->getMoneyValueOrFail()->setCostAmount(
            $this->extractCostAmount($newPriceProductOfferData, $costAmountKey),
        );

        return $priceProductTransfer;
    }

    /**
     * @param array<mixed> $prices
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypes
     *
     * @return array<mixed>
     */
    protected function addPrices(PriceProductTransfer $priceProductTransfer, array $prices, array $priceTypes): array
    {
        $prices = parent::addPrices($priceProductTransfer, $prices, $priceTypes);

        $moneyValueTransfer = $priceProductTransfer->getMoneyValueOrFail();
        $costAmount = $moneyValueTransfer->getCostAmount();

        if ($costAmount === null) {
            return $prices;
        }

        foreach ($priceTypes as $priceTypeTransfer) {
            $priceTypeName = $priceTypeTransfer->getNameOrFail();

            if ($priceProductTransfer->getPriceTypeOrFail()->getName() !== $priceTypeName) {
                continue;
            }

            $prices[$this->createCostAmountColumnId($priceTypeName)] = $this->moneyFacade->convertIntegerToDecimal($costAmount);
        }

        return $prices;
    }

    /**
     * @param array<mixed> $newPriceProductOfferData
     */
    protected function extractCostAmount(array $newPriceProductOfferData, string $costAmountKey): ?int
    {
        $costAmount = $newPriceProductOfferData[$costAmountKey] ?? null;

        if ($costAmount === null || $costAmount === '') {
            return null;
        }

        return $this->moneyFacade->convertDecimalToInteger((float)$costAmount);
    }

    protected function createCostAmountColumnId(string $priceTypeName): string
    {
        return $this->columnIdCreator->createPriceKey($priceTypeName, MoneyValueTransfer::COST_AMOUNT);
    }
}
