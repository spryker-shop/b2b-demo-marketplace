<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

/**
 * @see config/README.md for more information about this configuration.
 */
use Symfony\Config\ApiPlatformConfig;

return static function (ApiPlatformConfig $apiPlatform, string $env): void {
    $apiPlatform->title('Spryker Backend API');

    $apiPlatform->doctrine()->enabled(false);
    $apiPlatform->doctrineMongodbOdm()->enabled(false);
    $apiPlatform->mapping()->paths(['%kernel.project_dir%/src/Generated/Api/Backend']);

    if ($env === 'dockerdev') {
        $apiPlatform->enableSwagger(true);
        $apiPlatform->enableSwaggerUi(true);
        $apiPlatform->enableReDoc(true);
        $apiPlatform->enableEntrypoint(true);
        $apiPlatform->enableDocs(true);
        $apiPlatform->enableProfiler(true);
    }

    $apiPlatform->swagger()
        ->swaggerUiExtraConfiguration([
            'filter' => true,
            'docExpansion' => 'none',
        ])
        ->apiKeys('JWT', ['name' => 'Authorization', 'type' => 'header']);

    $apiPlatform->defaults()->paginationItemsPerPage(10);
    $apiPlatform->defaults()->filters(['spryker.api_platform.filter.property']);

    // Allow string "true"/"false" to be coerced to bool — the old Glue backend accepted
    // stringified booleans in request bodies (e.g. "isActive": "true").
    $apiPlatform->defaults()->denormalizationContext(['disable_type_enforcement' => true]);

    $apiPlatform->collection()
        ->existsParameterName('exists')
        ->order('ASC')
        ->orderParameterName('order')
        ->pagination()
            ->pageParameterName('page')
            ->enabledParameterName('pagination')
            ->itemsPerPageParameterName('itemsPerPage')
            ->partialParameterName('partial');

    $apiPlatform->formats('jsonapi', ['mime_types' => ['application/vnd.api+json', 'application/json']]);
    $apiPlatform->formats('jsonapi', ['mime_types' => ['application/vnd.api+json']]);
    $apiPlatform->formats('xml', ['mime_types' => ['application/xml', 'text/xml']]);
    $apiPlatform->formats('csv', ['mime_types' => ['text/csv']]);

    $apiPlatform->patchFormats('jsonapi', ['mime_types' => ['application/vnd.api+json', 'application/json']]);

    $apiPlatform->errorFormats('jsonapi', ['mime_types' => ['application/vnd.api+json', 'application/json']]);
};
