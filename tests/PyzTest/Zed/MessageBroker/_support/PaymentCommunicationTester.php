<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\MessageBroker;

use Codeception\Actor;
use Generated\Shared\Transfer\CurrencyTransfer;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Spryker\Shared\Kernel\Transfer\TransferInterface;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(\PyzTest\Zed\Payment\PHPMD)
 */
class PaymentCommunicationTester extends Actor
{
    use _generated\PaymentCommunicationTesterActions;

    /**
     * @var string
     */
    protected const CURRENCY_USD = 'USD';

    /**
     * @var string
     */
    protected const DEFAULT_OMS_PROCESS_NAME = 'ForeignPaymentStateMachine01';

    public function haveSalesOrder(string $initialItemState): SpySalesOrder
    {
        $this->haveCurrency([CurrencyTransfer::CODE => static::CURRENCY_USD]);

        return $this->haveSalesOrderEntity(
            [],
            [
                'email' => 'test@test.com',
                'currency_iso_code' => static::CURRENCY_USD,
            ],
            $initialItemState,
            static::DEFAULT_OMS_PROCESS_NAME,
        );
    }

    public function havePaymentMessageTransfer(
        string $paymentMessageTransferClassName,
        SpySalesOrder $salesOrderEntity,
    ): TransferInterface {
        return (new $paymentMessageTransferClassName())->setOrderItemIds(
            $this->getSalesOrderItemIds($salesOrderEntity),
        );
    }

    public function handlePaymentMessageTransfer(TransferInterface $paymentMessageTransfer): void
    {
        $channelName = 'payment-commands';
        $this->setupMessageBroker($paymentMessageTransfer::class, $channelName);
        $this->setupMessageBrokerPlugins();
        $messageBrokerFacade = $this->getLocator()->messageBroker()->facade();
        $messageBrokerFacade->sendMessage($paymentMessageTransfer);
        $messageBrokerFacade->startWorker(
            $this->buildMessageBrokerWorkerConfigTransfer([$channelName], 1),
        );
    }

    public function assertOrderHasCorrectState(SpySalesOrder $salesOrder, string $expectedItemState): void
    {
        $this->assertGreaterThan(
            0,
            SpyOmsOrderItemStateQuery::create()->filterByName($expectedItemState)->count(),
            sprintf('OMS order item state "%s" does not exist.', $expectedItemState),
        );

        // COUNT queries read straight from the database: Propel's instance pool still holds the
        // order items as they were hydrated before the message moved them.
        $orderItemCount = SpySalesOrderItemQuery::create()->filterByOrder($salesOrder)->count();
        $this->assertGreaterThan(0, $orderItemCount, 'Sales order has no items to assert on.');

        $itemsInExpectedStateCount = SpySalesOrderItemQuery::create()
            ->filterByOrder($salesOrder)
            ->useStateQuery()
                ->filterByName($expectedItemState)
            ->endUse()
            ->count();

        $this->assertSame(
            $orderItemCount,
            $itemsInExpectedStateCount,
            sprintf(
                'Expected all %d items of order "%s" to be in state "%s", %d are.',
                $orderItemCount,
                $salesOrder->getOrderReference(),
                $expectedItemState,
                $itemsInExpectedStateCount,
            ),
        );
    }

    /**
     * @return list<int>
     */
    protected function getSalesOrderItemIds(SpySalesOrder $salesOrder): array
    {
        $spySalesOrderItemQuery = new SpySalesOrderItemQuery();
        $spySalesOrderItemQuery->filterByOrder($salesOrder);
        $fetchedOrderItemIds = $spySalesOrderItemQuery->find();

        $orderItemIds = [];
        foreach ($fetchedOrderItemIds as $fetchedOrderItemId) {
            $orderItemIds[] = $fetchedOrderItemId->getIdSalesOrderItem();
        }

        return $orderItemIds;
    }
}
