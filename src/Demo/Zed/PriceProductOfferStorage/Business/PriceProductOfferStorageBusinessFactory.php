<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferStorage\Business;

use Demo\Zed\PriceProductOfferStorage\Business\PriceProductOfferStorage\PriceProductOfferStorageWriter;
use Spryker\Zed\PriceProductOfferStorage\Business\PriceProductOfferStorage\PriceProductOfferStorageWriterInterface;
use Spryker\Zed\PriceProductOfferStorage\Business\PriceProductOfferStorageBusinessFactory as SprykerPriceProductOfferStorageBusinessFactory;

/**
 * @method \Pyz\Zed\PriceProductOfferStorage\PriceProductOfferStorageConfig getConfig()
 */
class PriceProductOfferStorageBusinessFactory extends SprykerPriceProductOfferStorageBusinessFactory
{
    public function createPriceProductOfferStorageWriter(): PriceProductOfferStorageWriterInterface
    {
        return new PriceProductOfferStorageWriter(
            $this->getEventFacade(),
            $this->getPriceProductOfferFacade(),
            $this->getEventBehaviorFacade(),
        );
    }
}
