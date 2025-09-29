<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Client\OpenAi;

use GuzzleHttp\Client as GuzzleHttpClient;
use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;

class OpenAiDependencyProvider extends AbstractDependencyProvider
{
    public const CLIENT_HTTP = 'CLIENT_HTTP';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);

        $container = $this->addHttpClient($container);

        return $container;
    }

    protected function addHttpClient(Container $container)
    {
        $container->set(static::CLIENT_HTTP, function () {
            return new GuzzleHttpClient();
        });

        return $container;
    }
}
