<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductOfferMerchantPortalGui\Communication;

use Demo\Zed\ProductOfferMerchantPortalGui\Communication\Expander\CostPriceTableConfigurationExpander;
use Demo\Zed\ProductOfferMerchantPortalGui\Communication\Expander\PriceProductsVolumeDataExpander;
use Demo\Zed\ProductOfferMerchantPortalGui\Communication\Form\Transformer\PriceProductOfferTransformer;
use Demo\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\ConfigurationProvider\PriceProductOfferCreateGuiTableConfigurationProvider;
use Demo\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\ConfigurationProvider\PriceProductOfferUpdateGuiTableConfigurationProvider;
use Demo\Zed\ProductOfferMerchantPortalGui\Communication\Mapper\PriceProductOfferMapper;
use Demo\Zed\ProductOfferMerchantPortalGui\Communication\Mapper\PriceProductOfferTableDataMapper;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Expander\PriceProductsVolumeDataExpanderInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\ConfigurationProvider\PriceProductOfferCreateGuiTableConfigurationProviderInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\ConfigurationProvider\PriceProductOfferUpdateGuiTableConfigurationProviderInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Mapper\PriceProductOfferMapper as SprykerPriceProductOfferMapper;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Mapper\PriceProductOfferTableDataMapperInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\ProductOfferMerchantPortalGuiCommunicationFactory as SprykerProductOfferMerchantPortalGuiCommunicationFactory;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @method \Spryker\Zed\ProductOfferMerchantPortalGui\ProductOfferMerchantPortalGuiConfig getConfig()
 * @method \Spryker\Zed\ProductOfferMerchantPortalGui\Persistence\ProductOfferMerchantPortalGuiRepositoryInterface getRepository()
 */
class ProductOfferMerchantPortalGuiCommunicationFactory extends SprykerProductOfferMerchantPortalGuiCommunicationFactory
{
    /**
     * @return \Spryker\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\ConfigurationProvider\PriceProductOfferCreateGuiTableConfigurationProviderInterface
     */
    public function createPriceProductOfferCreateGuiTableConfigurationProvider(): PriceProductOfferCreateGuiTableConfigurationProviderInterface
    {
        return new PriceProductOfferCreateGuiTableConfigurationProvider(
            $this->getGuiTableFactory(),
            $this->getPriceProductFacade(),
            $this->getStoreFacade(),
            $this->getCurrencyFacade(),
            $this->createColumnIdCreator(),
            $this->createCostPriceTableConfigurationExpander(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\ConfigurationProvider\PriceProductOfferUpdateGuiTableConfigurationProviderInterface
     */
    public function createPriceProductOfferUpdateGuiTableConfigurationProvider(): PriceProductOfferUpdateGuiTableConfigurationProviderInterface
    {
        return new PriceProductOfferUpdateGuiTableConfigurationProvider(
            $this->getGuiTableFactory(),
            $this->getPriceProductFacade(),
            $this->getStoreFacade(),
            $this->getCurrencyFacade(),
            $this->createColumnIdCreator(),
            $this->createCostPriceTableConfigurationExpander(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Mapper\PriceProductOfferTableDataMapperInterface
     */
    public function createPriceProductOfferTableDataMapper(): PriceProductOfferTableDataMapperInterface
    {
        return new PriceProductOfferTableDataMapper(
            $this->getPriceProductFacade(),
            $this->getStoreFacade(),
            $this->createColumnIdCreator(),
        );
    }

    /**
     * @param int|null $idProductOffer
     *
     * @return \Symfony\Component\Form\DataTransformerInterface
     */
    public function createPriceProductOfferTransformer(?int $idProductOffer = null): DataTransformerInterface
    {
        return new PriceProductOfferTransformer(
            $this->getUtilEncodingService(),
            $this->getPriceProductFacade(),
            $this->getCurrencyFacade(),
            $this->getMoneyFacade(),
            $this->createPriceProductToPriceProductOfferMerger(),
            $this->createColumnIdCreator(),
            $this->createPriceProductOfferDataProvider(),
            $idProductOffer,
        );
    }

    /**
     * @return \Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Mapper\PriceProductOfferMapper
     */
    public function createPriceProductOfferMapper(): SprykerPriceProductOfferMapper
    {
        return new PriceProductOfferMapper(
            $this->getPriceProductFacade(),
            $this->getMoneyFacade(),
            $this->getPriceProductOfferVolumeFacade(),
            $this->getPriceProductVolumeService(),
            $this->createPriceProductOfferPropertyPathAnalyzer(),
            $this->createColumnIdCreator(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Expander\PriceProductsVolumeDataExpanderInterface
     */
    public function createPriceProductsVolumeDataExpander(): PriceProductsVolumeDataExpanderInterface
    {
        return new PriceProductsVolumeDataExpander(
            $this->getPriceProductVolumeService(),
            $this->createPriceProductOfferMapper(),
            $this->getPriceProductOfferVolumeFacade(),
            $this->createPriceProductFilter(),
            $this->createPriceProductOfferDataProvider(),
        );
    }

    public function createCostPriceTableConfigurationExpander(): CostPriceTableConfigurationExpander
    {
        return new CostPriceTableConfigurationExpander(
            $this->createColumnIdCreator(),
        );
    }
}
