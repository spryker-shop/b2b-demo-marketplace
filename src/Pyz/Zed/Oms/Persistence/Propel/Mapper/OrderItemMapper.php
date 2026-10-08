<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Pyz\Zed\Oms\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\ItemStateTransfer;
use Propel\Runtime\Collection\Collection;
use Spryker\Zed\Oms\Persistence\Propel\Mapper\OrderItemMapper as SprykerOrderItemMapper;

class OrderItemMapper extends SprykerOrderItemMapper
{
        public function mapOmsOrderItemStateHistoryEntityCollectionToItemStateHistoryTransfers(
        Collection $omsOrderItemStateHistoryEntities
    ): array {
        $itemStateTransfers = [];

        foreach ($omsOrderItemStateHistoryEntities as $omsOrderItemStateHistory) {
            // TODO: Some unknown issue
            if (!$omsOrderItemStateHistory->getOrderItem()) {
                continue;
            }

            $itemStateTransfers[] = (new ItemStateTransfer())
                ->fromArray($omsOrderItemStateHistory->toArray(), true)
                ->setName($omsOrderItemStateHistory->getState()->getName())
                ->setIdSalesOrderItem($omsOrderItemStateHistory->getFkSalesOrderItem())
                ->setIdSalesOrder($omsOrderItemStateHistory->getOrderItem()->getFkSalesOrder());
        }
        return $itemStateTransfers;
    }
}
