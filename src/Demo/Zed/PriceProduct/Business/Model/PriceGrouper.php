<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Business\Model;

use Demo\Zed\PriceProduct\Business\Model\Product\PriceProductMapperInterface;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\PriceProduct\Business\Model\PriceGrouper as SprykerPriceGrouper;
use Spryker\Zed\PriceProduct\Business\Model\ReaderInterface;
use Spryker\Zed\PriceProduct\PriceProductConfig;

class PriceGrouper extends SprykerPriceGrouper
{
    /**
     * @var \Demo\Zed\PriceProduct\Business\Model\Product\PriceProductMapperInterface
     */
    protected $priceProductMapper;

    public function __construct(
        ReaderInterface $priceReader,
        PriceProductMapperInterface $priceProductMapper,
        PriceProductConfig $config,
    ) {
        parent::__construct($priceReader, $priceProductMapper, $config);
    }

    /**
     * @param \Generated\Shared\Transfer\PriceProductTransfer $priceProductTransfer
     * @param array<string, array<string, mixed>> $prices
     *
     * @return array<string, array<string, mixed>>
     */
    protected function groupPriceByCurrencyAndStore(PriceProductTransfer $priceProductTransfer, array $prices): array
    {
        $priceMoneyValueTransfer = $priceProductTransfer->getMoneyValueOrFail();
        $priceTypeTransfer = $priceProductTransfer->getPriceTypeOrFail();
        $priceType = $priceTypeTransfer->getName();
        $currencyIsoCode = $priceMoneyValueTransfer->getCurrencyOrFail()->getCode();

        $prices = parent::groupPriceByCurrencyAndStore($priceProductTransfer, $prices);

        if ($priceMoneyValueTransfer->getCostAmount() === null) {
            return $prices;
        }

        $prices[$currencyIsoCode][$this->priceProductMapper->getCostPriceModeIdentifier()][$priceType]
            = $priceMoneyValueTransfer->getCostAmount();

        return $prices;
    }
}
