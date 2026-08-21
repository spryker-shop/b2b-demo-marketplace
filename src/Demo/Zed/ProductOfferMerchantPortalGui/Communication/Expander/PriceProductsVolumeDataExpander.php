<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductOfferMerchantPortalGui\Communication\Expander;

use ArrayObject;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Expander\PriceProductsVolumeDataExpander as SprykerPriceProductsVolumeDataExpander;

class PriceProductsVolumeDataExpander extends SprykerPriceProductsVolumeDataExpander
{
    /**
     * The cost price belongs to the price row itself, not to a single volume tier: volume prices are stored in
     * `price_data.volume_prices[]`, which only holds quantity, net and gross price. A cost price edited on a volume row
     * is therefore applied to the price row the volume prices belong to, which is what every volume row displays.
     *
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PriceProductTransfer> $priceProductTransfers
     * @param array<mixed> $requestData
     * @param int $volumeQuantity
     * @param int $idProductOffer
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\PriceProductTransfer>
     */
    public function expandPriceProductsWithVolumeData(
        ArrayObject $priceProductTransfers,
        array $requestData,
        int $volumeQuantity,
        int $idProductOffer,
    ): ArrayObject {
        $costAmountsByPriceIdentifier = $this->getCostAmountsByPriceIdentifier($priceProductTransfers, $requestData);

        $storedPriceProductTransfers = parent::expandPriceProductsWithVolumeData(
            $priceProductTransfers,
            $requestData,
            $volumeQuantity,
            $idProductOffer,
        );

        return $this->applyCostAmounts($storedPriceProductTransfers, $costAmountsByPriceIdentifier);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PriceProductTransfer> $priceProductTransfers
     * @param array<mixed> $requestData
     *
     * @return array<string, int|null>
     */
    protected function getCostAmountsByPriceIdentifier(ArrayObject $priceProductTransfers, array $requestData): array
    {
        $costAmountsByPriceIdentifier = [];

        foreach ($requestData as $requestDataKey => $requestDataValue) {
            if (strpos((string)$requestDataKey, MoneyValueTransfer::COST_AMOUNT) === false) {
                continue;
            }

            $costAmount = $this->priceProductOfferMapper->mapMoneyValuesToPriceProductTransfer(
                (string)$requestDataKey,
                (string)$requestDataValue,
                (new PriceProductTransfer())->setMoneyValue(new MoneyValueTransfer()),
            )->getMoneyValueOrFail()->getCostAmount();

            foreach ($priceProductTransfers as $priceProductTransfer) {
                $costAmountsByPriceIdentifier[$this->createPriceIdentifier($priceProductTransfer)] = $costAmount;
            }
        }

        return $costAmountsByPriceIdentifier;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\PriceProductTransfer> $storedPriceProductTransfers
     * @param array<string, int|null> $costAmountsByPriceIdentifier
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\PriceProductTransfer>
     */
    protected function applyCostAmounts(
        ArrayObject $storedPriceProductTransfers,
        array $costAmountsByPriceIdentifier,
    ): ArrayObject {
        if (!$costAmountsByPriceIdentifier) {
            return $storedPriceProductTransfers;
        }

        foreach ($storedPriceProductTransfers as $storedPriceProductTransfer) {
            $priceIdentifier = $this->createPriceIdentifier($storedPriceProductTransfer);

            if (!array_key_exists($priceIdentifier, $costAmountsByPriceIdentifier)) {
                continue;
            }

            $storedPriceProductTransfer->getMoneyValueOrFail()->setCostAmount(
                $costAmountsByPriceIdentifier[$priceIdentifier],
            );
        }

        return $storedPriceProductTransfers;
    }

    protected function createPriceIdentifier(PriceProductTransfer $priceProductTransfer): string
    {
        $moneyValueTransfer = $priceProductTransfer->getMoneyValueOrFail();

        return sprintf(
            '%d-%d-%d',
            $moneyValueTransfer->getFkStoreOrFail(),
            $moneyValueTransfer->getFkCurrencyOrFail(),
            $priceProductTransfer->getPriceTypeOrFail()->getIdPriceTypeOrFail(),
        );
    }
}
