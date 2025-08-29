<?php

namespace Pyz\Zed\UrlStorage\Business\Storage;

use Generated\Shared\Transfer\UrlStorageTransfer;
use Orm\Zed\UrlStorage\Persistence\SpyUrlStorage;

class UrlStorageWriter extends \Spryker\Zed\UrlStorage\Business\Storage\UrlStorageWriter
{
    /**
     * @param \Generated\Shared\Transfer\UrlStorageTransfer $urlStorageTransfer
     * @param \Orm\Zed\UrlStorage\Persistence\SpyUrlStorage|null $urlStorageEntity
     *
     * @return void
     */
    protected function storeDataSet(UrlStorageTransfer $urlStorageTransfer, ?SpyUrlStorage $urlStorageEntity = null)
    {
        if ($urlStorageEntity === null) {
            $urlStorageEntity = new SpyUrlStorage();
        }

        $resource = $this->findResourceArguments($urlStorageTransfer->toArray());

        if ($resource === null) {
            return;
        }

        $urlStorageEntity->setByName('fk_' . $resource[static::RESOURCE_TYPE], $resource[static::RESOURCE_VALUE]);
        $urlStorageEntity->setUrl($urlStorageTransfer->getUrl());
        $urlStorageEntity->setFkUrl($urlStorageTransfer->getIdUrl());
        $urlStorageEntity->setData($this->utilSanitize->arrayFilterRecursive($urlStorageTransfer->modifiedToArray()));
        $urlStorageEntity->setIdTenant($urlStorageTransfer->getIdTenant());
        $urlStorageEntity->setIsSendingToQueue($this->isSendingToQueue);
        $urlStorageEntity->save();
    }
}
