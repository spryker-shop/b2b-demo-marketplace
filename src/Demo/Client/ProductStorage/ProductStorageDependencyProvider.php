<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\ProductStorage;

use Demo\Client\ProductGrossMargin\Plugin\ProductStorage\ProductViewGrossMarginExpanderPlugin;
use Pyz\Client\ProductStorage\ProductStorageDependencyProvider as PyzProductStorageDependencyProvider;
use Spryker\Client\AvailabilityStorage\Plugin\ProductViewAvailabilityStorageExpanderPlugin;
use Spryker\Client\MerchantProductStorage\Plugin\ProductStorage\ProductViewMerchantProductExpanderPlugin;
use Spryker\Client\PriceProductStorage\Plugin\ProductViewPriceExpanderPlugin;
use Spryker\Client\ProductAttachmentStorage\Plugin\ProductStorage\ProductAttachmentProductViewExpanderPlugin;
use Spryker\Client\ProductBundleStorage\Plugin\ProductStorage\ProductBundleProductViewExpanderPlugin;
use Spryker\Client\ProductConfigurationStorage\Plugin\ProductStorage\ProductViewProductConfigurationExpanderPlugin;
use Spryker\Client\ProductDiscontinuedStorage\Plugin\ProductStorage\ProductDiscontinuedProductAvailabilityExpanderPlugin;
use Spryker\Client\ProductDiscontinuedStorage\Plugin\ProductStorage\ProductViewDiscontinuedOptionsExpanderPlugin;
use Spryker\Client\ProductImageStorage\Plugin\ProductViewImageExpanderPlugin;
use Spryker\Client\ProductMeasurementUnitStorage\Plugin\ProductStorage\ProductViewMeasurementUnitExpanderPlugin;
use Spryker\Client\ProductOfferStorage\Plugin\ProductStorage\ProductViewProductOfferExpanderPlugin;
use Spryker\Client\ProductStorage\Plugin\ProductVariantProductViewExpanderPlugin;
use SprykerFeature\Client\SelfServicePortal\Plugin\ProductStorage\ShipmentTypeProductViewExpanderPlugin;

class ProductStorageDependencyProvider extends PyzProductStorageDependencyProvider
{
    /**
     * @return array<\Spryker\Client\ProductStorage\Dependency\Plugin\ProductViewExpanderPluginInterface>
     */
    protected function getProductViewExpanderPlugins(): array
    {
        /** @var array<\Spryker\Client\ProductStorage\Dependency\Plugin\ProductViewExpanderPluginInterface> $plugins */
        $plugins = [
            new ProductViewDiscontinuedOptionsExpanderPlugin(),
            new ProductVariantProductViewExpanderPlugin(),
            new ProductViewProductOfferExpanderPlugin(),
            new ProductViewMerchantProductExpanderPlugin(),
            new ProductViewProductConfigurationExpanderPlugin(),
            new ProductViewPriceExpanderPlugin(),
            new ProductViewGrossMarginExpanderPlugin(),
            new ShipmentTypeProductViewExpanderPlugin(),
            new ProductViewAvailabilityStorageExpanderPlugin(),
            new ProductDiscontinuedProductAvailabilityExpanderPlugin(),
            new ProductViewImageExpanderPlugin(),
            new ProductBundleProductViewExpanderPlugin(),
            new ProductViewMeasurementUnitExpanderPlugin(),
            new ProductAttachmentProductViewExpanderPlugin(),
        ];

        return $plugins;
    }
}
