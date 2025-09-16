<?php
namespace Go\Client\OpenAi;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use GuzzleHttp\Client as GuzzleHttpClient;

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
