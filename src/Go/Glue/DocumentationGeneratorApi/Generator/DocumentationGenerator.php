<?php

namespace Go\Glue\DocumentationGeneratorApi\Generator;

use Generated\Shared\Transfer\ApiApplicationSchemaContextTransfer;
use Spryker\Glue\DocumentationGeneratorApi\Dependency\Client\DocumentationGeneratorApiToStorageClientInterface;
use Spryker\Glue\DocumentationGeneratorApi\Dependency\External\DocumentationGeneratorApiToFilesystemInterface;
use Spryker\Glue\DocumentationGeneratorApi\Dependency\Service\DocumentationGenerationApiToUtilEncodingServiceInterface;
use Spryker\Glue\DocumentationGeneratorApi\DocumentationGeneratorApiConfig;
use Spryker\Glue\DocumentationGeneratorApi\Expander\ContextExpanderCollectionInterface;
use Spryker\Glue\DocumentationGeneratorApiExtension\Dependency\Plugin\ApiApplicationProviderPluginInterface;
use Spryker\Glue\DocumentationGeneratorApiExtension\Dependency\Plugin\ContentGeneratorStrategyPluginInterface;

class DocumentationGenerator extends \Spryker\Glue\DocumentationGeneratorApi\Generator\DocumentationGenerator
{
    public function __construct(
        array $apiApplicationProviderPlugins,
        ContextExpanderCollectionInterface $contextExpanderCollection,
        DocumentationGeneratorApiToFilesystemInterface $filesystem,
        DocumentationGeneratorApiConfig $documentationGeneratorApiConfig,
        array $schemaFormatterPlugins,
        ContentGeneratorStrategyPluginInterface $contentGeneratorStrategyPlugin,
        DocumentationGeneratorApiToStorageClientInterface $storageClient,
        DocumentationGenerationApiToUtilEncodingServiceInterface $utilEncodingService,
        protected \Go\Client\TenantBehavior\TenantBehaviorClientInterface $tenantBehaviorClient,
    ) {
        parent::__construct($apiApplicationProviderPlugins, $contextExpanderCollection, $filesystem, $documentationGeneratorApiConfig, $schemaFormatterPlugins, $contentGeneratorStrategyPlugin, $storageClient, $utilEncodingService);
    }

    protected function initContext(ApiApplicationProviderPluginInterface $apiApplicationProviderPlugin): ApiApplicationSchemaContextTransfer
    {
        $applicationName = $apiApplicationProviderPlugin->getName();

        return (new ApiApplicationSchemaContextTransfer())
            ->setApplication($applicationName)
            ->setFileName($this->documentationGeneratorApiConfig->getGeneratedFullFileName($applicationName . '_' . $this->tenantBehaviorClient->getCurrentTenantReference()));
    }
}
