<?php

namespace Go\Glue\DocumentationGeneratorApi;

use Go\Glue\DocumentationGeneratorApi\Generator\DocumentationGenerator;
use Spryker\Glue\DocumentationGeneratorApi\Generator\DocumentationGeneratorInterface;

class DocumentationGeneratorApiFactory extends \Spryker\Glue\DocumentationGeneratorApi\DocumentationGeneratorApiFactory
{
    /**
     * @return \Spryker\Glue\DocumentationGeneratorApi\Generator\DocumentationGeneratorInterface
     */
    public function createDocumentationGenerator(): DocumentationGeneratorInterface
    {
        return new DocumentationGenerator(
            $this->getApiApplicationProviderPlugins(),
            $this->getContextExpanderPlugins(),
            $this->getFilesystem(),
            $this->getConfig(),
            $this->getSchemaFormatterPlugins(),
            $this->getContentGeneratorStrategyPlugin(),
            $this->getStorageClient(),
            $this->getUtilEncodingService(),
            $this->getContainer()->getLocator()->tenantBehavior()->client(),
            $this->getContainer()->getLocator()->storage()->client(),
        );
    }
}
