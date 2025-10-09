<?php

namespace Go\Glue\DocumentationGeneratorApi\Generator;

use Spryker\Glue\DocumentationGeneratorApi\Dependency\Client\DocumentationGeneratorApiToStorageClientInterface;
use Spryker\Glue\DocumentationGeneratorApi\Dependency\External\DocumentationGeneratorApiToFilesystemInterface;
use Spryker\Glue\DocumentationGeneratorApi\Dependency\Service\DocumentationGenerationApiToUtilEncodingServiceInterface;
use Spryker\Glue\DocumentationGeneratorApi\DocumentationGeneratorApiConfig;
use Spryker\Glue\DocumentationGeneratorApi\Expander\ContextExpanderCollectionInterface;
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
        protected \Pyz\Client\Storage\StorageClientInterface $storageGoClient,
    ) {
        parent::__construct($apiApplicationProviderPlugins, $contextExpanderCollection, $filesystem, $documentationGeneratorApiConfig, $schemaFormatterPlugins, $contentGeneratorStrategyPlugin, $storageClient, $utilEncodingService);
    }

    /**
     * @param array<string> $applications
     *
     * @return void
     */
    public function generateDocumentation(array $applications = []): void
    {
        foreach ($this->apiApplicationProviderPlugins as $apiApplicationProviderPlugin) {
            if (!$applications || in_array($apiApplicationProviderPlugin->getName(), $applications)) {
                $apiApplicationSchemaContextTransfer = $this->initContext($apiApplicationProviderPlugin);
                $apiApplicationSchemaContextTransfer = $this->expandContext($apiApplicationProviderPlugin, $apiApplicationSchemaContextTransfer);
                $formattedData = $this->formatContext($apiApplicationSchemaContextTransfer);
                $documentationContent = $this->contentGeneratorStrategyPlugin->generateContent($formattedData);

                $this->filesystem->dumpFile($apiApplicationSchemaContextTransfer->getFileNameOrFail(), $documentationContent);
                $time = filemtime($apiApplicationSchemaContextTransfer->getFileNameOrFail());

                /** @var string $apiSchemaStorageData */
                $apiSchemaStorageData = $this->utilEncodingService->encodeJson(
                    [
                        static::FILE_DATA => $documentationContent,
                        static::CREATED_AT => $time,
                    ],
                );

                $this->storageGoClient->getCacheService()->set(
                    sprintf(
                        $this->documentationGeneratorApiConfig->getApiSchemaStorageKeyPattern(),
                        strtolower($apiApplicationProviderPlugin->getName() . ':' . $this->tenantBehaviorClient->getCurrentTenantReference()),
                    ),
                    $apiSchemaStorageData,
                );
            }
        }
    }
}
