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

return static function (ApiPlatformConfig $apiPlatform): void {
    $apiPlatform->doctrine()->enabled(false);
    $apiPlatform->doctrineMongodbOdm()->enabled(false);
    $apiPlatform->mapping()->paths(['%kernel.project_dir%/src/Generated/Api/Storefront']);
    $apiPlatform->enableSwagger(true);
    $apiPlatform->enableSwaggerUi(true);
    $apiPlatform->enableReDoc(true);
    $apiPlatform->enableEntrypoint(true);
    $apiPlatform->enableDocs(true);
    $apiPlatform->enableProfiler('%kernel.environment%' === 'dockerdev');
    $apiPlatform->defaults()->paginationItemsPerPage(10);
    $apiPlatform->collection()
        ->existsParameterName('exists')
        ->order('ASC')
        ->orderParameterName('order')
        ->pagination()
            ->pageParameterName('page')
            ->enabledParameterName('pagination')
            ->itemsPerPageParameterName('itemsPerPage')
            ->partialParameterName('partial');

    $apiPlatform->formats('jsonld', ['mime_types' => ['application/ld+json']]);
    $apiPlatform->formats('jsonapi', ['mime_types' => ['application/vnd.api+json']]);
    $apiPlatform->formats('json', ['mime_types' => ['application/json']]);
    $apiPlatform->formats('xml', ['mime_types' => ['application/xml', 'text/xml']]);
    $apiPlatform->formats('yaml', ['mime_types' => ['application/x-yaml']]);
    $apiPlatform->formats('csv', ['mime_types' => ['text/csv']]);
    $apiPlatform->formats('jsonhal', ['mime_types' => ['application/hal+json']]);
};
