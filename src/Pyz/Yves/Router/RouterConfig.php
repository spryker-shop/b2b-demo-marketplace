<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\Router;

use Spryker\Client\Kernel\Container;
use Spryker\Yves\Router\RouterConfig as SprykerRouterConfig;

/**
 * @method \Spryker\Shared\Router\RouterConfig getSharedConfig()
 */
class RouterConfig extends SprykerRouterConfig
{
    /**
     * @SuppressWarnings(LocatorInDependencyProviderOnlyRule) Legacy: router config reads allowed languages at bootstrap, mirrors core RouterConfig. Do not suppress for new code.
     *
     * @see \Spryker\Yves\Router\Plugin\RouterEnhancer\LanguagePrefixRouterEnhancerPlugin
     *
     * @return array<string>
     */
    public function getAllowedLanguages(): array
    {
        return (new Container())->getLocator()->locale()->client()->getAllowedLanguages();
    }

    /**
     * @SuppressWarnings(LocatorInDependencyProviderOnlyRule) Legacy: router config reads store names at bootstrap, mirrors core RouterConfig. Do not suppress for new code.
     *
     * @return array<string>
     */
    public function getAllowedStores(): array
    {
        return (new Container())->getLocator()->storeStorage()->client()->getStoreNames();
    }
}
