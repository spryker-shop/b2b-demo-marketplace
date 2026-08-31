<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferDataImport\Business\Step;

use Demo\Zed\PriceProductOfferDataImport\Business\DataSet\PriceProductOfferDataSetInterface;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStore;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStoreQuery;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\PriceProductOfferDataImport\Business\Step\PriceProductStoreWriterStep as SprykerPriceProductStoreWriterStep;

class PriceProductStoreWriterStep extends SprykerPriceProductStoreWriterStep
{
    /**
     * @var string
     */
    protected const VALUE_COST = PriceProductOfferDataSetInterface::VALUE_COST;

    protected function getIdPriceProductStore(DataSetInterface $dataSet): string
    {
        /** @var \Orm\Zed\PriceProduct\Persistence\SpyPriceProductStore|null $priceProductStoreEntity */
        $priceProductStoreEntity = SpyPriceProductStoreQuery::create()
            ->filterByFkStore($dataSet[PriceProductOfferDataSetInterface::FK_STORE])
            ->filterByFkCurrency($dataSet[PriceProductOfferDataSetInterface::FK_CURRENCY])
            ->filterByFkPriceProduct($dataSet[PriceProductOfferDataSetInterface::FK_PRICE_PRODUCT])
            ->useSpyPriceProductOfferQuery()
                ->useSpyProductOfferQuery()
                    ->filterByProductOfferReference($dataSet[PriceProductOfferDataSetInterface::PRODUCT_OFFER_REFERENCE])
                ->endUse()
            ->endUse()
            ->findOne();

        if (!$priceProductStoreEntity) {
            $priceProductStoreEntity = (new SpyPriceProductStore())
                ->setFkStore($dataSet[PriceProductOfferDataSetInterface::FK_STORE])
                ->setFkCurrency($dataSet[PriceProductOfferDataSetInterface::FK_CURRENCY])
                ->setFkPriceProduct($dataSet[PriceProductOfferDataSetInterface::FK_PRICE_PRODUCT]);
        }

        $priceProductStoreEntity
            ->setNetPrice($this->castPriceValue($dataSet[static::VALUE_NET]))
            ->setGrossPrice($this->castPriceValue($dataSet[static::VALUE_GROSS]))
            ->setCostPrice($this->getCostPrice($dataSet))
            ->setPriceData($dataSet[PriceProductOfferDataSetInterface::KEY_PRICE_DATA])
            ->setPriceDataChecksum($dataSet[PriceProductOfferDataSetInterface::KEY_PRICE_DATA_CHECKSUM]);

        $priceProductStoreEntity->save();

        return $priceProductStoreEntity->getIdPriceProductStore();
    }

    protected function getCostPrice(DataSetInterface $dataSet): ?int
    {
        if (!$dataSet->offsetExists(static::VALUE_COST)) {
            return null;
        }

        return $this->castPriceValue((string)$dataSet[static::VALUE_COST]);
    }
}
