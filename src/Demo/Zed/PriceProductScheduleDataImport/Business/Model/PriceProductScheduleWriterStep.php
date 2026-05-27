<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductScheduleDataImport\Business\Model;

use Demo\Zed\PriceProductScheduleDataImport\Business\Model\DataSet\PriceProductScheduleDataSetInterface;
use Orm\Zed\PriceProductSchedule\Persistence\SpyPriceProductSchedule;
use Orm\Zed\PriceProductSchedule\Persistence\SpyPriceProductScheduleQuery;
use Spryker\Zed\DataImport\Business\Exception\DataKeyNotFoundInDataSetException;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\PriceProductScheduleDataImport\Business\Model\DataSet\PriceProductScheduleDataSetInterface as SprykerPriceProductScheduleDataSetInterface;
use Spryker\Zed\PriceProductScheduleDataImport\Business\Model\PriceProductScheduleWriterStep as SprykerPriceProductScheduleWriterStep;

class PriceProductScheduleWriterStep extends SprykerPriceProductScheduleWriterStep
{
    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\DataKeyNotFoundInDataSetException
     *
     * @return void
     */
    public function execute(DataSetInterface $dataSet): void
    {
        if (empty($dataSet[SprykerPriceProductScheduleDataSetInterface::FK_PRODUCT_ABSTRACT]) && empty($dataSet[PriceProductScheduleDataSetInterface::FK_PRODUCT_CONCRETE])) {
            throw new DataKeyNotFoundInDataSetException(sprintf(
                static::EXCEPTION_MESSAGE,
                PriceProductScheduleDataSetInterface::KEY_ABSTRACT_SKU,
                PriceProductScheduleDataSetInterface::KEY_CONCRETE_SKU,
                implode(', ', array_keys($dataSet->getArrayCopy())),
            ));
        }

        $priceProductScheduleEntity = $this->createPriceProductScheduleQuery($dataSet)->findOneOrCreate();

        $this->savePriceProductSchedule($priceProductScheduleEntity, $dataSet);
    }

    protected function createPriceProductScheduleQuery(DataSetInterface $dataSet): SpyPriceProductScheduleQuery
    {
        $priceProductScheduleQuery = SpyPriceProductScheduleQuery::create();

        $priceProductScheduleQuery
            ->filterByFkPriceType($dataSet[PriceProductScheduleDataSetInterface::FK_PRICE_TYPE])
            ->filterByFkStore($dataSet[PriceProductScheduleDataSetInterface::FK_STORE])
            ->filterByFkCurrency($dataSet[PriceProductScheduleDataSetInterface::FK_CURRENCY])
            ->filterByNetPrice($dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_NET])
            ->filterByGrossPrice($dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_GROSS])
            ->filterByActiveFrom($dataSet[PriceProductScheduleDataSetInterface::KEY_INCLUDED_FROM])
            ->filterByActiveTo($dataSet[PriceProductScheduleDataSetInterface::KEY_INCLUDED_TO]);

        if (!empty($dataSet[PriceProductScheduleDataSetInterface::FK_PRODUCT_ABSTRACT])) {
            $priceProductScheduleQuery->filterByFkProductAbstract($dataSet[PriceProductScheduleDataSetInterface::FK_PRODUCT_ABSTRACT]);
        }

        if (!empty($dataSet[PriceProductScheduleDataSetInterface::FK_PRODUCT_CONCRETE])) {
            $priceProductScheduleQuery->filterByFkProduct($dataSet[PriceProductScheduleDataSetInterface::FK_PRODUCT_CONCRETE]);
        }

        return $priceProductScheduleQuery;
    }

    protected function savePriceProductSchedule(
        SpyPriceProductSchedule $priceProductScheduleEntity,
        DataSetInterface $dataSet,
    ): void {
        $rawCostPrice = $dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_COST];
        $costPrice = ($rawCostPrice === '' || $rawCostPrice === null)
            ? null
            : (int)$rawCostPrice;

        $priceProductScheduleEntity
            ->setFkStore($dataSet[PriceProductScheduleDataSetInterface::FK_STORE])
            ->setFkCurrency($dataSet[PriceProductScheduleDataSetInterface::FK_CURRENCY])
            ->setFkPriceProductScheduleList($dataSet[PriceProductScheduleDataSetInterface::FK_PRICE_PRODUCT_SCHEDULE_LIST])
            ->setNetPrice($dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_NET])
            ->setGrossPrice($dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_GROSS])
            ->setCostPrice($costPrice)
            ->setActiveFrom($dataSet[PriceProductScheduleDataSetInterface::KEY_INCLUDED_FROM])
            ->setActiveTo($dataSet[PriceProductScheduleDataSetInterface::KEY_INCLUDED_TO])
            ->setIsCurrent(false)
            ->save();
    }
}
