<?php

namespace Pyz\Zed\ProductImageStorage\Business;

use Pyz\Zed\ProductImageStorage\Business\Storage\ProductAbstractImageStorageWriter;
use Pyz\Zed\ProductImageStorage\Business\Storage\ProductConcreteImageStorageWriter;

class ProductImageStorageBusinessFactory extends \Spryker\Zed\ProductImageStorage\Business\ProductImageStorageBusinessFactory
{
    /**
     * @return \Spryker\Zed\ProductImageStorage\Business\Storage\ProductAbstractImageStorageWriterInterface
     */
    public function createProductAbstractImageWriter()
    {
        return new ProductAbstractImageStorageWriter(
            $this->getProductImageFacade(),
            $this->getQueryContainer(),
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getEventBehaviorFacade(),
            $this->getConfig()->isSendingToQueue(),
        );
    }

    /**
     * @return \Spryker\Zed\ProductImageStorage\Business\Storage\ProductConcreteImageStorageWriterInterface
     */
    public function createProductConcreteImageWriter()
    {
        return new ProductConcreteImageStorageWriter(
            $this->getProductImageFacade(),
            $this->getQueryContainer(),
            $this->getRepository(),
            $this->getConfig()->isSendingToQueue(),
        );
    }
}
