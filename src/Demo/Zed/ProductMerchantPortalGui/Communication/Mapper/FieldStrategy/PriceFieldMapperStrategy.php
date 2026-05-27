<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductMerchantPortalGui\Communication\Mapper\FieldStrategy;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Spryker\Zed\ProductMerchantPortalGui\Communication\Mapper\FieldStrategy\PriceFieldMapperStrategy as SprykerPriceFieldMapperStrategy;

class PriceFieldMapperStrategy extends SprykerPriceFieldMapperStrategy
{
    /**
     * @var string
     */
    protected const SUFFIX_PRICE_TYPE_COST = 'cost';

    public function isApplicable(string $dataField): bool
    {
        if (parent::isApplicable($dataField)) {
            return true;
        }

        return $this->isCostPriceField($dataField);
    }

    /**
     * @param array<string, mixed> $data
     * @param \Generated\Shared\Transfer\MoneyValueTransfer $moneyValueTransfer
     *
     * @return \Generated\Shared\Transfer\MoneyValueTransfer
     */
    protected function mapDataToMoneyValueTransfer(
        array $data,
        MoneyValueTransfer $moneyValueTransfer,
    ): MoneyValueTransfer {
        $priceKey = (string)key($data);

        if (strpos($priceKey, MoneyValueTransfer::COST_AMOUNT) === false) {
            return parent::mapDataToMoneyValueTransfer($data, $moneyValueTransfer);
        }

        return $moneyValueTransfer->setCostAmount($this->convertDecimalToInteger($data[$priceKey]));
    }

    protected function isCostPriceField(string $fieldName): bool
    {
        $pattern = sprintf('/\[%sAmount]$/', static::SUFFIX_PRICE_TYPE_COST);
        preg_match($pattern, $fieldName, $matches);

        return (bool)$matches;
    }
}
