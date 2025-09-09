<?php

declare(strict_types = 1);

namespace Pyz\Client\RabbitMq;

use Spryker\Client\RabbitMq\Model\Consumer\ConsumerInterface;
use Spryker\Client\RabbitMq\RabbitMqClientInterface as SprykerRabbitMqClientInterface;

interface RabbitMqClientInterface extends SprykerRabbitMqClientInterface
{
    /**
     * Specification:
     * - Called multiple times per lifecycle.
     * - N (stores) * queue.
     *
     * @api
     *
     * @param string $queue
     * @param string|null $storeCode
     * @param string|null $locale
     *
     * @return array<string, int>
     */
    public function getQueueMetrics(
        string $queue,
        ?string $storeCode = null,
        ?string $locale = null,
    ): array;
}
