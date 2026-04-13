<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\Router;

use Pyz\Yves\Router\RouterDependencyProvider as PyzRouterDependencyProvider;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Plugin\Router\SearchByImageRouteProviderPlugin;

class RouterDependencyProvider extends PyzRouterDependencyProvider
{
    /**
     * @return array<\Spryker\Yves\RouterExtension\Dependency\Plugin\RouteProviderPluginInterface>
     */
    protected function getRouteProvider(): array
    {
        return array_merge(parent::getRouteProvider(), [
            new SearchByImageRouteProviderPlugin(),
        ]);
    }
}
