<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductCategoryStorage\Business;

use Pyz\Zed\ProductCategoryStorage\Business\Trigger\ProductCategoryPublishEventTrigger;
use Pyz\Zed\ProductCategoryStorage\Business\Trigger\ProductCategoryPublishEventTriggerInterface;
use Pyz\Zed\ProductCategoryStorage\Business\Writer\CategoryStore\ProductCategoryStorageByCategoryStoreEventsWriter;
use Pyz\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriter;
use Pyz\Zed\ProductCategoryStorage\ProductCategoryStorageDependencyProvider;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\ProductCategoryStorage\Business\ProductCategoryStorageBusinessFactory as SprykerProductCategoryStorageBusinessFactory;
use Spryker\Zed\ProductCategoryStorage\Business\Writer\CategoryStore\ProductCategoryStorageByCategoryStoreEventsWriterInterface;
use Spryker\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriterInterface;

/**
 * Backport of spryker/suite#1619 (CC-31649): bounded category-to-product fan-out.
 * Remove when spryker/product-category-storage ships it.
 *
 * @method \Pyz\Zed\ProductCategoryStorage\ProductCategoryStorageConfig getConfig()
 * @method \Spryker\Zed\ProductCategoryStorage\Persistence\ProductCategoryStorageQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\ProductCategoryStorage\Persistence\ProductCategoryStorageRepositoryInterface getRepository()
 * @method \Spryker\Zed\ProductCategoryStorage\Persistence\ProductCategoryStorageEntityManagerInterface getEntityManager()
 */
class ProductCategoryStorageBusinessFactory extends SprykerProductCategoryStorageBusinessFactory
{
    /**
     * @return \Pyz\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriterInterface
     */
    public function createProductCategoryStorageWriter(): ProductCategoryStorageWriterInterface
    {
        return new ProductCategoryStorageWriter(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getStoreFacade(),
            $this->createProductAbstractReader(),
            $this->createProductCategoryStorageReader(),
            $this->getConfig(),
            $this->createProductCategoryPublishEventTrigger(),
        );
    }

    public function createProductCategoryStorageByCategoryStoreEventsWriter(): ProductCategoryStorageByCategoryStoreEventsWriterInterface
    {
        /** @var \Pyz\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriterInterface $productCategoryStorageWriter */
        $productCategoryStorageWriter = $this->createProductCategoryStorageWriter();

        return new ProductCategoryStorageByCategoryStoreEventsWriter(
            $this->getEventBehaviorFacade(),
            $this->createProductAbstractReader(),
            $productCategoryStorageWriter,
        );
    }

    public function createProductCategoryPublishEventTrigger(): ProductCategoryPublishEventTriggerInterface
    {
        return new ProductCategoryPublishEventTrigger(
            $this->getEventFacade(),
            $this->getConfig(),
        );
    }

    public function getEventFacade(): EventFacadeInterface
    {
        return $this->getProvidedDependency(ProductCategoryStorageDependencyProvider::FACADE_EVENT);
    }
}
