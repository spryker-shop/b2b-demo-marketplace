<?php

declare(strict_types = 1);

namespace Pyz\Client\RabbitMq\Model\Publisher;

use Spryker\Client\RabbitMq\Model\Publisher\Publisher as SprykerPublisher;

class Publisher extends SprykerPublisher
{
    /**
     * @var bool
     */
    protected static bool $isChannelBufferCorrupted = false;

    /**
     * @override Track channel buffer status
     *
     * @return array<\PhpAmqpLib\Channel\AMQPChannel>
     */
    protected function getDefaultChannel(): array
    {
        if (!static::$isChannelBufferCorrupted && isset($this->channelBuffer[static::DEFAULT_CHANNEL])) {
            return $this->channelBuffer[static::DEFAULT_CHANNEL];
        }

        $this->channelBuffer[static::DEFAULT_CHANNEL] = [$this->connectionManager->getDefaultChannel()];

        static::$isChannelBufferCorrupted = false;

        return $this->channelBuffer[static::DEFAULT_CHANNEL];
    }
}
