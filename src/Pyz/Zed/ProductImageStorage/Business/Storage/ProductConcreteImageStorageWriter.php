<?php

namespace Pyz\Zed\ProductImageStorage\Business\Storage;

use Generated\Shared\Transfer\ProductConcreteImageStorageTransfer;
use Orm\Zed\Product\Persistence\SpyProductLocalizedAttributes;
use Orm\Zed\ProductImageStorage\Persistence\SpyProductConcreteImageStorage;

class ProductConcreteImageStorageWriter extends \Spryker\Zed\ProductImageStorage\Business\Storage\ProductConcreteImageStorageWriter
{
    protected function storeDataSet(SpyProductLocalizedAttributes $spyProductLocalizedEntity, array $imageSets, ?SpyProductConcreteImageStorage $spyProductConcreteImageStorage = null)
    {
        if ($spyProductConcreteImageStorage === null) {
            $spyProductConcreteImageStorage = new SpyProductConcreteImageStorage();
        }

        if (empty($imageSets[$spyProductLocalizedEntity->getFkProduct()][$spyProductLocalizedEntity->getIdProductAttributes()])) {
            if (!$spyProductConcreteImageStorage->isNew()) {
                $spyProductConcreteImageStorage->delete();
            }

            return;
        }

        $productConcreteStorageTransfer = new ProductConcreteImageStorageTransfer();
        $productConcreteStorageTransfer->setIdProductConcrete($spyProductLocalizedEntity->getFkProduct());
        $productConcreteStorageTransfer->setImageSets($imageSets[$spyProductLocalizedEntity->getFkProduct()][$spyProductLocalizedEntity->getIdProductAttributes()]);

        $spyProductConcreteImageStorage->setFkProduct($spyProductLocalizedEntity->getFkProduct());
        $spyProductConcreteImageStorage->setData($productConcreteStorageTransfer->toArray());
        $spyProductConcreteImageStorage->setLocale($spyProductLocalizedEntity->getLocale()->getLocaleName());
        $spyProductConcreteImageStorage->setIdTenant($spyProductLocalizedEntity->getIdTenant());
        $spyProductConcreteImageStorage->setIsSendingToQueue($this->isSendingToQueue);
        $spyProductConcreteImageStorage->save();
    }

}
