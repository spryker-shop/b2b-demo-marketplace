<?php

namespace Go\Zed\DynamicEntityGui\Communication\Response\SchemaFileResponse;

use Spryker\Zed\DynamicEntityGui\Dependency\Facade\DynamicEntityGuiToStorageFacadeInterface;
use Spryker\Zed\DynamicEntityGui\DynamicEntityGuiConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SchemaFileResponseBuilder extends \Spryker\Zed\DynamicEntityGui\Communication\Response\SchemaFileResponse\SchemaFileResponseBuilder
{
    public function __construct(
        DynamicEntityGuiConfig $config,
        DynamicEntityGuiToStorageFacadeInterface $storageFacade,
        protected \Pyz\Client\Storage\StorageClientInterface $storageGoClient,
    ) {
        parent::__construct($config, $storageFacade);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function createResponse(): BinaryFileResponse
    {
        $backendApiSchemaStorageKey = $this->config->getBackendApiSchemaStorageKey();
        $schemaData = $this->storageGoClient->getCacheService()->get($backendApiSchemaStorageKey);

        $response = new BinaryFileResponse($this->createTemporaryFile($schemaData));
        $response->headers->set(static::CONTENT_TYPE_HEADER_NAME, static::CONTENT_TYPE_HEADER_VALUE);
        $response->headers->set(
            static::CONTENT_DISPOSITION_HEADER_NAME,
            sprintf(
                static::CONTENT_DISPOSITION_HEADER_VALUE,
                $this->config->getDownloadFileName(),
            ),
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
