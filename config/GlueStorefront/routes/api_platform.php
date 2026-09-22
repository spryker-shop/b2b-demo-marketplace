<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    if (!is_dir(dirname(__DIR__, 2) . '/src/Generated/Api/Storefront')) {
        return;
    }

    $routingConfigurator->import('.', 'api_platform')
        ->prefix('/');
};
