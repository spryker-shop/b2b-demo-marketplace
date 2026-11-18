<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\Publisher;

use Pyz\Zed\Publisher\PublisherDependencyProvider as PyzPublisherDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Publisher\KnowledgeDocumentWritePublisherPlugin;

class PublisherDependencyProvider extends PyzPublisherDependencyProvider
{
    /**
     * @return array<int|string, \Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherPluginInterface>|array<string, array<int|string, \Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherPluginInterface>>
     */
    protected function getPublisherPlugins(): array
    {
        return array_merge(
            parent::getPublisherPlugins(),
            $this->getAiCommercePlugins(),
        );
    }

    /**
     * @return array<\Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherPluginInterface>
     */
    protected function getAiCommercePlugins(): array
    {
        return [
            new KnowledgeDocumentWritePublisherPlugin(),
        ];
    }
}
