<?php

declare(strict_types = 1);

namespace Pyz\Client\RabbitMq\Model\Queue;

use RuntimeException;
use Spryker\Client\RabbitMq\Model\Connection\ConnectionManager;

class QueueMetricReader
{
    /**
     * @var \Spryker\Client\RabbitMq\Model\Connection\ConnectionManager
     */
    private ConnectionManager $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * @param string $queue
     * @param string|null $storeCode
     * @param string|null $locale
     *
     * @throws \RuntimeException
     *
     * @return array<string, int>
     */
    public function getQueueMetrics(
        string $queue,
        ?string $storeCode = null,
        ?string $locale = null,
    ): array {
        $channels = $storeCode ?
            $this->connectionManager->getChannelsByStoreName($storeCode, $locale) :
            [$this->connectionManager->getDefaultChannel()];

        $channel = reset($channels);
        if (!$channel) {
            throw new RuntimeException(sprintf('Could not find a connection for %s %s', $storeCode, $queue));
        }

        [, $messageCount, $consumerCount] = $channel->queue_declare($queue, true);

        return ['messageCount' => $messageCount, 'consumerCount' => $consumerCount];
    }
}
