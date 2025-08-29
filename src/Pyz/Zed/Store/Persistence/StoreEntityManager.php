<?php

namespace Pyz\Zed\Store\Persistence;

use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Store\Persistence\SpyStore;

class StoreEntityManager extends \Spryker\Zed\Store\Persistence\StoreEntityManager
{
    public function createStore(StoreTransfer $storeTransfer): StoreTransfer
    {
        $storeEntity = (new SpyStore())
            ->fromArray($storeTransfer->toArray());

        $storeEntity->save();

        $storeTransfer->setIdStore($storeEntity->getIdStore());

        return $storeTransfer;
    }
}
