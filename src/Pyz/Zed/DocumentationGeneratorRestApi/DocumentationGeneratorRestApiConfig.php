<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DocumentationGeneratorRestApi;

use Spryker\Zed\DocumentationGeneratorRestApi\DocumentationGeneratorRestApiConfig as SprykerDocumentationGeneratorRestApiConfig;

class DocumentationGeneratorRestApiConfig extends SprykerDocumentationGeneratorRestApiConfig
{
    public function getPathVersionPrefix(): string
    {
        return 'v';
    }

    public function getPathVersionResolving(): bool
    {
        return true;
    }

    public function isNestedRelationshipsEnabled(): bool
    {
        return true;
    }

    /**
     * The parent's command omits `--spec-version`, so API Platform's exporter defaults to `3` and
     * emits the current OpenAPI minor (3.2.0 as of `spryker/api-platform` 1.40.0) — including
     * `type: [string, 'null']` for nullable properties, which is only valid from 3.1 on.
     * {@see \ApiPlatform\OpenApi\Serializer\LegacyOpenApiNormalizer} only downgrades that back to
     * `type: string` + `nullable: true` when `spec_version` is the exact string `3.0.0`, and the
     * combined document this feeds into ({@see \Spryker\Zed\DocumentationGeneratorRestApi\Business\Merger\OpenApiMerger})
     * always declares itself `openapi: 3.0.0` — so the embedded fragment has to match, or `speccy
     * lint --rules=default` rejects the array-form `type` as invalid OpenAPI 3.0.
     */
    public function getApiPlatformExportCommand(): string
    {
        return parent::getApiPlatformExportCommand() . ' --spec-version=3.0.0';
    }
}
