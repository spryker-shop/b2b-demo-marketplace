<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\ConfigurationProvider;

use Demo\Zed\ProductOfferMerchantPortalGui\Communication\Expander\CostPriceTableConfigurationExpander;
use Spryker\Shared\GuiTable\Configuration\Builder\GuiTableConfigurationBuilderInterface;
use Spryker\Shared\GuiTable\GuiTableFactoryInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\Column\ColumnIdCreatorInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\ConfigurationProvider\PriceProductOfferUpdateGuiTableConfigurationProvider as SprykerPriceProductOfferUpdateGuiTableConfigurationProvider;
use Spryker\Zed\ProductOfferMerchantPortalGui\Dependency\Facade\ProductOfferMerchantPortalGuiToCurrencyFacadeInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Dependency\Facade\ProductOfferMerchantPortalGuiToPriceProductFacadeInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Dependency\Facade\ProductOfferMerchantPortalGuiToStoreFacadeInterface;

class PriceProductOfferUpdateGuiTableConfigurationProvider extends SprykerPriceProductOfferUpdateGuiTableConfigurationProvider
{
    public function __construct(
        GuiTableFactoryInterface $guiTableFactory,
        ProductOfferMerchantPortalGuiToPriceProductFacadeInterface $priceProductFacade,
        ProductOfferMerchantPortalGuiToStoreFacadeInterface $storeFacade,
        ProductOfferMerchantPortalGuiToCurrencyFacadeInterface $currencyFacade,
        ColumnIdCreatorInterface $columnIdCreator,
        protected CostPriceTableConfigurationExpander $costPriceTableConfigurationExpander,
    ) {
        parent::__construct(
            $guiTableFactory,
            $priceProductFacade,
            $storeFacade,
            $currencyFacade,
            $columnIdCreator,
        );
    }

    /**
     * @param \Spryker\Shared\GuiTable\Configuration\Builder\GuiTableConfigurationBuilderInterface $guiTableConfigurationBuilder
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypeTransfers
     *
     * @return \Spryker\Shared\GuiTable\Configuration\Builder\GuiTableConfigurationBuilderInterface
     */
    protected function addColumns(
        GuiTableConfigurationBuilderInterface $guiTableConfigurationBuilder,
        array $priceTypeTransfers,
    ): GuiTableConfigurationBuilderInterface {
        $guiTableConfigurationBuilder = parent::addColumns($guiTableConfigurationBuilder, $priceTypeTransfers);

        return $this->costPriceTableConfigurationExpander->expandWithColumns(
            $guiTableConfigurationBuilder,
            $priceTypeTransfers,
        );
    }

    /**
     * @param \Spryker\Shared\GuiTable\Configuration\Builder\GuiTableConfigurationBuilderInterface $guiTableConfigurationBuilder
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypeTransfers
     *
     * @return \Spryker\Shared\GuiTable\Configuration\Builder\GuiTableConfigurationBuilderInterface
     */
    protected function addEditableColumns(
        GuiTableConfigurationBuilderInterface $guiTableConfigurationBuilder,
        array $priceTypeTransfers,
    ): GuiTableConfigurationBuilderInterface {
        $guiTableConfigurationBuilder = parent::addEditableColumns($guiTableConfigurationBuilder, $priceTypeTransfers);

        return $this->costPriceTableConfigurationExpander->expandWithEditableColumns(
            $guiTableConfigurationBuilder,
            $priceTypeTransfers,
        );
    }
}
