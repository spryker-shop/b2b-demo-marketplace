<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Go\Zed\Event\Business;
use Go\Zed\Event\Business\Dispatcher\EventDispatcher;
use Go\Zed\Event\Business\Queue\Consumer\EventQueueConsumer;

/**
 * @method \Spryker\Zed\Event\EventConfig getConfig()
 */
class EventBusinessFactory extends \Spryker\Zed\Event\Business\EventBusinessFactory
{
    /**
     * @return \Spryker\Zed\Event\Business\Queue\Consumer\EventQueueConsumerInterface
     */
    public function createEventQueueConsumer()
    {
        return new EventQueueConsumer($this->createEventLogger(), $this->getUtilEncodingService(), $this->getConfig());
    }

    /**
     * @return \Spryker\Zed\Event\Business\Dispatcher\EventDispatcherInterface
     */
    public function createEventDispatcher()
    {
        $eventListeners = $this->createSubscriberMerger()
            ->mergeSubscribersWith($this->getEventListeners());

        return new EventDispatcher(
            $eventListeners,
            $this->createEventQueueProducer(),
            $this->createEventLogger(),
            $this->getUtilEncodingService(),
        );
    }
}
