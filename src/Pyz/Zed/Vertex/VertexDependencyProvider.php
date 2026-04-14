<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Vertex;

use Spryker\Zed\Calculation\Communication\Plugin\Calculator\ItemTaxAmountFullAggregatorPlugin;
use Spryker\Zed\Calculation\Communication\Plugin\Calculator\PriceToPayAggregatorPlugin;
use Spryker\Zed\MerchantProfile\Communication\Plugin\TaxApp\MerchantProfileAddressCalculableObjectTaxAppExpanderPlugin;
use Spryker\Zed\MerchantProfile\Communication\Plugin\TaxApp\MerchantProfileAddressOrderTaxAppExpanderPlugin;
use Spryker\Zed\ProductOfferAvailability\Communication\Plugin\TaxApp\ProductOfferAvailabilityCalculableObjectTaxAppExpanderPlugin;
use Spryker\Zed\ProductOfferAvailability\Communication\Plugin\TaxApp\ProductOfferAvailabilityOrderTaxAppExpanderPlugin;
use Spryker\Zed\Tax\Communication\Plugin\Calculator\TaxAmountAfterCancellationCalculatorPlugin;
use Spryker\Zed\Tax\Communication\Plugin\Calculator\TaxAmountCalculatorPlugin;
use Spryker\Zed\Tax\Communication\Plugin\Calculator\TaxRateAverageAggregatorPlugin;
use SprykerEco\Zed\Vertex\Communication\Plugin\Order\OrderCustomerWithVertexCodeExpanderPlugin;
use SprykerEco\Zed\Vertex\Communication\Plugin\Order\OrderExpensesWithVertexCodeExpanderPlugin;
use SprykerEco\Zed\Vertex\Communication\Plugin\Order\OrderItemProductOptionWithVertexCodeExpanderPlugin;
use SprykerEco\Zed\Vertex\Communication\Plugin\Order\OrderItemWithVertexSpecificFieldsExpanderPlugin;
use SprykerEco\Zed\Vertex\Communication\Plugin\Quote\CalculableObjectCustomerWithVertexCodeExpanderPlugin;
use SprykerEco\Zed\Vertex\Communication\Plugin\Quote\CalculableObjectExpensesWithVertexCodeExpanderPlugin;
use SprykerEco\Zed\Vertex\Communication\Plugin\Quote\CalculableObjectItemProductOptionWithVertexCodeExpanderPlugin;
use SprykerEco\Zed\Vertex\Communication\Plugin\Quote\CalculableObjectItemWithVertexSpecificFieldsExpanderPlugin;
use SprykerEco\Zed\Vertex\VertexDependencyProvider as SprykerVertexDependencyProvider;

class VertexDependencyProvider extends SprykerVertexDependencyProvider
{
    /**
     * @return array<\SprykerEco\Zed\Vertex\Dependency\Plugin\CalculableObjectVertexExpanderPluginInterface|\Spryker\Zed\TaxAppExtension\Dependency\Plugin\CalculableObjectTaxAppExpanderPluginInterface>
     */
    protected function getCalculableObjectVertexExpanderPlugins(): array
    {
        return [
            new CalculableObjectCustomerWithVertexCodeExpanderPlugin(),
            new CalculableObjectExpensesWithVertexCodeExpanderPlugin(),
            new CalculableObjectItemProductOptionWithVertexCodeExpanderPlugin(),
            new CalculableObjectItemWithVertexSpecificFieldsExpanderPlugin(),
            new MerchantProfileAddressCalculableObjectTaxAppExpanderPlugin(),
            new ProductOfferAvailabilityCalculableObjectTaxAppExpanderPlugin(),
        ];
    }

    /**
     * @return array<\SprykerEco\Zed\Vertex\Dependency\Plugin\OrderVertexExpanderPluginInterface|\Spryker\Zed\TaxAppExtension\Dependency\Plugin\OrderTaxAppExpanderPluginInterface>
     */
    protected function getOrderVertexExpanderPlugins(): array
    {
        return [
            new OrderCustomerWithVertexCodeExpanderPlugin(),
            new OrderExpensesWithVertexCodeExpanderPlugin(),
            new OrderItemProductOptionWithVertexCodeExpanderPlugin(),
            new OrderItemWithVertexSpecificFieldsExpanderPlugin(),
            new MerchantProfileAddressOrderTaxAppExpanderPlugin(),
            new ProductOfferAvailabilityOrderTaxAppExpanderPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Zed\CalculationExtension\Dependency\Plugin\CalculationPluginInterface>
     */
    protected function getFallbackQuoteCalculationPlugins(): array
    {
        return [
            new TaxAmountCalculatorPlugin(),
            new ItemTaxAmountFullAggregatorPlugin(),
            new PriceToPayAggregatorPlugin(),
            new TaxRateAverageAggregatorPlugin(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<\Spryker\Zed\CalculationExtension\Dependency\Plugin\CalculationPluginInterface>
     */
    protected function getFallbackOrderCalculationPlugins(): array
    {
        return [
            new TaxAmountCalculatorPlugin(),
            new ItemTaxAmountFullAggregatorPlugin(),
            new PriceToPayAggregatorPlugin(),
            new TaxAmountAfterCancellationCalculatorPlugin(),
        ];
    }
}
