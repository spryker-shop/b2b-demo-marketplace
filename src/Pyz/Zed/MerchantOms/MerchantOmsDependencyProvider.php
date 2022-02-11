<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\MerchantOms;

use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\CancelMarketplaceOrderItemCommandPlugin;
use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\DeliverMarketplaceOrderItemCommandPlugin;
use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\ShipByMerchantMarketplaceOrderItemCommandPlugin;
use Spryker\Zed\MerchantOms\MerchantOmsDependencyProvider as SprykerMerchantOmsDependencyProvider;

class MerchantOmsDependencyProvider extends SprykerMerchantOmsDependencyProvider
{
    protected function getStateMachineCommandPlugins(): array
    {
        return [
            'MarketplaceOrder/ShipOrderItem' => new ShipByMerchantMarketplaceOrderItemCommandPlugin(),
            'MarketplaceOrder/DeliverOrderItem' => new DeliverMarketplaceOrderItemCommandPlugin(),
            'MarketplaceOrder/CancelOrderItem' => new CancelMarketplaceOrderItemCommandPlugin(),
        ];
    }
}
