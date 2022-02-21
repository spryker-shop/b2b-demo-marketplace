<?php

namespace Pyz\Zed\SalesMerchantPortalGui;

use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\Plugin\SalesMerchantPortalGui\ProductOfferMerchantOrderItemTableExpanderPlugin;
use Spryker\Zed\SalesMerchantPortalGui\SalesMerchantPortalGuiDependencyProvider as SprykerSalesMerchantPortalGuiDependencyProvider;

class SalesMerchantPortalGuiDependencyProvider extends SprykerSalesMerchantPortalGuiDependencyProvider
{
    /**
     * @return \Spryker\Zed\SalesMerchantPortalGuiExtension\Dependency\Plugin\MerchantOrderItemTableExpanderPluginInterface[]
     */
    protected function getMerchantOrderItemTableExpanderPlugins(): array
    {
        return [
            new ProductOfferMerchantOrderItemTableExpanderPlugin(),
        ];
    }
}
