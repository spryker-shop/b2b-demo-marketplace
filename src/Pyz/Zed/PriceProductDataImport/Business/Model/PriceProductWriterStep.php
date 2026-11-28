<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PriceProductDataImport\Business\Model;

use Orm\Zed\PriceProduct\Persistence\Map\SpyPriceTypeTableMap;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductQuery;
use Orm\Zed\PriceProduct\Persistence\SpyPriceType;
use Orm\Zed\PriceProduct\Persistence\SpyPriceTypeQuery;
use Pyz\Zed\DataImport\Business\Model\Publisher\DataImporterPublisher;
use Spryker\Zed\DataImport\Business\Exception\DataKeyNotFoundInDataSetException;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\PriceProduct\Dependency\PriceProductEvents;
use Spryker\Zed\PriceProductDataImport\Business\Model\DataSet\PriceProductDataSet;
use Spryker\Zed\PriceProductDataImport\Business\Model\PriceProductWriterStep as SprykerPriceProductWriterStep;
use Spryker\Zed\Product\Dependency\ProductEvents;

/**
 * Backport of spryker/suite#1644 (CC-41867): the price type id is read once per run instead of once per row.
 * Events go through the Pyz publisher, which holds them while the bulk transaction is open (CC-41794).
 */
class PriceProductWriterStep extends SprykerPriceProductWriterStep
{
    /**
     * Price type ids by name, read once per import run instead of once per row.
     *
     * @var array<string, int>
     */
    protected array $idPriceTypeCache = [];

    /**
     * Price types this run created. Their ids are not cached: the creating transaction can still be rolled back.
     *
     * @var array<string, bool>
     */
    protected array $createdPriceTypeNames = [];

    /**
     * @throws \Spryker\Zed\DataImport\Business\Exception\DataKeyNotFoundInDataSetException
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $priceProductQuery = SpyPriceProductQuery::create();
        $priceProductQuery->filterByFkPriceType($this->getIdPriceType((string)$dataSet[PriceProductDataSet::KEY_PRICE_TYPE]));

        if (empty($dataSet[PriceProductDataSet::KEY_ABSTRACT_SKU]) && empty($dataSet[PriceProductDataSet::KEY_CONCRETE_SKU])) {
            throw new DataKeyNotFoundInDataSetException(sprintf(
                'One of "%s" or "%s" must be in the data set. Given: "%s"',
                PriceProductDataSet::KEY_ABSTRACT_SKU,
                PriceProductDataSet::KEY_CONCRETE_SKU,
                implode(', ', array_keys($dataSet->getArrayCopy())),
            ));
        }

        if (!empty($dataSet[PriceProductDataSet::KEY_ABSTRACT_SKU])) {
            $priceProductQuery->filterByFkProductAbstract($dataSet[PriceProductDataSet::ID_PRODUCT_ABSTRACT]);
            $this->addPublishEvents(PriceProductEvents::PRICE_ABSTRACT_PUBLISH, $dataSet[PriceProductDataSet::ID_PRODUCT_ABSTRACT]);
            $this->addPublishEvents(ProductEvents::PRODUCT_ABSTRACT_PUBLISH, $dataSet[PriceProductDataSet::ID_PRODUCT_ABSTRACT]);
        } else {
            $this->addPublishEvents(PriceProductEvents::PRICE_CONCRETE_PUBLISH, $dataSet[PriceProductDataSet::ID_PRODUCT_CONCRETE]);
            $priceProductQuery->filterByFkProduct($dataSet[PriceProductDataSet::ID_PRODUCT_CONCRETE]);
        }

        $productPriceEntity = $priceProductQuery->findOneOrCreate();
        $productPriceEntity->save();

        $priceProductStoreEntity = $this->getPriceProductStoreEntityWithDefaultDimension($dataSet, $productPriceEntity);
        $priceProductStoreEntity->save();

        $this->savePriceProductDefault($priceProductStoreEntity);
    }

    public function afterExecute(): void
    {
        foreach ($this->entityEvents as $eventName => $ids) {
            foreach ($ids as $id) {
                DataImporterPublisher::addEvent($eventName, $id);
            }
        }

        $this->entityEvents = [];
    }

    protected function getIdPriceType(string $priceTypeName): int
    {
        if (isset($this->idPriceTypeCache[$priceTypeName])) {
            return $this->idPriceTypeCache[$priceTypeName];
        }

        $priceTypeEntity = $this->findOrCreatePriceTypeEntity($priceTypeName);

        if ($priceTypeEntity->isNew() || $priceTypeEntity->isModified()) {
            $priceTypeEntity->setPriceModeConfiguration(SpyPriceTypeTableMap::COL_PRICE_MODE_CONFIGURATION_BOTH);
            $priceTypeEntity->save();
            $this->createdPriceTypeNames[$priceTypeName] = true;
        }

        $idPriceType = (int)$priceTypeEntity->getIdPriceType();

        if (!isset($this->createdPriceTypeNames[$priceTypeName])) {
            $this->idPriceTypeCache[$priceTypeName] = $idPriceType;
        }

        return $idPriceType;
    }

    protected function findOrCreatePriceTypeEntity(string $priceTypeName): SpyPriceType
    {
        return SpyPriceTypeQuery::create()
            ->filterByName($priceTypeName)
            ->findOneOrCreate();
    }
}
