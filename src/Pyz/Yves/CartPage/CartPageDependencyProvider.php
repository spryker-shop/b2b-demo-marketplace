<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\CartPage;

use SprykerFeature\Yves\SspServiceManagement\Plugin\CartPage\ProductOfferPreAddToCartPlugin;
use SprykerFeature\Yves\SspServiceManagement\Plugin\CartPage\ServiceDateTimePreAddToCartPlugin;
use SprykerFeature\Yves\SspServiceManagement\Plugin\CartPage\ServicePointPreAddToCartPlugin;
use SprykerFeature\Yves\SspServiceManagement\Plugin\CartPage\ShipmentTypePreAddToCartPlugin;
use SprykerShop\Yves\CartPage\CartPageDependencyProvider as SprykerCartPageDependencyProvider;
use SprykerShop\Yves\DiscountPromotionWidget\Plugin\CartPage\DiscountPromotionAddToCartFormWidgetParameterExpanderPlugin;
use SprykerShop\Yves\MerchantProductOfferWidget\Plugin\CartPage\MerchantProductOfferPreAddToCartPlugin;
use SprykerShop\Yves\MerchantProductWidget\Plugin\CartPage\MerchantProductPreAddToCartPlugin;
use SprykerShop\Yves\MultiCartWidget\Plugin\CartPage\MultiCartMiniCartViewExpanderPlugin;
use SprykerShop\Yves\ProductBundleWidget\Plugin\CartPage\ProductBundleCartItemTransformerPlugin;
use SprykerShop\Yves\UrlPage\Plugin\CartPage\UrlCartItemTransformerPlugin;

class CartPageDependencyProvider extends SprykerCartPageDependencyProvider
{
    /**
     * @return array<\SprykerShop\Yves\CartPageExtension\Dependency\Plugin\CartItemTransformerPluginInterface>
     */
    protected function getCartItemTransformerPlugins(): array
    {
        return [
            new ProductBundleCartItemTransformerPlugin(),
            new UrlCartItemTransformerPlugin(),
        ];
    }

    /**
     * @return array<\SprykerShop\Yves\CartPageExtension\Dependency\Plugin\AddToCartFormWidgetParameterExpanderPluginInterface>
     */
    protected function getAddToCartFormWidgetParameterExpanderPlugins(): array
    {
        return [
            new DiscountPromotionAddToCartFormWidgetParameterExpanderPlugin(),
        ];
    }

    /**
     * @return array<\SprykerShop\Yves\CartPageExtension\Dependency\Plugin\PreAddToCartPluginInterface>
     */
    protected function getPreAddToCartPlugins(): array
    {
        return [
            new MerchantProductPreAddToCartPlugin(),
            new MerchantProductOfferPreAddToCartPlugin(),
            new ProductOfferPreAddToCartPlugin(),
            new ServicePointPreAddToCartPlugin(),
            new ShipmentTypePreAddToCartPlugin(),
            new ServiceDateTimePreAddToCartPlugin(),
        ];
    }

    /**
     * @return array<\SprykerShop\Yves\CartPageExtension\Dependency\Plugin\MiniCartViewExpanderPluginInterface>
     */
    protected function getMiniCartViewExpanderPlugins(): array
    {
        return [
            new MultiCartMiniCartViewExpanderPlugin(),
        ];
    }
}
