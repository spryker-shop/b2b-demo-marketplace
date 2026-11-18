<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\Queue;

use Pyz\Zed\Queue\QueueDependencyProvider as PyzQueueDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerFeature\Shared\AiCommerce\AiCommerceConfig as SharedAiCommerceConfig;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Queue\KnowledgeDocumentIndexQueueMessageProcessorPlugin;

class QueueDependencyProvider extends PyzQueueDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\Queue\Dependency\Plugin\QueueMessageProcessorPluginInterface>
     */
    protected function getProcessorMessagePlugins(Container $container): array
    {
        return parent::getProcessorMessagePlugins($container) + [
            SharedAiCommerceConfig::KNOWLEDGE_DOCUMENT_INDEX_QUEUE => new KnowledgeDocumentIndexQueueMessageProcessorPlugin(),
        ];
    }
}
