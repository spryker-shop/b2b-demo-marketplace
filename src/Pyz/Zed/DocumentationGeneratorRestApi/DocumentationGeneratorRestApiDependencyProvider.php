<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DocumentationGeneratorRestApi;

use Spryker\Glue\GlueApplication\Plugin\DocumentationGeneratorRestApi\ResourceRelationshipCollectionProviderPlugin;
use Spryker\Glue\GlueApplication\Plugin\DocumentationGeneratorRestApi\ResourceRoutePluginsProviderPlugin;
use Spryker\Zed\DocumentationGeneratorRestApi\DocumentationGeneratorRestApiDependencyProvider as SprykerDocumentationGeneratorRestApiDependencyProvider;

class DocumentationGeneratorRestApiDependencyProvider extends SprykerDocumentationGeneratorRestApiDependencyProvider
{
    /**
     * @SuppressWarnings(LayerAccessRule) Legacy: documentation generator registers Glue provider plugins. Do not suppress for new code.
     *
     * @return array<\Spryker\Glue\DocumentationGeneratorRestApiExtension\Dependency\Plugin\ResourceRoutePluginsProviderPluginInterface>
     */
    protected function getResourceRoutePluginProviderPlugins(): array
    {
        return [
            new ResourceRoutePluginsProviderPlugin(),
        ];
    }

    /**
     * @SuppressWarnings(LayerAccessRule) Legacy: documentation generator registers Glue provider plugins. Do not suppress for new code.
     *
     * @return array<\Spryker\Glue\DocumentationGeneratorRestApiExtension\Dependency\Plugin\ResourceRelationshipCollectionProviderPluginInterface>
     */
    protected function getResourceRelationshipCollectionProviderPlugins(): array
    {
        return [
            new ResourceRelationshipCollectionProviderPlugin(),
        ];
    }
}
