<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\Strategy;

use ArrayObject;

/**
 * This strategy aims at providing queues in the pre-defined order
 * of array keys of the resulting array here
 * \Pyz\Zed\Queue\QueueDependencyProvider::getProcessorMessagePlugins
 */
class OrderedQueuesStrategy extends AbstractStrategy
{
    protected ArrayObject $queuesPerStore;

    /**
     * @return void
     */
    protected function getQueuesWithMessages(): void
    {
        $this->queuesPerStore = $this->scanQueues(
            $this->queueNames,
        );
        $this->currentIterator = $this->queuesPerStore->getIterator();
    }
}
