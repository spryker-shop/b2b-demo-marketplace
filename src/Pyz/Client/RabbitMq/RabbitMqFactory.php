<?php
declare(strict_types = 1);

namespace Pyz\Client\RabbitMq;

use Pyz\Client\RabbitMq\Model\Connection\ConnectionBuilder\ConnectionBuilder;
use Pyz\Client\RabbitMq\Model\Publisher\Publisher;
use Pyz\Client\RabbitMq\Model\Queue\QueueMetricReader;
use Spryker\Client\RabbitMq\Model\Connection\ConnectionBuilder\ConnectionBuilderInterface;
use Spryker\Client\RabbitMq\Model\Consumer\Consumer;
use Spryker\Client\RabbitMq\Model\Consumer\ConsumerInterface;
use Spryker\Client\RabbitMq\Model\Publisher\PublisherInterface;
use Spryker\Client\RabbitMq\RabbitMqFactory as SprykerRabbitMqFactory;

/**
 * @method \Spryker\Client\RabbitMq\RabbitMqConfig getConfig()
 */
class RabbitMqFactory extends SprykerRabbitMqFactory
{
    /**
     * @return \Spryker\Client\RabbitMq\Model\Connection\ConnectionBuilder\ConnectionBuilderInterface
     */
    public function createConnectionBuilder(): ConnectionBuilderInterface
    {
        return new ConnectionBuilder(
            $this->getConfig(),
            $this->getStoreClient(),
            $this->createQueueEstablishmentHelper(),
        );
    }

    /**
     * @return \Spryker\Client\RabbitMq\Model\Publisher\PublisherInterface
     */
    public function createPublisher(): PublisherInterface
    {
        return new Publisher(
            $this->getStaticConnectionManager(),
            $this->getConfig(),
        );
    }

    public function createQueueMetricReader(): QueueMetricReader
    {
        return new QueueMetricReader($this->getStaticConnectionManager());
    }
}
