<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    // Skip API Platform route import when no resources have been generated yet
    // (e.g. on branches without API Platform migrations or before `glue api:generate` runs).
    if (!is_dir(dirname(__DIR__, 2) . '/src/Generated/Api/Storefront')) {
        return;
    }

    $routingConfigurator
        ->import('.', 'api_platform')
        ->prefix('/');
};
