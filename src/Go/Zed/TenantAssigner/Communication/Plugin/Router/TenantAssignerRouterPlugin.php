<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantAssigner\Communication\Plugin\Router;

use Spryker\Zed\Router\Plugin\Router\AbstractZedRouterPlugin;
use Spryker\Zed\Router\Route\RouteCollection;

class TenantAssignerRouterPlugin extends AbstractZedRouterPlugin
{
    /**
     * @param \Spryker\Zed\Router\Route\RouteCollection $routeCollection
     *
     * @return \Spryker\Zed\Router\Route\RouteCollection
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addTenantAssignerIndexRoute($routeCollection);
        $routeCollection = $this->addTenantAssignerTableRoute($routeCollection);
        $routeCollection = $this->addTenantAssignerAssignTenantRoute($routeCollection);
        $routeCollection = $this->addTenantAssignerBulkAssignTenantRoute($routeCollection);

        return $routeCollection;
    }

    /**
     * @param \Spryker\Zed\Router\Route\RouteCollection $routeCollection
     *
     * @return \Spryker\Zed\Router\Route\RouteCollection
     */
    protected function addTenantAssignerIndexRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/tenant-assigner', 'TenantAssigner', 'Index', 'index');
        $routeCollection->add('tenant-assigner', $route);

        return $routeCollection;
    }

    /**
     * @param \Spryker\Zed\Router\Route\RouteCollection $routeCollection
     *
     * @return \Spryker\Zed\Router\Route\RouteCollection
     */
    protected function addTenantAssignerTableRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/tenant-assigner/index/table', 'TenantAssigner', 'Index', 'table');
        $routeCollection->add('tenant-assigner/table', $route);

        return $routeCollection;
    }

    /**
     * @param \Spryker\Zed\Router\Route\RouteCollection $routeCollection
     *
     * @return \Spryker\Zed\Router\Route\RouteCollection
     */
    protected function addTenantAssignerAssignTenantRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/tenant-assigner/index/assign-tenant', 'TenantAssigner', 'Index', 'assignTenant');
        $route->setMethods(['POST']);
        $routeCollection->add('tenant-assigner/assign-tenant', $route);

        return $routeCollection;
    }

    /**
     * @param \Spryker\Zed\Router\Route\RouteCollection $routeCollection
     *
     * @return \Spryker\Zed\Router\Route\RouteCollection
     */
    protected function addTenantAssignerBulkAssignTenantRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/tenant-assigner/index/bulk-assign-tenant', 'TenantAssigner', 'Index', 'bulkAssignTenant');
        $route->setMethods(['POST']);
        $routeCollection->add('tenant-assigner/bulk-assign-tenant', $route);

        return $routeCollection;
    }
}
