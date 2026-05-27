<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceCartConnector\Business;

use Demo\Zed\PriceCartConnector\Business\Manager\PriceManager;
use Spryker\Zed\PriceCartConnector\Business\Manager\PriceManagerInterface;
use Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorBusinessFactory as SprykerPriceCartConnectorBusinessFactory;

/**
 * @method \Demo\Zed\PriceCartConnector\PriceCartConnectorConfig getConfig()
 */
class PriceCartConnectorBusinessFactory extends SprykerPriceCartConnectorBusinessFactory
{
    public function createPriceManager(): PriceManagerInterface
    {
        return new PriceManager(
            $this->getPriceProductFacade(),
            $this->getPriceFacade(),
            $this->createPriceProductFilter(),
            $this->getPriceProductService(),
            $this->getPriceProductExpanderPlugins(),
            $this->createItemIdentifierBuilder(),
        );
    }
}
