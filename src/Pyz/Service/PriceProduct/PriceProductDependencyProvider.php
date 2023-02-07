<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Service\PriceProduct;

use Spryker\Service\PriceProduct\PriceProductDependencyProvider as SprykerPriceProductDependencyProvider;
use Spryker\Service\PriceProductMerchantRelationship\Plugin\PriceProduct\MerchantRelationshipPreBuildPriceProductGroupKeyPlugin;
use Spryker\Service\PriceProductMerchantRelationship\Plugin\PriceProduct\MerchantRelationshipPriceProductFilterPlugin;
use Spryker\Service\PriceProductOffer\Plugin\PriceProduct\PriceProductOfferPriceProductFilterPlugin;
use Spryker\Service\PriceProductOfferVolume\Plugin\PriceProductOffer\PriceProductOfferVolumeFilterPlugin;
use Spryker\Service\PriceProductVolume\Plugin\PriceProductExtension\PriceProductVolumeFilterPlugin;
use Spryker\Service\ProductConfiguration\Plugin\PriceProduct\ProductConfigurationPriceProductFilterPlugin;
use Spryker\Service\ProductConfiguration\Plugin\PriceProduct\ProductConfigurationVolumePriceProductFilterPlugin;

class PriceProductDependencyProvider extends SprykerPriceProductDependencyProvider
{
    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Service\PriceProductExtension\Dependency\Plugin\PriceProductFilterPluginInterface>
     */
    protected function getPriceProductDecisionPlugins(): array
    {
        return array_merge([
            /*
             * MerchantRelationshipPriceProductFilterPlugin should be at the beginning to filter non-active merchant prices
             * and define right minimum price in next filter plugins like in `PriceProductVolumeFilterPlugin`.
             */
            new MerchantRelationshipPriceProductFilterPlugin(),
            new PriceProductOfferPriceProductFilterPlugin(),
            new PriceProductOfferVolumeFilterPlugin(),
            new PriceProductVolumeFilterPlugin(),
            new ProductConfigurationPriceProductFilterPlugin(),
            new ProductConfigurationVolumePriceProductFilterPlugin(),
        ], parent::getPriceProductDecisionPlugins());
    }

    /**
     * @return array<int, \Spryker\Service\PriceProductExtension\Dependency\Plugin\PreBuildPriceProductGroupKeyPluginInterface>
     */
    protected function getPreBuildPriceProductGroupKeyPlugins(): array
    {
        return [
            new MerchantRelationshipPreBuildPriceProductGroupKeyPlugin(),
        ];
    }
}
