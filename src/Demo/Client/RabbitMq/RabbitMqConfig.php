<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\RabbitMq;

use Pyz\Client\RabbitMq\RabbitMqConfig as PyzRabbitMqConfig;
use Spryker\Shared\Event\EventConfig;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;

class RabbitMqConfig extends PyzRabbitMqConfig
{
    /**
     * @return array<mixed>
     */
    protected function getQueueConfiguration(): array
    {
        return array_merge(
            parent::getQueueConfiguration(),
            [
                SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_INDEX_QUEUE => [
                    EventConfig::EVENT_ROUTING_KEY_ERROR => SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_INDEX_ERROR_QUEUE,
                ],
            ],
        );
    }
}
