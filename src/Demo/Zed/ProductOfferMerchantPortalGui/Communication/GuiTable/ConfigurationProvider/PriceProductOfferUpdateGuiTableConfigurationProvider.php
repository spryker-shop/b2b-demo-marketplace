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
use Spryker\Zed\ProductOfferMerchantPortalGui\Dependency\Facade\ProductOfferMerchantPortalGuiToMerchantUserFacadeInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Dependency\Facade\ProductOfferMerchantPortalGuiToPriceProductFacadeInterface;

class PriceProductOfferUpdateGuiTableConfigurationProvider extends SprykerPriceProductOfferUpdateGuiTableConfigurationProvider
{
    public function __construct(
        GuiTableFactoryInterface $guiTableFactory,
        ProductOfferMerchantPortalGuiToPriceProductFacadeInterface $priceProductFacade,
        ProductOfferMerchantPortalGuiToMerchantUserFacadeInterface $merchantUserFacade,
        ProductOfferMerchantPortalGuiToCurrencyFacadeInterface $currencyFacade,
        ColumnIdCreatorInterface $columnIdCreator,
        protected CostPriceTableConfigurationExpander $costPriceTableConfigurationExpander,
    ) {
        parent::__construct(
            $guiTableFactory,
            $priceProductFacade,
            $merchantUserFacade,
            $currencyFacade,
            $columnIdCreator,
        );
    }

    /**
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypeTransfers
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
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypeTransfers
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
