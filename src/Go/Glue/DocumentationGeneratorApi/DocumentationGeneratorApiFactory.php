<?php

namespace Go\Glue\DocumentationGeneratorApi;

use Go\Glue\DocumentationGeneratorApi\Generator\DocumentationGenerator;
use Spryker\Glue\DocumentationGeneratorApi\Generator\DocumentationGeneratorInterface;
use Go\Glue\DocumentationGeneratorApi\InvalidationVerifier\InvalidationVerifier;
use Spryker\Glue\DocumentationGeneratorApi\InvalidationVerifier\InvalidationVerifierInterface;

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
        );
    }

    /**
     * @return \Spryker\Glue\DocumentationGeneratorApi\InvalidationVerifier\InvalidationVerifierInterface
     */
    public function createInvalidationVerifier(): InvalidationVerifierInterface
    {
        return new InvalidationVerifier(
            $this->getInvalidationVoterPlugins(),
            $this->getApiApplicationProviderPlugins(),
            $this->getConfig(),
            $this->getContainer()->getLocator()->tenantBehavior()->client(),
        );
    }
}
