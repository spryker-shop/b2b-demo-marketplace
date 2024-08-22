<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonPage\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

class ProductComparisonPageRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    public const ROUTE_NAME_PRODUCT_COMPARISON_LIST = 'product-comparison-list';

    public const ROUTE_NAME_PRODUCT_COMPARISON_ADD = 'product-comparison/add';

    public const ROUTE_NAME_PRODUCT_COMPARISON_REMOVE = 'product-comparison/remove';

    public const ROUTE_NAME_PRODUCT_COMPARISON_REMOVE_ALL = 'product-comparison/remove-all';

    private const ID_PRODUCT_ABSTRACT_PATTERN = '[0-9]+';

    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addProductComparisonListRoute($routeCollection);
        $routeCollection = $this->addProductComparisonAddRoute($routeCollection);
        $routeCollection = $this->addRemoveProductComparisonRoute($routeCollection);
        $routeCollection = $this->addRemoveAllProductComparisonRoute($routeCollection);

        return $routeCollection;
    }

    private function addProductComparisonListRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(self::ROUTE_NAME_PRODUCT_COMPARISON_LIST, 'ProductComparisonPage', 'ProductComparison');
        $route = $route->setMethods(Request::METHOD_GET);
        $routeCollection->add(self::ROUTE_NAME_PRODUCT_COMPARISON_LIST, $route);

        return $routeCollection;
    }

    private function addProductComparisonAddRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(self::ROUTE_NAME_PRODUCT_COMPARISON_ADD . '/{idProductAbstract}', 'ProductComparisonPage', 'ProductComparison', 'addAction');
        $route = $route->setRequirement('idProductAbstract', self::ID_PRODUCT_ABSTRACT_PATTERN);
        $route = $route->setMethods(Request::METHOD_GET);
        $routeCollection->add(self::ROUTE_NAME_PRODUCT_COMPARISON_ADD, $route);

        return $routeCollection;
    }

    private function addRemoveProductComparisonRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(self::ROUTE_NAME_PRODUCT_COMPARISON_REMOVE . '/{idProductAbstract}', 'ProductComparisonPage', 'ProductComparison', 'removeAction');
        $route = $route->setRequirement('idProductAbstract', self::ID_PRODUCT_ABSTRACT_PATTERN);
        $route = $route->setMethods(Request::METHOD_GET);
        $routeCollection->add(self::ROUTE_NAME_PRODUCT_COMPARISON_REMOVE, $route);

        return $routeCollection;
    }

    private function addRemoveAllProductComparisonRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(self::ROUTE_NAME_PRODUCT_COMPARISON_REMOVE_ALL, 'ProductComparisonPage', 'ProductComparison', 'removeAllAction');
        $route = $route->setMethods(Request::METHOD_GET);
        $routeCollection->add(self::ROUTE_NAME_PRODUCT_COMPARISON_REMOVE_ALL, $route);

        return $routeCollection;
    }
}
