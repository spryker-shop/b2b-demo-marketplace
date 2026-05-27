<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\Price\Business;

use Demo\Zed\PriceProduct\Dependency\Facade\PriceProductToPriceFacadeInterface;
use Demo\Zed\PriceProductOfferGui\Dependency\Facade\PriceProductOfferGuiToPriceFacadeInterface;
use Demo\Zed\ProductManagement\Dependency\Facade\ProductManagementToPriceInterface;
use Spryker\Zed\Price\Business\PriceFacade as SprykerPriceFacade;

/**
 * @method \Demo\Zed\Price\Business\PriceBusinessFactory getFactory()
 */
class PriceFacade extends SprykerPriceFacade implements
    PriceFacadeInterface,
    PriceProductToPriceFacadeInterface,
    PriceProductOfferGuiToPriceFacadeInterface,
    ProductManagementToPriceInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getGrossPriceModeIdentifier(): string
    {
        return $this->getFactory()
            ->getModuleConfig()
            ->getGrossPriceModeIdentifier();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNetPriceModeIdentifier(): string
    {
        return $this->getFactory()
            ->getModuleConfig()
            ->getNetPriceModeIdentifier();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDefaultPriceMode(): string
    {
        return $this->getFactory()
            ->getModuleConfig()
            ->getDefaultPriceMode();
    }

    /**
     * @api
     */
    public function getCostPriceModeIdentifier(): string
    {
        return $this->getFactory()
            ->getConfig()
            ->getCostPriceModeIdentifier();
    }
}
