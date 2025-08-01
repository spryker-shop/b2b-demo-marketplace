<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Availability;

use Spryker\Zed\Availability\AvailabilityDependencyProvider as SprykerAvailabilityDependencyProvider;
use Spryker\Zed\Availability\Communication\Plugin\Cart\ProductConcreteBatchAvailabilityStrategyPlugin;
use Spryker\Zed\ProductConfigurationCart\Communication\Plugin\Availability\ProductConfigurationCartItemQuantityCounterStrategyPlugin;
use Spryker\Zed\ProductOffer\Communication\Plugin\Cart\ProductOfferCartItemQuantityCounterStrategyPlugin;
use Spryker\Zed\ProductOfferAvailability\Communication\Plugin\Availability\ProductOfferAvailabilityStrategyPlugin;
use Spryker\Zed\SalesOrderAmendment\Communication\Plugin\Availability\OrderAmendmentQuantityBatchAvailabilityStrategyPlugin;
use SprykerShop\Zed\DateTimeConfiguratorPageExample\Communication\Plugin\Availability\ExampleDateTimeConfiguratorAvailabilityStrategyPlugin;

class AvailabilityDependencyProvider extends SprykerAvailabilityDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\AvailabilityExtension\Dependency\Plugin\BatchAvailabilityStrategyPluginInterface>
     */
    protected function getBatchAvailabilityStrategyPlugins(): array
    {
        return [
            /*
             * ProductConcreteBatchAvailabilityStrategyPlugin needs to be after all other implementations.
             */
            new ProductConcreteBatchAvailabilityStrategyPlugin(),
            /*
             * OrderAmendmentQuantityBatchAvailabilityStrategyPlugin needs to be after all other implementations including ProductConcreteBatchAvailabilityStrategyPlugin.
             */
            new OrderAmendmentQuantityBatchAvailabilityStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\AvailabilityExtension\Dependency\Plugin\AvailabilityStrategyPluginInterface>
     */
    protected function getAvailabilityStrategyPlugins(): array
    {
        return [
            new ProductOfferAvailabilityStrategyPlugin(),
            new ExampleDateTimeConfiguratorAvailabilityStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\AvailabilityExtension\Dependency\Plugin\CartItemQuantityCounterStrategyPluginInterface>
     */
    protected function getCartItemQuantityCounterStrategyPlugins(): array
    {
        return [
            new ProductConfigurationCartItemQuantityCounterStrategyPlugin(),
            new ProductOfferCartItemQuantityCounterStrategyPlugin(),
        ];
    }
}
