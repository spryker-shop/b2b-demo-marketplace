<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductMerchantPortalGui\Communication\Mapper;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTableViewTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\ProductMerchantPortalGui\Dependency\Facade\ProductMerchantPortalGuiToMoneyFacadeInterface;

class CostPriceProductMapper
{
    /**
     * @uses \Spryker\Shared\PriceProduct\PriceProductConfig::PRICE_TYPE_DEFAULT
     *
     * @var string
     */
    protected const PRICE_TYPE_DEFAULT = 'DEFAULT';

    /**
     * @var string
     */
    protected const PRICE_KEY_FORMAT = '%s[%s][%s]';

    public function __construct(
        protected ProductMerchantPortalGuiToMoneyFacadeInterface $moneyFacade,
    ) {
    }

    public function mapPriceProductTransferToPriceProductTableViewTransfer(
        PriceProductTransfer $priceProductTransfer,
        PriceProductTableViewTransfer $priceProductTableViewTransfer,
    ): PriceProductTableViewTransfer {
        if (!$this->isDefaultPriceType($priceProductTransfer)) {
            return $priceProductTableViewTransfer;
        }

        $costAmount = $priceProductTransfer->getMoneyValueOrFail()->getCostAmount();

        if ($costAmount === null) {
            return $priceProductTableViewTransfer;
        }

        $prices = $priceProductTableViewTransfer->getPrices();
        $prices[$this->createCostKey($priceProductTransfer)] = $costAmount;

        return $priceProductTableViewTransfer->setPrices($prices);
    }

    /**
     * @param array<string, mixed> $data
     * @param \Generated\Shared\Transfer\PriceProductTransfer $priceProductTransfer
     *
     * @return \Generated\Shared\Transfer\PriceProductTransfer
     */
    public function mapTableDataToPriceProductTransfer(
        array $data,
        PriceProductTransfer $priceProductTransfer,
    ): PriceProductTransfer {
        if (!$this->isDefaultPriceType($priceProductTransfer)) {
            return $priceProductTransfer;
        }

        $costKey = $this->createCostKey($priceProductTransfer);

        if (!array_key_exists($costKey, $data) || $data[$costKey] === '' || $data[$costKey] === null) {
            return $priceProductTransfer;
        }

        $costAmount = $this->moneyFacade->convertDecimalToInteger((float)$data[$costKey]);
        $priceProductTransfer->getMoneyValueOrFail()->setCostAmount($costAmount);

        return $priceProductTransfer;
    }

    protected function isDefaultPriceType(PriceProductTransfer $priceProductTransfer): bool
    {
        return $priceProductTransfer->getPriceTypeOrFail()->getNameOrFail() === static::PRICE_TYPE_DEFAULT;
    }

    protected function createCostKey(PriceProductTransfer $priceProductTransfer): string
    {
        return sprintf(
            static::PRICE_KEY_FORMAT,
            mb_strtolower($priceProductTransfer->getPriceTypeOrFail()->getNameOrFail()),
            PriceProductTransfer::MONEY_VALUE,
            MoneyValueTransfer::COST_AMOUNT,
        );
    }
}
