<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductDataImport\Business\Model;

use Demo\Zed\PriceProductDataImport\Business\Model\DataSet\PriceProductDataSet;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProduct;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStore;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStoreQuery;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\PriceProductDataImport\Business\Model\PriceProductWriterStep as SprykerPriceProductWriterStep;

class PriceProductWriterStep extends SprykerPriceProductWriterStep
{
    protected function getPriceProductStoreEntityWithDefaultDimension(
        DataSetInterface $dataSet,
        SpyPriceProduct $productPriceEntity,
    ): SpyPriceProductStore {
        $priceProductStoreEntity = SpyPriceProductStoreQuery::create()
            ->filterByFkStore($dataSet[PriceProductDataSet::ID_STORE])
            ->filterByFkCurrency($dataSet[PriceProductDataSet::ID_CURRENCY])
            ->filterByFkPriceProduct($productPriceEntity->getPrimaryKey())
            ->joinPriceProductDefault()
            ->findOne();

        $rawCostPrice = $dataSet[PriceProductDataSet::KEY_PRICE_COST];
        $costPrice = ($rawCostPrice === '' || $rawCostPrice === null)
            ? null
            : (int)$rawCostPrice;

        if (
            $priceProductStoreEntity
            && $priceProductStoreEntity->getGrossPrice() === (int)$dataSet[PriceProductDataSet::KEY_PRICE_GROSS]
            && $priceProductStoreEntity->getNetPrice() === (int)$dataSet[PriceProductDataSet::KEY_PRICE_NET]
            && $priceProductStoreEntity->getCostPrice() === $costPrice
            && $priceProductStoreEntity->getPriceDataChecksum() === $dataSet[PriceProductDataSet::KEY_PRICE_DATA_CHECKSUM]
        ) {
            return $priceProductStoreEntity;
        }

        $priceProductDefaultEntity = $this->getPriceProductDefaultEntity($priceProductStoreEntity);

        return (new SpyPriceProductStore())
            ->setFkStore($dataSet[PriceProductDataSet::ID_STORE])
            ->setFkCurrency($dataSet[PriceProductDataSet::ID_CURRENCY])
            ->setFkPriceProduct($productPriceEntity->getPrimaryKey())
            ->setGrossPrice((int)$dataSet[PriceProductDataSet::KEY_PRICE_GROSS])
            ->setNetPrice((int)$dataSet[PriceProductDataSet::KEY_PRICE_NET])
            ->setCostPrice($costPrice)
            ->setPriceData($dataSet[PriceProductDataSet::KEY_PRICE_DATA])
            ->setPriceDataChecksum($dataSet[PriceProductDataSet::KEY_PRICE_DATA_CHECKSUM])
            ->addPriceProductDefault($priceProductDefaultEntity);
    }
}
