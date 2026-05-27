<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferGui\Communication;

use Demo\Zed\PriceProductOfferGui\Communication\Reader\PriceProductOfferReader;
use Demo\Zed\PriceProductOfferGui\Dependency\Facade\PriceProductOfferGuiToPriceFacadeInterface;
use Spryker\Zed\PriceProductOfferGui\Communication\PriceProductOfferGuiCommunicationFactory as SprykerPriceProductOfferGuiCommunicationFactory;
use Spryker\Zed\PriceProductOfferGui\Communication\Reader\PriceProductOfferReaderInterface;
use Spryker\Zed\PriceProductOfferGui\PriceProductOfferGuiDependencyProvider;

/**
 * @method \Spryker\Zed\PriceProductOfferGui\PriceProductOfferGuiConfig getConfig()
 */
class PriceProductOfferGuiCommunicationFactory extends SprykerPriceProductOfferGuiCommunicationFactory
{
    public function createPriceProductOfferReader(): PriceProductOfferReaderInterface
    {
        return new PriceProductOfferReader(
            $this->getPriceProductFacade(),
            $this->getPriceFacade(),
            $this->getUtilEncodingService(),
        );
    }

    public function getPriceFacade(): PriceProductOfferGuiToPriceFacadeInterface
    {
        return $this->getProvidedDependency(PriceProductOfferGuiDependencyProvider::FACADE_PRICE);
    }
}
