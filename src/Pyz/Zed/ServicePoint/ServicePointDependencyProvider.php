<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ServicePoint;

use Orm\Zed\ServicePoint\Persistence\SpyServicePointQuery;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\ServicePoint\Dependency\Facade\ServicePointToCountryFacadeBridge;
use Spryker\Zed\ServicePoint\Dependency\Facade\ServicePointToStoreFacadeBridge;

/**
 * @method \Spryker\Zed\ServicePoint\ServicePointConfig getConfig()
 */
class ServicePointDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_STORE = 'FACADE_STORE';

    /**
     * @var string
     */
    public const FACADE_COUNTRY = 'FACADE_COUNTRY';

    public const string PROPEL_QUERY_SERVICE_POINT = 'PROPEL_QUERY_SERVICE_POINT';

    public const string PLUGINS_SERVICE_POINT_VIEW_SECTION = 'PLUGINS_SERVICE_POINT_VIEW_SECTION';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container = $this->addStoreFacade($container);
        $container = $this->addCountryFacade($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);

        $container = $this->addServicePointPropelQuery($container);
        $container = $this->addServicePointViewSectionPlugins($container);

        return $container;
    }

    protected function addServicePointPropelQuery(Container $container): Container
    {
        $container->set(static::PROPEL_QUERY_SERVICE_POINT, $container->factory(function () {
            return SpyServicePointQuery::create();
        }));

        return $container;
    }

    protected function addServicePointViewSectionPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_SERVICE_POINT_VIEW_SECTION, function () {
            return $this->getServicePointViewSectionPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\ServicePoint\Dependency\Plugin\ServicePointViewSectionPluginInterface>
     */
    protected function getServicePointViewSectionPlugins(): array
    {
        return [];
    }

    protected function addCountryFacade(Container $container): Container
    {
        $container->set(static::FACADE_COUNTRY, function (Container $container) {
            return new ServicePointToCountryFacadeBridge(
                $container->getLocator()->country()->facade(),
            );
        });

        return $container;
    }

    protected function addStoreFacade(Container $container): Container
    {
        $container->set(static::FACADE_STORE, function (Container $container) {
            return new ServicePointToStoreFacadeBridge(
                $container->getLocator()->store()->facade(),
            );
        });

        return $container;
    }
}
