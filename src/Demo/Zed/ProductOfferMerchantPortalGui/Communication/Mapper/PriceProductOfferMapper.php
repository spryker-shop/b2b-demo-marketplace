<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductOfferMerchantPortalGui\Communication\Mapper;

use Generated\Shared\Transfer\GuiTableEditableDataErrorTransfer;
use Generated\Shared\Transfer\GuiTableEditableInitialDataTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductOfferCollectionTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ValidationErrorTransfer;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Mapper\PriceProductOfferMapper as SprykerPriceProductOfferMapper;

class PriceProductOfferMapper extends SprykerPriceProductOfferMapper
{
    /**
     * @uses \Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Validator\PriceProductOffer\PropertyPath\PriceProductOfferPropertyPathAnalyzer::PROPERTY_PATH_VALUES_INDEX_PRICE_PRODUCT_INDEX
     *
     * @var int
     */
    protected const PROPERTY_PATH_VALUES_INDEX_PRICE_PRODUCT_INDEX = 4;

    /**
     * @param array<mixed> $initialData
     *
     * @return array<mixed>
     */
    protected function addInitialDataErrors(
        ValidationErrorTransfer $validationErrorTransfer,
        PriceProductOfferCollectionTransfer $priceProductOfferCollectionTransfer,
        array $initialData,
    ): array {
        if ($this->hasPriceProductIndex((string)$validationErrorTransfer->getPropertyPath())) {
            return parent::addInitialDataErrors(
                $validationErrorTransfer,
                $priceProductOfferCollectionTransfer,
                $initialData,
            );
        }

        return $this->addRowErrors($validationErrorTransfer, $initialData);
    }

    protected function hasPriceProductIndex(string $propertyPath): bool
    {
        $propertyPathValues = explode('][', trim($propertyPath, '[]'));

        return count($propertyPathValues) > static::PROPERTY_PATH_VALUES_INDEX_PRICE_PRODUCT_INDEX;
    }

    /**
     * @param array<mixed> $initialData
     *
     * @return array<mixed>
     */
    protected function addRowErrors(ValidationErrorTransfer $validationErrorTransfer, array $initialData): array
    {
        $initialDataErrors = $initialData[GuiTableEditableInitialDataTransfer::ERRORS] ?? [];

        foreach (array_keys($initialData[GuiTableEditableInitialDataTransfer::DATA] ?? []) as $rowNumber) {
            $initialDataErrors[$rowNumber][GuiTableEditableDataErrorTransfer::ROW_ERROR] = $validationErrorTransfer->getMessage();
        }

        $initialData[GuiTableEditableInitialDataTransfer::ERRORS] = $initialDataErrors;

        return $initialData;
    }

    public function mapMoneyValuesToPriceProductTransfer(
        string $requestDataKey,
        string $requestDataValue,
        PriceProductTransfer $priceProductTransfer,
    ): PriceProductTransfer {
        $priceProductTransfer = parent::mapMoneyValuesToPriceProductTransfer(
            $requestDataKey,
            $requestDataValue,
            $priceProductTransfer,
        );

        if (strpos($requestDataKey, MoneyValueTransfer::COST_AMOUNT) === false) {
            return $priceProductTransfer;
        }

        $priceProductTransfer->getMoneyValueOrFail()->setCostAmount(
            $requestDataValue === '' ? null : $this->convertDecimalToInteger($requestDataValue),
        );

        return $priceProductTransfer;
    }
}
