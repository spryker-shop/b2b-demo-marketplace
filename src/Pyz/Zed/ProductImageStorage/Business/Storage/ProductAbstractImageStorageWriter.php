<?php

namespace Pyz\Zed\ProductImageStorage\Business\Storage;

use Generated\Shared\Transfer\ProductAbstractImageStorageTransfer;
use Orm\Zed\Product\Persistence\SpyProductAbstractLocalizedAttributes;
use Orm\Zed\ProductImageStorage\Persistence\SpyProductAbstractImageStorage;

class ProductAbstractImageStorageWriter extends \Spryker\Zed\ProductImageStorage\Business\Storage\ProductAbstractImageStorageWriter
{
    protected function storeDataSet(
        SpyProductAbstractLocalizedAttributes $spyProductAbstractLocalizedEntity,
        array $imageSets,
        ?SpyProductAbstractImageStorage $spyProductAbstractImageStorage = null
    ) {
        if ($spyProductAbstractImageStorage === null) {
            $spyProductAbstractImageStorage = new SpyProductAbstractImageStorage();
        }

        if (empty($imageSets[$spyProductAbstractLocalizedEntity->getFkProductAbstract()])) {
            if (!$spyProductAbstractImageStorage->isNew()) {
                $spyProductAbstractImageStorage->delete();
            }

            return;
        }

        $productAbstractStorageTransfer = new ProductAbstractImageStorageTransfer();
        $productAbstractStorageTransfer->setIdProductAbstract($spyProductAbstractLocalizedEntity->getFkProductAbstract());
        $productAbstractStorageTransfer->setImageSets($imageSets[$spyProductAbstractLocalizedEntity->getFkProductAbstract()][$spyProductAbstractLocalizedEntity->getIdAbstractAttributes()]);

        $spyProductAbstractImageStorage->setFkProductAbstract($spyProductAbstractLocalizedEntity->getFkProductAbstract());
        $spyProductAbstractImageStorage->setData($productAbstractStorageTransfer->toArray());
        $spyProductAbstractImageStorage->setLocale($spyProductAbstractLocalizedEntity->getLocale()->getLocaleName());
        $spyProductAbstractImageStorage->setIsSendingToQueue($this->isSendingToQueue);
        $spyProductAbstractImageStorage->setIdTenant($spyProductAbstractLocalizedEntity->getIdTenant());
        $spyProductAbstractImageStorage->save();
    }
}
