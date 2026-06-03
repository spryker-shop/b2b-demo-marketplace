<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductMerchantPortalGui;

use Demo\Zed\ProductMerchantPortalGui\Communication\Plugin\ProductMerchantPortalGui\CostPriceProductAbstractTableConfigurationExpanderPlugin;
use Demo\Zed\ProductMerchantPortalGui\Communication\Plugin\ProductMerchantPortalGui\CostPriceProductConcreteTableConfigurationExpanderPlugin;
use Demo\Zed\ProductMerchantPortalGui\Communication\Plugin\ProductMerchantPortalGui\CostPriceProductMapperPlugin;
use Pyz\Zed\ProductMerchantPortalGui\ProductMerchantPortalGuiDependencyProvider as PyzProductMerchantPortalGuiDependencyProvider;

class ProductMerchantPortalGuiDependencyProvider extends PyzProductMerchantPortalGuiDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ProductMerchantPortalGuiExtension\Dependency\Plugin\PriceProductAbstractTableConfigurationExpanderPluginInterface>
     */
    protected function getPriceProductAbstractTableConfigurationExpanderPlugins(): array
    {
        return array_merge(parent::getPriceProductAbstractTableConfigurationExpanderPlugins(), [
            new CostPriceProductAbstractTableConfigurationExpanderPlugin(),
        ]);
    }

    /**
     * @return array<\Spryker\Zed\ProductMerchantPortalGuiExtension\Dependency\Plugin\PriceProductConcreteTableConfigurationExpanderPluginInterface>
     */
    protected function getPriceProductConcreteTableConfigurationExpanderPlugins(): array
    {
        return array_merge(parent::getPriceProductConcreteTableConfigurationExpanderPlugins(), [
            new CostPriceProductConcreteTableConfigurationExpanderPlugin(),
        ]);
    }

    /**
     * @return array<\Spryker\Zed\ProductMerchantPortalGuiExtension\Dependency\Plugin\PriceProductMapperPluginInterface>
     */
    protected function getPriceProductMapperPlugins(): array
    {
        return array_merge(parent::getPriceProductMapperPlugins(), [
            new CostPriceProductMapperPlugin(),
        ]);
    }
}
