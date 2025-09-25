<?php

namespace Go\Zed\Oms\Persistence;

use DateTime;
use Generated\Shared\Transfer\OrderMatrixCriteriaTransfer;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderItemStateTableMap;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderProcessTableMap;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemTableMap;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Propel\Runtime\ActiveQuery\Criteria;

class OmsRepository extends \Spryker\Zed\Oms\Persistence\OmsRepository
{
    /**
     * @param \Generated\Shared\Transfer\OrderMatrixCriteriaTransfer $orderMatrixCriteriaTransfer
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery
     */
    protected function getOrderMatrixSubquery(OrderMatrixCriteriaTransfer $orderMatrixCriteriaTransfer): SpySalesOrderItemQuery
    {
        /** @var \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery $subQuery */
        $subQuery = $this->getFactory()
            ->getSalesOrderItemPropelQuery();

        /** @var \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery $subQuery */
        $subQuery = $subQuery->filterByFkOmsOrderProcess($orderMatrixCriteriaTransfer->getOrderMatrixConditions()->getProcessIds(), Criteria::IN)
            ->useStateQuery()
            ->withColumn(SpyOmsOrderItemStateTableMap::COL_NAME, static::STATE_NAME)
            ->withColumn(SpyOmsOrderItemStateTableMap::COL_TENANT_REFERENCE, 'tenant_reference')
            ->endUse();

        $subQuery->useProcessQuery()
            ->withColumn(SpyOmsOrderProcessTableMap::COL_NAME, static::PROCESS_NAME)
            ->endUse()
            ->withColumn(sprintf(
                static::DATE_CASE_EXPRESSION,
                SpySalesOrderItemTableMap::COL_LAST_STATE_CHANGE,
                (new DateTime('-1 day'))->format(static::DATE_FORMAT),
                SpySalesOrderItemTableMap::COL_LAST_STATE_CHANGE,
                (new DateTime('-7 day'))->format(static::DATE_FORMAT),
            ), static::DATE_WINDOW)
            ->withColumn(SpySalesOrderItemTableMap::COL_FK_OMS_ORDER_PROCESS, static::FK_OMS_ORDER_PROCESS)
            ->withColumn(SpySalesOrderItemTableMap::COL_FK_OMS_ORDER_ITEM_STATE, static::FK_OMS_ORDER_ITEM_STATE);

        if ($orderMatrixCriteriaTransfer->getPagination() && $orderMatrixCriteriaTransfer->getPagination()->getLimit()) {
            $subQuery->limit($orderMatrixCriteriaTransfer->getPagination()->getLimit())
                ->offset($orderMatrixCriteriaTransfer->getPagination()->getOffset());
        }
        $stateBlackList = $this->getFactory()->getConfig()->getStateBlacklist();

        if ($stateBlackList) {
            $subQuery->useStateQuery()
                ->filterByName($stateBlackList, Criteria::NOT_IN)
                ->endUse();
        }

        return $subQuery;
    }
}
