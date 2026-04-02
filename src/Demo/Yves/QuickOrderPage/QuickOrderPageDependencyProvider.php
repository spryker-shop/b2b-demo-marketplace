<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\QuickOrderPage;

use Pyz\Yves\QuickOrderPage\QuickOrderPageDependencyProvider as PyzQuickOrderPageDependencyProviderAlias;
use SprykerFeature\Yves\AiCommerce\QuickOrderImageToCart\Plugin\QuickOrderPage\AiCommerceQuickOrderImageToCartFormPlugin;

class QuickOrderPageDependencyProvider extends PyzQuickOrderPageDependencyProviderAlias
{
    /**
     * @return array<\SprykerShop\Yves\QuickOrderPageExtension\Dependency\Plugin\QuickOrderFormPluginInterface>
     */
    protected function getQuickOrderFormPlugins(): array
    {
        return [
            new AiCommerceQuickOrderImageToCartFormPlugin(),
        ];
    }
}
