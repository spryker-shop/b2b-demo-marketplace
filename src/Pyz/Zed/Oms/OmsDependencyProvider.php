<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Oms;

use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\CloseMerchantOrderItemCommandPlugin;
use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\ReturnMerchantOrderItemCommandPlugin;
use Pyz\Zed\MerchantSalesOrder\Communication\Plugin\Oms\Condition\IsOrderPaidConditionPlugin;
use Pyz\Zed\MerchantSalesOrder\Communication\Plugin\Oms\CreateMerchantOrdersCommandPlugin;
use Pyz\Zed\Oms\Communication\Plugin\Oms\InitiationTimeoutProcessorPlugin;
use Spryker\Zed\Availability\Communication\Plugin\Oms\AvailabilityReservationPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\DummyPayment\Communication\Plugin\Oms\Command\RefundPlugin;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\SendOrderConfirmationPlugin;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\SendOrderShippedPlugin;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\SendOrderStatusChangedMessagePlugin;
use Spryker\Zed\Oms\Communication\Plugin\Oms\ReservationHandler\ReservationVersionPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandCollectionInterface;
use Spryker\Zed\Oms\Dependency\Plugin\Condition\ConditionCollectionInterface;
use Spryker\Zed\Oms\OmsDependencyProvider as SprykerOmsDependencyProvider;
use Spryker\Zed\OmsProductOfferReservation\Communication\Plugin\Oms\ProductOfferOmsReservationAggregationPlugin;
use Spryker\Zed\OmsProductOfferReservation\Communication\Plugin\Oms\ProductOfferOmsReservationReaderStrategyPlugin;
use Spryker\Zed\OmsProductOfferReservation\Communication\Plugin\Oms\ProductOfferOmsReservationWriterStrategyPlugin;
use Spryker\Zed\OmsProductOfferReservation\Communication\Plugin\Oms\ProductOfferReservationPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Oms\ProductBundleReservationPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\ProductOfferPackagingUnit\Communication\Plugin\Oms\ProductOfferPackagingUnitOmsReservationAggregationPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Oms\ProductPackagingUnitOmsReservationAggregationPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Reservation\LeadProductReservationPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\Refund\Communication\Plugin\Oms\RefundCommandPlugin;
use Spryker\Zed\SalesInvoice\Communication\Plugin\Oms\GenerateOrderInvoiceCommandPlugin;
use Spryker\Zed\SalesMerchantCommission\Communication\Plugin\Oms\Command\SalesMerchantCommissionCalculationCommandByOrderPlugin;
use Spryker\Zed\SalesOrderAmendmentOms\Communication\Plugin\Oms\DeleteOrderAmendmentQuoteCommandByOrderPlugin;
use Spryker\Zed\SalesOrderAmendmentOms\Communication\Plugin\Oms\UpdateDeletedItemReservationCommandByOrderPlugin;
use Spryker\Zed\SalesPayment\Communication\Plugin\Oms\SendCancelPaymentMessageCommandPlugin;
use Spryker\Zed\SalesPayment\Communication\Plugin\Oms\SendCapturePaymentMessageCommandPlugin;
use Spryker\Zed\SalesPayment\Communication\Plugin\Oms\SendRefundPaymentMessageCommandPlugin;
use Spryker\Zed\SalesPaymentMerchant\Communication\Plugin\Oms\Command\MerchantPayoutCommandByOrderPlugin;
use Spryker\Zed\SalesPaymentMerchant\Communication\Plugin\Oms\Command\MerchantPayoutReverseCommandByOrderPlugin;
use Spryker\Zed\SalesPaymentMerchant\Communication\Plugin\Oms\Condition\IsMerchantPaidOutConditionPlugin;
use Spryker\Zed\SalesPaymentMerchant\Communication\Plugin\Oms\Condition\IsMerchantPayoutReversedConditionPlugin;
use Spryker\Zed\SalesReturn\Communication\Plugin\Oms\Command\StartReturnCommandPlugin;
use Spryker\Zed\Shipment\Dependency\Plugin\Oms\ShipmentManualEventGrouperPlugin;
use Spryker\Zed\Shipment\Dependency\Plugin\Oms\ShipmentOrderMailExpanderPlugin;
use Spryker\Zed\TaxApp\Communication\Plugin\Oms\Command\SubmitPaymentTaxInvoicePlugin;
use Spryker\Zed\TaxApp\Communication\Plugin\Oms\OrderRefundedEventListenerPlugin;

class OmsDependencyProvider extends SprykerOmsDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_TRANSLATOR = 'FACADE_TRANSLATOR';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->extendConditionPlugins($container);
        $container = $this->extendCommandPlugins($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function extendConditionPlugins(Container $container): Container
    {
        $container->extend(self::CONDITION_PLUGINS, function (ConditionCollectionInterface $conditionCollection) {
            $conditionCollection->add(new IsOrderPaidConditionPlugin(), 'MerchantSalesOrder/IsOrderPaid');
            $conditionCollection->add(new IsMerchantPaidOutConditionPlugin(), 'SalesPaymentMerchant/IsMerchantPaidOut');
            $conditionCollection->add(new IsMerchantPayoutReversedConditionPlugin(), 'SalesPaymentMerchant/IsMerchantPayoutReversed');

            return $conditionCollection;
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationPostSaveTerminationAwareStrategyPluginInterface>
     */
    protected function getReservationPostSaveTerminationAwareStrategyPlugins(): array
    {
        return [
            new ProductOfferReservationPostSaveTerminationAwareStrategyPlugin(),
            new ReservationVersionPostSaveTerminationAwareStrategyPlugin(),
            new AvailabilityReservationPostSaveTerminationAwareStrategyPlugin(),
            new ProductBundleReservationPostSaveTerminationAwareStrategyPlugin(),
            new LeadProductReservationPostSaveTerminationAwareStrategyPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsOrderMailExpanderPluginInterface>
     */
    protected function getOmsOrderMailExpanderPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ShipmentOrderMailExpanderPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsManualEventGrouperPluginInterface>
     */
    protected function getOmsManualEventGrouperPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ShipmentManualEventGrouperPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationAggregationPluginInterface>
     */
    protected function getOmsReservationAggregationPlugins(): array
    {
        return [
            new ProductOfferPackagingUnitOmsReservationAggregationPlugin(),
            new ProductOfferOmsReservationAggregationPlugin(),
            new ProductPackagingUnitOmsReservationAggregationPlugin(),
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
        $container = $this->addTranslatorFacade($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addTranslatorFacade(Container $container): Container
    {
        $container->set(static::FACADE_TRANSLATOR, function (Container $container) {
            return $container->getLocator()->translator()->facade();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\TimeoutProcessorPluginInterface>
     */
    protected function getTimeoutProcessorPlugins(): array
    {
        return [
            new InitiationTimeoutProcessorPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationWriterStrategyPluginInterface>
     */
    protected function getOmsReservationWriterStrategyPlugins(): array
    {
        return [
            new ProductOfferOmsReservationWriterStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationReaderStrategyPluginInterface>
     */
    protected function getOmsReservationReaderStrategyPlugins(): array
    {
        return [
            new ProductOfferOmsReservationReaderStrategyPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function extendCommandPlugins(Container $container): Container
    {
        $container->extend(self::COMMAND_PLUGINS, function (CommandCollectionInterface $commandCollection) {
            $commandCollection->add(new SendOrderConfirmationPlugin(), 'Oms/SendOrderConfirmation');
            $commandCollection->add(new SendOrderShippedPlugin(), 'Oms/SendOrderShipped');
            $commandCollection->add(new CreateMerchantOrdersCommandPlugin(), 'MerchantSalesOrder/CreateOrders');
            $commandCollection->add(new CloseMerchantOrderItemCommandPlugin(), 'MerchantOms/CloseOrderItem');
            $commandCollection->add(new StartReturnCommandPlugin(), 'Return/StartReturn');
            $commandCollection->add(new GenerateOrderInvoiceCommandPlugin(), 'Invoice/Generate');
            $commandCollection->add(new ReturnMerchantOrderItemCommandPlugin(), 'MerchantOms/ReturnOrderItem');
            $commandCollection->add(new RefundPlugin(), 'DummyPayment/Refund');
            $commandCollection->add(new SendOrderStatusChangedMessagePlugin(), 'Order/RequestProductReviews');
            $commandCollection->add(new SubmitPaymentTaxInvoicePlugin(), 'TaxApp/SubmitPaymentTaxInvoice');
            $commandCollection->add(new SendCapturePaymentMessageCommandPlugin(), 'Payment/Capture');
            $commandCollection->add(new SendRefundPaymentMessageCommandPlugin(), 'Payment/Refund');
            $commandCollection->add(new SendCancelPaymentMessageCommandPlugin(), 'Payment/Cancel');
            $commandCollection->add(new RefundCommandPlugin(), 'Payment/Refund/Confirm');
            $commandCollection->add(new SalesMerchantCommissionCalculationCommandByOrderPlugin(), 'MerchantCommission/Calculate');
            $commandCollection->add(new SalesMerchantCommissionCalculationCommandByOrderPlugin(), 'MerchantCommission/Calculate');
            $commandCollection->add(new MerchantPayoutCommandByOrderPlugin(), 'SalesPaymentMerchant/Payout');
            $commandCollection->add(new MerchantPayoutReverseCommandByOrderPlugin(), 'SalesPaymentMerchant/ReversePayout');
            $commandCollection->add(new UpdateDeletedItemReservationCommandByOrderPlugin(), 'OrderAmendment/UnreserveDeletedItems');
            $commandCollection->add(new DeleteOrderAmendmentQuoteCommandByOrderPlugin(), 'OrderAmendment/StartGracePeriod');

            return $commandCollection;
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsEventTriggeredListenerPluginInterface>
     */
    protected function getOmsEventTriggeredListenerPlugins(): array
    {
        return [
            new OrderRefundedEventListenerPlugin(),
        ];
    }
}
