<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\CmsBlockCustomerGroup;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;

class CmsBlockCustomerGroupDependencyProvider extends AbstractDependencyProvider
{
    public const string CLIENT_ZED_REQUEST = 'CLIENT_ZED_REQUEST';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addZedRequestClient($container);

        return $container;
    }

    protected function addZedRequestClient(Container $container): Container
    {
        $container->set(static::CLIENT_ZED_REQUEST, function (Container $container) {
            return $container->getLocator()->zedRequest()->client();
        });

        return $container;
    }
}
