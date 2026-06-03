<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Business\Model\Product;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStore;
use Spryker\Zed\PriceProduct\Business\Model\Product\PriceProductStoreWriter as SprykerPriceProductStoreWriter;

class PriceProductStoreWriter extends SprykerPriceProductStoreWriter
{
    public function persistPriceProductStore(PriceProductTransfer $priceProductTransfer): PriceProductTransfer
    {
        $moneyValueTransfer = $priceProductTransfer->getMoneyValueOrFail();

        if (!$priceProductTransfer->getIdPriceProduct()) {
            $priceProductTransfer = $this->savePriceProductEntity($priceProductTransfer);
        }

        $priceProductStoreEntity = $this->findPriceProductStoreEntity(
            $priceProductTransfer,
            $moneyValueTransfer,
        );

        $priceProductStoreEntity->fromArray($moneyValueTransfer->toArray());
        $priceProductStoreEntity
            ->setGrossPrice($moneyValueTransfer->getGrossAmount())
            ->setNetPrice($moneyValueTransfer->getNetAmount())
            ->setCostPrice($moneyValueTransfer->getCostAmount())
            ->setFkPriceProduct($priceProductTransfer->getIdPriceProductOrFail());

        $priceProductStoreEntity = $this->setPriceDataChecksum($moneyValueTransfer, $priceProductStoreEntity);

        $priceProductStoreEntity->save();

        $moneyValueTransfer->setIdEntity((int)$priceProductStoreEntity->getIdPriceProductStore());

        $priceProductTransfer = $this->persistPriceProductDimension($priceProductTransfer);

        if ($this->priceProductConfig->getIsDeleteOrphanStorePricesOnSaveEnabled()) {
            $this->deleteOrphanPriceProductStoreEntities($priceProductTransfer);
        }

        return $priceProductTransfer;
    }

    protected function findPriceProductStoreEntity(
        PriceProductTransfer $priceProductTransfer,
        MoneyValueTransfer $moneyValueTransfer,
    ): SpyPriceProductStore {
        return $this->priceProductQueryContainer
            ->queryPriceProductStoreByProductCurrencyStore(
                $priceProductTransfer->getIdPriceProductOrFail(),
                $moneyValueTransfer->getFkCurrencyOrFail(),
                $moneyValueTransfer->getFkStoreOrFail(),
            )
            ->filterByNetPrice($moneyValueTransfer->getNetAmount())
            ->filterByGrossPrice($moneyValueTransfer->getGrossAmount())
            ->filterByCostPrice($moneyValueTransfer->getCostAmount())
            ->filterByPriceDataChecksum($moneyValueTransfer->getPriceDataChecksum())
            ->findOneOrCreate();
    }
}
