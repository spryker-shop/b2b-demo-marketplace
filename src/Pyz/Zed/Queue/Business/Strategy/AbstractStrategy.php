<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\Strategy;

use ArrayObject;
use Generated\Shared\Transfer\QueueTransfer;
use Generator;
use Iterator;
use Psr\Log\LoggerInterface;
use Pyz\Client\RabbitMq\RabbitMqClientInterface;

abstract class AbstractStrategy implements QueueProcessingStrategyInterface
{
    use ScanQueuesTrait;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var array<string>
     */
    protected array $queueNames;

    protected Iterator $currentIterator;

    public function __construct(
        RabbitMqClientInterface $mqClient,
        array $queueNames,
        LoggerInterface $logger,
    ) {
        $this->mqClient = $mqClient;
        $this->queueNames = $queueNames;
        $this->logger = $logger;
        $this->currentIterator = (new ArrayObject())->getIterator();
    }

    public function getNextQueue(): Generator
    {
        // get queues for all stores with msgs count
        $queueTransfer = $this->getQueue();
        if ($queueTransfer) {
            yield $queueTransfer;
        }
    }

    protected function getQueue(): ?QueueTransfer
    {
        if (!$this->currentIterator->valid()) {
            $this->getQueuesWithMessages();
        }

        /** @var \Generated\Shared\Transfer\QueueTransfer|null $queueTransfer */
        $queueTransfer = $this->currentIterator->current();
        $this->currentIterator->next();

        return $queueTransfer;
    }

    abstract protected function getQueuesWithMessages(): void;
}
