<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceCartConnector\Business\Manager;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Spryker\Zed\PriceCartConnector\Business\Manager\PriceManager as SprykerPriceManager;

class PriceManager extends SprykerPriceManager
{
    /**
     * @var int
     */
    private const GROSS_MARGIN_PERCENTAGE_MULTIPLIER = 100;

    /**
     * @var array<string, int|null>
     */
    private array $concreteCostAmountByProductOfferReference = [];

    public function addPriceToItems(
        CartChangeTransfer $cartChangeTransfer,
        ?bool $ignorePriceMissingException = false,
    ): CartChangeTransfer {
        $cartChangeTransfer->setQuote(
            $this->setQuotePriceMode($cartChangeTransfer->getQuoteOrFail()),
        );
        $priceMode = $cartChangeTransfer->getQuoteOrFail()->getPriceModeOrFail();

        $priceProductFilterTransfers = $this->createPriceProductFilterTransfers($cartChangeTransfer);
        $priceProductTransfers = $this->priceProductFacade->getValidPrices($priceProductFilterTransfers);
        $priceProductTransfers = $this->executePriceProductExpanderPlugins($priceProductTransfers, $cartChangeTransfer);

        $priceProductTransfersIndexedByItemIdentifier = $this->getPriceProductTransfersIndexedByItemIdentifier(
            $cartChangeTransfer,
            array_values($priceProductTransfers),
            $priceProductFilterTransfers,
            $ignorePriceMissingException,
        );

        foreach ($cartChangeTransfer->getItems() as $key => $itemTransfer) {
            $itemIdentifier = $this->getItemIdentifier($itemTransfer, (string)$key);

            if (!array_key_exists($itemIdentifier, $priceProductTransfersIndexedByItemIdentifier)) {
                continue;
            }

            $indexedPriceProductTransfer = $priceProductTransfersIndexedByItemIdentifier[$itemIdentifier];
            $itemTransfer = $this->setOriginUnitPrices(
                $itemTransfer,
                $indexedPriceProductTransfer,
                $priceMode,
            );

            $moneyValueTransfer = $indexedPriceProductTransfer->getMoneyValueOrFail();
            $costAmount = $this->resolveCostAmount($moneyValueTransfer, $priceProductFilterTransfers[$itemIdentifier] ?? null);
            $itemTransfer->setOriginUnitCostPrice($costAmount)
                ->setUnitCostPrice($costAmount)
                ->setGrossMargin($this->getGrossMargin($moneyValueTransfer, $costAmount));

            if ($this->hasForcedUnitGrossPrice($itemTransfer)) {
                continue;
            }

            if ($this->hasSourceUnitPrices($itemTransfer)) {
                $itemTransfer = $this->applySourceUnitPrices($itemTransfer);

                continue;
            }

            $itemTransfer = $this->applyOriginUnitPrices($itemTransfer);
        }

        return $cartChangeTransfer;
    }

    private function resolveCostAmount(
        MoneyValueTransfer $moneyValueTransfer,
        ?PriceProductFilterTransfer $priceProductFilterTransfer,
    ): ?int {
        $costAmount = $moneyValueTransfer->getCostAmount();
        if ($costAmount) {
            return $costAmount;
        }

        if ($priceProductFilterTransfer === null) {
            return null;
        }

        $productOfferReference = $priceProductFilterTransfer->getProductOfferReference();
        if ($productOfferReference === null) {
            return null;
        }

        if (!array_key_exists($productOfferReference, $this->concreteCostAmountByProductOfferReference)) {
            $this->concreteCostAmountByProductOfferReference[$productOfferReference] = $this->getProductConcreteCostAmount($priceProductFilterTransfer);
        }

        return $this->concreteCostAmountByProductOfferReference[$productOfferReference];
    }

    protected function getGrossMargin(MoneyValueTransfer $moneyValueTransfer, ?int $costPrice = null): int
    {
        $grossAmount = $moneyValueTransfer->getGrossAmount();
        if (!$costPrice || !$grossAmount) {
            return 0;
        }

        return (int)((($grossAmount - $costPrice) / $grossAmount) * self::GROSS_MARGIN_PERCENTAGE_MULTIPLIER);
    }

    protected function getProductConcreteCostAmount(
        PriceProductFilterTransfer $priceFilterTransfer,
    ): ?int {
        $priceProductTransfer = $this->priceProductFacade->findPriceProductFor($priceFilterTransfer);
        if (!$priceProductTransfer) {
            return null;
        }

        return $priceProductTransfer->getMoneyValue()?->getCostAmount();
    }
}
