<?php

declare(strict_types = 1);

namespace Pyz\Client\RabbitMq;

use Pyz\Client\RabbitMq\Model\Queue\QueueMetricReader;
use Spryker\Client\RabbitMq\Model\Consumer\ConsumerInterface;
use Spryker\Client\RabbitMq\RabbitMqClient as SprykerRabbitMqClient;

/**
 * @method \Pyz\Client\RabbitMq\RabbitMqFactory getFactory()
 */
class RabbitMqClient extends SprykerRabbitMqClient implements RabbitMqClientInterface
{
    protected static QueueMetricReader $metricsReader;

    /**
     * Called multiple times per lifecycle
     * N (stores) * queue
     *
     * @param string $queue
     * @param string|null $storeCode
     * @param string|null $locale
     *
     * @return array<string, int>
     */
    public function getQueueMetrics(string $queue, ?string $storeCode = null, ?string $locale = null): array
    {
        static::$metricsReader = static::$metricsReader ?? $this->getFactory()->createQueueMetricReader();

        return static::$metricsReader->getQueueMetrics($queue, $storeCode, $locale);
    }
}
