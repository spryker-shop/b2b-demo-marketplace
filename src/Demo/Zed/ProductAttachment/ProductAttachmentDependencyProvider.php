<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductAttachment;

use Spryker\Zed\ProductAttachment\ProductAttachmentDependencyProvider as SprykerProductAttachmentDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentCollectionExpanderPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentCollectionPostSavePlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\ProductAttachment\KnowledgeSelectionProductAttachmentFormExpanderPlugin;

class ProductAttachmentDependencyProvider extends SprykerProductAttachmentDependencyProvider
{
    /**
     * @return list<\Spryker\Zed\ProductAttachmentExtension\Dependency\Plugin\ProductAttachmentCollectionExpanderPluginInterface>
     */
    protected function getProductAttachmentCollectionExpanderPlugins(): array
    {
        return [
            new KnowledgeSelectionProductAttachmentCollectionExpanderPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\ProductAttachmentExtension\Dependency\Plugin\ProductAttachmentCollectionPostSavePluginInterface>
     */
    protected function getProductAttachmentCollectionPostSavePlugins(): array
    {
        return [
            new KnowledgeSelectionProductAttachmentCollectionPostSavePlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\ProductAttachmentExtension\Dependency\Plugin\ProductAttachmentFormExpanderPluginInterface>
     */
    protected function getProductAttachmentFormExpanderPlugins(): array
    {
        return [
            new KnowledgeSelectionProductAttachmentFormExpanderPlugin(),
        ];
    }
}
