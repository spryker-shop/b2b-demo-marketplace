<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\SymfonyMessenger;

use Pyz\Client\SymfonyMessenger\SymfonyMessengerConfig as PyzSymfonyMessengerConfig;
use Spryker\Shared\Event\EventConfig;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig;

class SymfonyMessengerConfig extends PyzSymfonyMessengerConfig
{
    /**
     * @return array<mixed>
     */
    public function getQueueConfiguration(): array
    {
        return array_merge(
            parent::getQueueConfiguration(),
            [
                AiCommerceConfig::KNOWLEDGE_DOCUMENT_INDEX_QUEUE => [
                    EventConfig::EVENT_ROUTING_KEY_ERROR => AiCommerceConfig::KNOWLEDGE_DOCUMENT_INDEX_ERROR_QUEUE,
                ],
            ],
        );
    }
}
