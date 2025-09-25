<?php

namespace Go\Glue\DocumentationGeneratorApi\InvalidationVerifier;

use Spryker\Glue\DocumentationGeneratorApi\DocumentationGeneratorApiConfig;

class InvalidationVerifier extends \Spryker\Glue\DocumentationGeneratorApi\InvalidationVerifier\InvalidationVerifier
{
    public function __construct(
        array $documentationInvalidationVoterPlugins,
        array $apiApplicationProviderPlugins,
        DocumentationGeneratorApiConfig $documentationGeneratorApiConfig,
        protected \Go\Client\TenantBehavior\TenantBehaviorClientInterface $tenantBehaviorClient,
    ) {
        parent::__construct($documentationInvalidationVoterPlugins, $apiApplicationProviderPlugins, $documentationGeneratorApiConfig);
    }

    /**
     * @param mixed $application
     *
     * @return bool
     */
    protected function hasSchemaFile(mixed $application): bool
    {
        foreach ($this->apiApplicationProviderPlugins as $apiApplicationProviderPlugin) {
            if (is_string($application) && $apiApplicationProviderPlugin->getName() !== $application) {
                continue;
            }
            if (!file_exists($this->documentationGeneratorApiConfig->getGeneratedFullFileName($apiApplicationProviderPlugin->getName() . '_' . $this->tenantBehaviorClient->getCurrentTenantReference()))) {
                return false;
            }
        }

        return true;
    }
}
