<?php

namespace Pyz\Zed\UrlStorage\Business\Storage;

use Orm\Zed\UrlStorage\Persistence\SpyUrlRedirectStorage;

class RedirectStorageWriter extends \Spryker\Zed\UrlStorage\Business\Storage\RedirectStorageWriter
{
    protected const ID_TENANT = 'id_tenant';

    /**
     * @param array $spyRedirectEntity
     * @param \Orm\Zed\UrlStorage\Persistence\SpyUrlRedirectStorage|null $spyUrlRedirectStorage
     *
     * @return void
     */
    protected function storeDataSet(array $spyRedirectEntity, ?SpyUrlRedirectStorage $spyUrlRedirectStorage = null)
    {
        if ($spyUrlRedirectStorage === null) {
            $spyUrlRedirectStorage = new SpyUrlRedirectStorage();
        }

        $spyUrlRedirectStorage->setFkUrlRedirect($spyRedirectEntity[static::ID_URL_REDIRECT]);
        $spyUrlRedirectStorage->setData($this->utilSanitize->arrayFilterRecursive($spyRedirectEntity));
        $spyUrlRedirectStorage->setIdTenant($spyRedirectEntity[static::ID_TENANT]);
        $spyUrlRedirectStorage->setIsSendingToQueue($this->isSendingToQueue);
        $spyUrlRedirectStorage->save();
    }
}
