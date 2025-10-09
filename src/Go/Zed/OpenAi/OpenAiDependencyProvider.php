<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\OpenAi;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class OpenAiDependencyProvider extends AbstractBundleDependencyProvider
{
    public const CLIENT_OPEN_AI = 'CLIENT_OPEN_AI';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addOpenAiClient($container);

        return $container;
    }

    protected function addOpenAiClient(Container $container): Container
    {
        $container->set(static::CLIENT_OPEN_AI, function (Container $container) {
            return $container->getLocator()->openAi()->client();
        });

        return $container;
    }
}
