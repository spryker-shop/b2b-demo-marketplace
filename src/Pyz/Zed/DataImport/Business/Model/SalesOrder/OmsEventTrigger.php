<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\SalesOrder;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\MerchantOmsTriggerRequestTransfer;
use Generated\Shared\Transfer\MerchantOrderItemCriteriaTransfer;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\MerchantOms\Business\MerchantOmsFacadeInterface;
use Spryker\Zed\MerchantSalesOrder\Business\MerchantSalesOrderFacadeInterface;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;

class OmsEventTrigger
{
    protected const OMS_EVENTS_SEPARATOR = ';';

    protected const MERCHANT_OMS_EVENT_PREFIX = 'merchant:';

    public function __construct(
        protected OmsFacadeInterface $omsFacade,
        protected MerchantSalesOrderFacadeInterface $merchantSalesOrderFacade,
        protected MerchantOmsFacadeInterface $merchantOmsFacade,
    ) {
    }

    public function triggerOmsEvents(CheckoutResponseTransfer $checkoutResponseTransfer, DataSetInterface $dataSet): void
    {
        $salesOrderItemIds = [];

        foreach ($checkoutResponseTransfer->getSaveOrderOrFail()->getOrderItems() as $itemTransfer) {
            $salesOrderItemIds[] = $itemTransfer->getIdSalesOrderItemOrFail();
        }

        $this->omsFacade->checkConditions();

        foreach ($this->parseOmsEvents($dataSet) as $omsEvent) {
            if (str_starts_with($omsEvent, static::MERCHANT_OMS_EVENT_PREFIX)) {
                $this->triggerMerchantOmsEvent(
                    substr($omsEvent, strlen(static::MERCHANT_OMS_EVENT_PREFIX)),
                    $salesOrderItemIds,
                );
            } else {
                $this->omsFacade->triggerEventForOrderItems($omsEvent, $salesOrderItemIds);
            }

            $this->omsFacade->checkConditions();
        }
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @return array<string>
     */
    protected function parseOmsEvents(DataSetInterface $dataSet): array
    {
        $omsEvents = [];

        foreach (explode(static::OMS_EVENTS_SEPARATOR, (string)$dataSet[SalesOrderDataSetInterface::COLUMN_OMS_EVENTS]) as $omsEvent) {
            $omsEvent = trim($omsEvent);

            if ($omsEvent === '') {
                continue;
            }

            $omsEvents[] = $omsEvent;
        }

        return $omsEvents;
    }

    /**
     * @param string $merchantOmsEvent
     * @param array<int> $salesOrderItemIds
     *
     * @return void
     */
    protected function triggerMerchantOmsEvent(string $merchantOmsEvent, array $salesOrderItemIds): void
    {
        $merchantOrderItemCollectionTransfer = $this->merchantSalesOrderFacade->getMerchantOrderItemCollection(
            (new MerchantOrderItemCriteriaTransfer())->setOrderItemIds($salesOrderItemIds),
        );

        $merchantOmsTriggerRequestTransfer = (new MerchantOmsTriggerRequestTransfer())
            ->setMerchantOmsEventName($merchantOmsEvent);

        foreach ($merchantOrderItemCollectionTransfer->getMerchantOrderItems() as $merchantOrderItemTransfer) {
            $merchantOmsTriggerRequestTransfer->addMerchantOrderItem($merchantOrderItemTransfer);
        }

        if (!count($merchantOmsTriggerRequestTransfer->getMerchantOrderItems())) {
            return;
        }

        $this->merchantOmsFacade->triggerEventForMerchantOrderItems($merchantOmsTriggerRequestTransfer);
    }
}
