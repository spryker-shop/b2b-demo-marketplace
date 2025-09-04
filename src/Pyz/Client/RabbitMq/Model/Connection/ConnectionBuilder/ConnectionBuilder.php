<?php

declare(strict_types = 1);

namespace Pyz\Client\RabbitMq\Model\Connection\ConnectionBuilder;

use Generated\Shared\Transfer\QueueConnectionTransfer;
use Spryker\Client\RabbitMq\Model\Connection\ConnectionBuilder\ConnectionBuilder as SprykerConnectionBuilder;
use Spryker\Client\RabbitMq\Model\Connection\ConnectionInterface;

class ConnectionBuilder extends SprykerConnectionBuilder
{
    /**
     * @var bool
     */
    protected static bool $isConnectionClosed = false;

    /**
     * @override Track connection closed state
     *
     * @param \Generated\Shared\Transfer\QueueConnectionTransfer $queueConnectionTransfer
     *
     * @return \Spryker\Client\RabbitMq\Model\Connection\ConnectionInterface
     */
    protected function createOrGetConnection(QueueConnectionTransfer $queueConnectionTransfer): ConnectionInterface
    {
        if (
            !static::$isConnectionClosed
            && isset($this->createdConnectionsByConnectionName[$queueConnectionTransfer->getName()])
        ) {
            return $this->createdConnectionsByConnectionName[$queueConnectionTransfer->getName()];
        }

        unset($this->createdConnectionsByConnectionName[$queueConnectionTransfer->getName()]);

        $connection = $this->createConnection($queueConnectionTransfer);
        $this->createdConnectionsByConnectionName[$queueConnectionTransfer->getName()] = $connection;

        static::$isConnectionClosed = false;

        return $connection;
    }
}
