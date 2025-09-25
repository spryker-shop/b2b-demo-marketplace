<?php

namespace Go\Zed\DynamicEntityGui\Communication;

use Go\Zed\DynamicEntityGui\Communication\Checker\OpenApiSchemaChecker;
use Spryker\Zed\DynamicEntityGui\Communication\Checker\OpenApiSchemaCheckerInterface;
use Go\Zed\DynamicEntityGui\Communication\Response\SchemaFileResponse\SchemaFileResponseBuilder;
use Spryker\Zed\DynamicEntityGui\Communication\Response\SchemaFileResponse\SchemaFileResponseBuilderInterface;

class DynamicEntityGuiCommunicationFactory extends \Spryker\Zed\DynamicEntityGui\Communication\DynamicEntityGuiCommunicationFactory
{
    /**
     * @return \Spryker\Zed\DynamicEntityGui\Communication\Checker\OpenApiSchemaCheckerInterface
     */
    public function createOpenApiSchemaChecker(): OpenApiSchemaCheckerInterface
    {
        return new OpenApiSchemaChecker(
            $this->getConfig(),
            $this->getDynamicEntityFacade(),
            $this->getStorageFacade(),
            $this->storageGoClient(),
        );
    }

    /**
     * @return \Spryker\Zed\DynamicEntityGui\Communication\Response\SchemaFileResponse\SchemaFileResponseBuilderInterface
     */
    public function createSchemaFileResponseBuilder(): SchemaFileResponseBuilderInterface
    {
        return new SchemaFileResponseBuilder(
            $this->getConfig(),
            $this->getStorageFacade(),
            $this->storageGoClient(),
        );
    }

    public function storageGoClient(): \Pyz\Client\Storage\StorageClientInterface
    {
        return $this->getContainer()->getLocator()->storage()->client();
    }
}
