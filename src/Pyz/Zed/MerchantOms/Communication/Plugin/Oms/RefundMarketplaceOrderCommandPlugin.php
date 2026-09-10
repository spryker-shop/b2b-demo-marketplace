<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\MerchantOms\Communication\Plugin\Oms;

use Generated\Shared\Transfer\MerchantOrderItemCriteriaTransfer;
use LogicException;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\StateMachine\Dependency\Plugin\CommandByItemsPluginInterface;

/**
 * @method \Pyz\Zed\MerchantOms\MerchantOmsConfig getConfig()
 * @method \Pyz\Zed\MerchantOms\Communication\MerchantOmsCommunicationFactory getFactory()
 * @method \Spryker\Zed\MerchantOms\Business\MerchantOmsFacadeInterface getFacade()
 */
class RefundMarketplaceOrderCommandPlugin extends AbstractPlugin implements CommandByItemsPluginInterface
{
    protected const string EVENT_REFUND = 'refund';

    /**
     * {@inheritDoc}
     * - Triggers the refund event once for the order items of all given merchant order items.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\StateMachineItemTransfer> $stateMachineItemTransfers
     *
     * @return void
     */
    public function run(array $stateMachineItemTransfers): void
    {
        $orderItemIds = $this->getOrderItemIds($stateMachineItemTransfers);

        if (!$orderItemIds) {
            return;
        }

        $result = $this->getFactory()
            ->getOmsFacade()
            ->triggerEventForOrderItems(static::EVENT_REFUND, $orderItemIds);

        if ($result === null) {
            throw new LogicException(sprintf(
                'Sales order items #%s transition for event "%s" has not happened.',
                implode(', ', $orderItemIds),
                static::EVENT_REFUND,
            ));
        }
    }

    /**
     * @param array<\Generated\Shared\Transfer\StateMachineItemTransfer> $stateMachineItemTransfers
     *
     * @return array<int>
     */
    protected function getOrderItemIds(array $stateMachineItemTransfers): array
    {
        $orderItemIds = [];

        foreach ($stateMachineItemTransfers as $stateMachineItemTransfer) {
            $merchantOrderItemTransfer = $this->getFactory()->getMerchantSalesOrderFacade()->findMerchantOrderItem(
                (new MerchantOrderItemCriteriaTransfer())
                    ->setIdMerchantOrderItem($stateMachineItemTransfer->getIdentifier()),
            );

            if (!$merchantOrderItemTransfer) {
                continue;
            }

            $orderItemIds[] = $merchantOrderItemTransfer->getIdOrderItemOrFail();
        }

        return $orderItemIds;
    }
}
