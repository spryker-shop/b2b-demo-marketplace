<?php

namespace Go\Glue\DocumentationGeneratorApi;

use Spryker\Glue\Kernel\Locator;

class DocumentationGeneratorApiConfig extends \Spryker\Glue\DocumentationGeneratorApi\DocumentationGeneratorApiConfig
{

    /**
     * Specification:
     * - Returns file path with generated documentation.
     *
     * @api
     *
     * @param string $applicationName
     *
     * @return string
     */
    public function getGeneratedFullFileName(string $applicationName): string
    {
        /** @var \Generated\Glue\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface $locator */
        $locator = Locator::getInstance();

        return sprintf(
            '%s/src/Generated/Glue%s/Specification/spryker_%s_api.schema.yml',
            APPLICATION_ROOT_DIR,
            ucfirst($applicationName),
            strtolower($applicationName . '_' . $locator->tenantBehavior()->client()->getCurrentTenantReference()),
        );
    }
}
