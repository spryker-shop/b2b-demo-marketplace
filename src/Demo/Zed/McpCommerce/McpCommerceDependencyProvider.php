<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class McpCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_CONFIGURATION = 'FACADE_CONFIGURATION';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addConfigurationFacade($container);

        return $container;
    }

    protected function addConfigurationFacade(Container $container): Container
    {
        $container->set(static::FACADE_CONFIGURATION, function (Container $container) {
            return $container->getLocator()->configuration()->facade();
        });

        return $container;
    }
}
