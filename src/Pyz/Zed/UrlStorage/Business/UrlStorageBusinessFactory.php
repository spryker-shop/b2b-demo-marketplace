<?php

namespace Pyz\Zed\UrlStorage\Business;

use Pyz\Zed\UrlStorage\Business\Storage\RedirectStorageWriter;
use Pyz\Zed\UrlStorage\Business\Storage\UrlStorageWriter;

class UrlStorageBusinessFactory extends \Spryker\Zed\UrlStorage\Business\UrlStorageBusinessFactory
{
    /**
     * @return \Spryker\Zed\UrlStorage\Business\Storage\UrlStorageWriterInterface
     */
    public function createUrlStorageWriter()
    {
        return new UrlStorageWriter(
            $this->getUtilSanitizeService(),
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getStoreFacade(),
            $this->getConfig()->isSendingToQueue(),
        );
    }

    /**
     * @return \Spryker\Zed\UrlStorage\Business\Storage\RedirectStorageWriterInterface
     */
    public function createRedirectStorageWriter()
    {
        return new RedirectStorageWriter(
            $this->getUtilSanitizeService(),
            $this->getQueryContainer(),
            $this->getConfig()->isSendingToQueue(),
        );
    }
}
