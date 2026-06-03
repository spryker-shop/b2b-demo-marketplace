<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductMerchantPortalGui\Communication;

use Demo\Zed\ProductMerchantPortalGui\Communication\Creator\PriceProductTableColumnCreator;
use Demo\Zed\ProductMerchantPortalGui\Communication\Expander\CostPriceTableConfigurationExpander;
use Demo\Zed\ProductMerchantPortalGui\Communication\Mapper\CostPriceProductMapper;
use Demo\Zed\ProductMerchantPortalGui\Communication\Mapper\FieldStrategy\PriceFieldMapperStrategy;
use Spryker\Zed\ProductMerchantPortalGui\Communication\Creator\PriceProductTableColumnCreatorInterface;
use Spryker\Zed\ProductMerchantPortalGui\Communication\Mapper\FieldStrategy\FieldMapperStrategyInterface;
use Spryker\Zed\ProductMerchantPortalGui\Communication\ProductMerchantPortalGuiCommunicationFactory as SprykerProductMerchantPortalGuiCommunicationFactory;

/**
 * @method \Spryker\Zed\ProductMerchantPortalGui\ProductMerchantPortalGuiConfig getConfig()
 * @method \Spryker\Zed\ProductMerchantPortalGui\Persistence\ProductMerchantPortalGuiRepositoryInterface getRepository()
 */
class ProductMerchantPortalGuiCommunicationFactory extends SprykerProductMerchantPortalGuiCommunicationFactory
{
    public function createPriceProductTableColumnCreator(): PriceProductTableColumnCreatorInterface
    {
        return new PriceProductTableColumnCreator();
    }

    public function createPriceFieldMapperStrategy(): FieldMapperStrategyInterface
    {
        return new PriceFieldMapperStrategy(
            $this->getPriceProductFacade(),
            $this->getPriceProductVolumeService(),
            $this->getMoneyFacade(),
        );
    }

    public function createCostPriceTableConfigurationExpander(): CostPriceTableConfigurationExpander
    {
        return new CostPriceTableConfigurationExpander(
            $this->getPriceProductFacade(),
        );
    }

    public function createCostPriceProductMapper(): CostPriceProductMapper
    {
        return new CostPriceProductMapper(
            $this->getMoneyFacade(),
        );
    }
}
