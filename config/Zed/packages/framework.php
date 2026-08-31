<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

/**
 * @see config/README.md for more information about this configuration.
 */
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework, ContainerConfigurator $container, string $env): void {
    $framework->secret('spryker-zed-secret');

    $framework->assets([
            'base_path' => '/assets',
        ]);

    $framework->test('%kernel.environment%' === 'dockerdev');
    $container->parameters()->set('.container.dumper.inline_factories', $env !== 'dockerdev');
};
