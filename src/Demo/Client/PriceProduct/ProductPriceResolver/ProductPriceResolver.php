<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\PriceProduct\ProductPriceResolver;

use Generated\Shared\Transfer\CurrentProductPriceTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Spryker\Client\PriceProduct\ProductPriceResolver\ProductPriceResolver as SprykerProductPriceResolver;

class ProductPriceResolver extends SprykerProductPriceResolver
{
    /**
     * @var \Demo\Client\PriceProduct\PriceProductConfig
     */
    protected $priceProductConfig;

    protected function getPriceValueByPriceMode(MoneyValueTransfer $moneyValueTransfer, string $priceMode): ?int
    {
        if ($priceMode === $this->priceProductConfig->getPriceModeIdentifierForCostType()) {
            return $moneyValueTransfer->getCostAmount();
        }

        if ($priceMode === $this->priceProductConfig->getPriceModeIdentifierForNetType()) {
            return $moneyValueTransfer->getNetAmount();
        }

        return $moneyValueTransfer->getGrossAmount();
    }

    /**
     * @param array<\Generated\Shared\Transfer\PriceProductTransfer> $priceProductTransfers
     * @param \Generated\Shared\Transfer\CurrentProductPriceTransfer $currentProductPriceTransfer
     * @param \Generated\Shared\Transfer\PriceProductFilterTransfer $priceProductFilter
     *
     * @return \Generated\Shared\Transfer\CurrentProductPriceTransfer
     */
    protected function prepareCurrentProductPriceTransfer(
        array $priceProductTransfers,
        CurrentProductPriceTransfer $currentProductPriceTransfer,
        PriceProductFilterTransfer $priceProductFilter,
    ): CurrentProductPriceTransfer {
        $priceProductTransfer = $this->priceProductService->resolveProductPriceByPriceProductFilter(
            $priceProductTransfers,
            $priceProductFilter,
        );

        if (!$priceProductTransfer) {
            return $currentProductPriceTransfer;
        }

        /** @var string $priceMode */
        $priceMode = $priceProductFilter->getPriceModeOrFail();
        /** @var \Generated\Shared\Transfer\MoneyValueTransfer $moneyValueTransfer */
        $moneyValueTransfer = $priceProductTransfer->getMoneyValueOrFail();

        $price = $this->getPriceValueByPriceMode($moneyValueTransfer, $priceMode);

        if ($price === null) {
            return $currentProductPriceTransfer;
        }

        $priceProductFilterAllPriceTypes = clone $priceProductFilter;
        $priceProductFilterAllPriceTypes->setPriceTypeName(null);
        $priceProductFilterAllPriceTypes->setPriceDimension($priceProductTransfer->getPriceDimension());

        $priceProductAllPriceTypesTransfers = $this->priceProductService->resolveProductPricesByPriceProductFilter(
            $priceProductTransfers,
            $priceProductFilterAllPriceTypes,
        );

        $prices = [];
        $priceDataByPriceType = [];
        foreach ($priceProductAllPriceTypesTransfers as $priceProductOnePriceTypeTransfer) {
            /** @var \Generated\Shared\Transfer\MoneyValueTransfer $onePriceTypeMoneyValueTransfer */
            $onePriceTypeMoneyValueTransfer = $priceProductOnePriceTypeTransfer->getMoneyValueOrFail();
            $prices[$priceProductOnePriceTypeTransfer->getPriceTypeName()] = $this->getPriceValueByPriceMode($onePriceTypeMoneyValueTransfer, $priceMode);

            $priceData = $priceProductOnePriceTypeTransfer->getMoneyValueOrFail()->getPriceData();
            if (!$priceData) {
                continue;
            }

            $priceDataByPriceType[$priceProductOnePriceTypeTransfer->getPriceTypeName()] = $priceData;
        }

        $costPrice = $moneyValueTransfer->getCostAmount();

        if ($priceProductFilter->getProductOfferReference() !== null && $costPrice === null) {
            $costPrice = $this->getConcreteProductCostPrice($priceProductFilter, $priceProductTransfers);
        }

        return $currentProductPriceTransfer
            ->setPrice($price)
            ->setPrices($prices)
            ->setCurrency($priceProductFilter->getCurrency())
            ->setQuantity($priceProductFilter->getQuantity())
            ->setPriceMode($priceMode)
            ->setSumPrice($price * $priceProductFilter->getQuantity())
            ->setPriceData($moneyValueTransfer->getPriceData())
            ->setPriceDataByPriceType($this->getPriceDataByPriceType($moneyValueTransfer, $priceDataByPriceType))
            ->setPriceDimension($priceProductTransfer->getPriceDimension())
            ->setGrossMargin($this->getGrossMargin($moneyValueTransfer, $costPrice));
    }

    protected function getGrossMargin(MoneyValueTransfer $moneyValueTransfer, ?int $costPrice = null): int
    {
        if (!$costPrice || (int)$moneyValueTransfer->getNetAmount() === 0) {
            return 0;
        }

        return (int)((($moneyValueTransfer->getNetAmount() - $costPrice) / $moneyValueTransfer->getNetAmount()) * 100);
    }

    /**
     * @param \Generated\Shared\Transfer\PriceProductFilterTransfer $priceProductFilter
     * @param array<\Generated\Shared\Transfer\PriceProductTransfer> $priceProductTransfers
     *
     * @return int|null
     */
    protected function getConcreteProductCostPrice(PriceProductFilterTransfer $priceProductFilter, array $priceProductTransfers): ?int
    {
        $originalPriceProductFilter = clone $priceProductFilter;
        $originalPriceProductFilter->setProductOfferReference(null);

        $originalConcreteProductPriceTransfer = $this->priceProductService->resolveProductPriceByPriceProductFilter(
            $priceProductTransfers,
            $originalPriceProductFilter,
        );

        return $originalConcreteProductPriceTransfer?->getMoneyValue()?->getCostAmount();
    }
}
