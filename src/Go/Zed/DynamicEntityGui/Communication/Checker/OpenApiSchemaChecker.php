<?php

namespace Go\Zed\DynamicEntityGui\Communication\Checker;

use Spryker\Zed\DynamicEntityGui\Dependency\Facade\DynamicEntityGuiToDynamicEntityFacadeInterface;
use Spryker\Zed\DynamicEntityGui\Dependency\Facade\DynamicEntityGuiToStorageFacadeInterface;
use Spryker\Zed\DynamicEntityGui\DynamicEntityGuiConfig;

class OpenApiSchemaChecker extends \Spryker\Zed\DynamicEntityGui\Communication\Checker\OpenApiSchemaChecker
{
    public function __construct(
        DynamicEntityGuiConfig $config,
        DynamicEntityGuiToDynamicEntityFacadeInterface $dynamicEntityFacade,
        DynamicEntityGuiToStorageFacadeInterface $storageFacade,
        protected \Pyz\Client\Storage\StorageClientInterface $storageGoClient,
    ) {
        parent::__construct($config, $dynamicEntityFacade, $storageFacade);
    }


    /**
     * @return bool
     */
    public function isSchemaFileActual(): bool
    {
        $backendApiSchemaStorageKey = $this->config->getBackendApiSchemaStorageKey();
        $schemaData = $this->storageGoClient->getCacheService()->get($backendApiSchemaStorageKey);

        if ($schemaData === null) {
            return false;
        }

        return !$this->hasUpdatedConfigurations($schemaData[static::CREATED_AT]);
    }
}
