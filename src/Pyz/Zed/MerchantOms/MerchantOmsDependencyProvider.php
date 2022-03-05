<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\MerchantOms;

use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\CancelMarketplaceOrderItemCommandPlugin;
use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\DeliverMarketplaceOrderItemCommandPlugin;
use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\ShipByMerchantMarketplaceOrderItemCommandPlugin;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\MerchantOms\MerchantOmsDependencyProvider as SprykerMerchantOmsDependencyProvider;

class MerchantOmsDependencyProvider extends SprykerMerchantOmsDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_OMS = 'FACADE_OMS';

    /**
     * @var string
     */
    public const FACADE_SALES_RETURN = 'FACADE_SALES_RETURN';

    protected function getStateMachineCommandPlugins(): array
    {
        return [
            'MarketplaceOrder/ShipOrderItem' => new ShipByMerchantMarketplaceOrderItemCommandPlugin(),
            'MarketplaceOrder/DeliverOrderItem' => new DeliverMarketplaceOrderItemCommandPlugin(),
            'MarketplaceOrder/CancelOrderItem' => new CancelMarketplaceOrderItemCommandPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);

        $container = $this->addOmsFacade($container);
        $container = $this->addSalesReturnFacade($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function addOmsFacade(Container $container): Container
    {
        $container->set(static::FACADE_OMS, function (Container $container) {
            return $container->getLocator()->oms()->facade();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function addSalesReturnFacade(Container $container): Container
    {
        $container->set(static::FACADE_SALES_RETURN, function (Container $container) {
            return $container->getLocator()->salesReturn()->facade();
        });

        return $container;
    }
}
