<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductPageSearch\Communication\Plugin\Event\Subscriber;

use Pyz\Zed\ProductPageSearch\Communication\Plugin\Event\Listener\ProductPageCategoryNodeSearchListener;
use Pyz\Zed\ProductPageSearch\Communication\Plugin\Event\Listener\ProductPageCategorySearchListener;
use Spryker\Zed\Category\Dependency\CategoryEvents;
use Spryker\Zed\Event\Dependency\EventCollectionInterface;
use Spryker\Zed\ProductPageSearch\Communication\Plugin\Event\Subscriber\ProductPageSearchEventSubscriber as SprykerProductPageSearchEventSubscriber;

/**
 * Registers the Pyz category listeners with the category fan-out workaround (CC-31649).
 *
 * @method \Spryker\Zed\ProductPageSearch\Communication\ProductPageSearchCommunicationFactory getFactory()
 * @method \Spryker\Zed\ProductPageSearch\Business\ProductPageSearchFacadeInterface getFacade()
 * @method \Spryker\Zed\ProductPageSearch\ProductPageSearchConfig getConfig()
 */
class ProductPageSearchEventSubscriber extends SprykerProductPageSearchEventSubscriber
{
    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function addProductPageCategoryCreateSearchListener(EventCollectionInterface $eventCollection)
    {
        $eventCollection->addListenerQueued(CategoryEvents::ENTITY_SPY_CATEGORY_CREATE, new ProductPageCategorySearchListener(), 0, null, $this->getConfig()->getProductPageEventQueueName());
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function addProductPageCategoryPublishSearchListener(EventCollectionInterface $eventCollection)
    {
        $eventCollection->addListenerQueued(static::ENTITY_CATEGORY_PUBLISH, new ProductPageCategorySearchListener(), 0, null, $this->getConfig()->getProductPageEventQueueName());
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function addProductPageCategoryUpdateSearchListener(EventCollectionInterface $eventCollection)
    {
        $eventCollection->addListenerQueued(CategoryEvents::ENTITY_SPY_CATEGORY_UPDATE, new ProductPageCategorySearchListener(), 0, null, $this->getConfig()->getProductPageEventQueueName());
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function addProductPageCategoryDeleteSearchListener(EventCollectionInterface $eventCollection)
    {
        $eventCollection->addListenerQueued(CategoryEvents::ENTITY_SPY_CATEGORY_DELETE, new ProductPageCategorySearchListener(), 0, null, $this->getConfig()->getProductPageEventQueueName());
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function addProductPageCategoryNodeCreateSearchListener(EventCollectionInterface $eventCollection)
    {
        $eventCollection->addListenerQueued(CategoryEvents::ENTITY_SPY_CATEGORY_NODE_CREATE, new ProductPageCategoryNodeSearchListener(), 0, null, $this->getConfig()->getProductPageEventQueueName());
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function addProductPageCategoryNodeUpdateSearchListener(EventCollectionInterface $eventCollection)
    {
        $eventCollection->addListenerQueued(CategoryEvents::ENTITY_SPY_CATEGORY_NODE_UPDATE, new ProductPageCategoryNodeSearchListener(), 0, null, $this->getConfig()->getProductPageEventQueueName());
    }

    /**
     * @param \Spryker\Zed\Event\Dependency\EventCollectionInterface $eventCollection
     *
     * @return void
     */
    protected function addProductPageCategoryNodeDeleteSearchListener(EventCollectionInterface $eventCollection)
    {
        $eventCollection->addListenerQueued(CategoryEvents::ENTITY_SPY_CATEGORY_NODE_DELETE, new ProductPageCategoryNodeSearchListener(), 0, null, $this->getConfig()->getProductPageEventQueueName());
    }
}
