<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferGui\Communication\Reader;

use Demo\Zed\PriceProductOfferGui\Dependency\Facade\PriceProductOfferGuiToPriceFacadeInterface;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\PriceProductOfferGui\Communication\Reader\PriceProductOfferReader as SprykerPriceProductOfferReader;
use Spryker\Zed\PriceProductOfferGui\Dependency\Facade\PriceProductOfferGuiToPriceProductFacadeInterface;
use Spryker\Zed\PriceProductOfferGui\Dependency\Service\PriceProductOfferGuiToUtilEncodingServiceInterface;

class PriceProductOfferReader extends SprykerPriceProductOfferReader
{
    /**
     * @var \Demo\Zed\PriceProductOfferGui\Dependency\Facade\PriceProductOfferGuiToPriceFacadeInterface
     */
    protected $priceFacade;

    public function __construct(
        PriceProductOfferGuiToPriceProductFacadeInterface $priceProductFacade,
        PriceProductOfferGuiToPriceFacadeInterface $priceFacade,
        PriceProductOfferGuiToUtilEncodingServiceInterface $utilEncodingService,
    ) {
        parent::__construct($priceProductFacade, $priceFacade, $utilEncodingService);
    }

    /**
     * @param \Generated\Shared\Transfer\PriceProductTransfer $priceProductTransfer
     * @param array<string, array<string, array<string, array<string, \Generated\Shared\Transfer\PriceProductTransfer>>>> $priceTable
     *
     * @return array<string, array<string, array<string, array<string, \Generated\Shared\Transfer\PriceProductTransfer>>>>
     */
    protected function getPriceTable(
        PriceProductTransfer $priceProductTransfer,
        array $priceTable,
    ): array {
        $priceTypeTransfer = $priceProductTransfer->getPriceType();

        if (!$priceTypeTransfer) {
            return $priceTable;
        }

        $grossPriceModeIdentifier = $this->getGrossPriceModeIdentifier();
        $netPriceModeIdentifier = $this->getNetPriceModeIdentifier();
        $costPriceModeIdentifier = $this->getCostPriceModeIdentifier();

        $priceType = $priceTypeTransfer->getName();
        $priceModeConfiguration = $priceTypeTransfer->getPriceModeConfiguration();

        $moneyValueTransfer = $priceProductTransfer->getMoneyValueOrFail();
        $storeName = $moneyValueTransfer->getStoreOrFail()->getName();
        $currencyIsoCode = $moneyValueTransfer->getCurrencyOrFail()->getCode();

        if ($priceModeConfiguration === $this->getPriceModeIdentifierForBothType()) {
            $priceTable[$storeName][$currencyIsoCode][$costPriceModeIdentifier][$priceType] = $priceProductTransfer;
            $priceTable[$storeName][$currencyIsoCode][$netPriceModeIdentifier][$priceType] = $priceProductTransfer;
            $priceTable[$storeName][$currencyIsoCode][$grossPriceModeIdentifier][$priceType] = $priceProductTransfer;

            return $priceTable;
        }

        $priceTable[$storeName][$currencyIsoCode][$priceModeConfiguration][$priceType] = $priceProductTransfer;

        return $priceTable;
    }

    protected function getCostPriceModeIdentifier(): string
    {
        return $this->priceFacade->getCostPriceModeIdentifier();
    }
}
