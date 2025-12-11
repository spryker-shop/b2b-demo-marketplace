<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

/**
 * @see config/README.md for more information about this configuration.
 */
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $framework->secret('spryker-glue-secret');

    $framework->assets([
        'base_path' => '/assets',
    ]);

    $framework->test('%kernel.environment%' === 'dockerdev');
};
