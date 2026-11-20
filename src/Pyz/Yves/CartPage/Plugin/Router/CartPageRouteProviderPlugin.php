<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\CartPage\Plugin\Router;

use Spryker\Yves\Router\Route\RouteCollection;
use SprykerShop\Yves\CartPage\Plugin\Router\CartPageRouteProviderPlugin as SprykerCartPageRouteProviderPlugin;
use Symfony\Component\HttpFoundation\Request;

class CartPageRouteProviderPlugin extends SprykerCartPageRouteProviderPlugin
{
    /**
     * @var string
     */
    public const ROUTE_NAME_CART_ITEM_DETAILS = 'cart/item-details';

    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = parent::addRoutes($routeCollection);
        $routeCollection = $this->addCartItemDetailsRoute($routeCollection);

        return $routeCollection;
    }

    /**
     * @uses \Pyz\Yves\CartPage\Controller\CartController::itemDetailsAction()
     */
    protected function addCartItemDetailsRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/cart/item-details/{groupKey}', 'CartPage', 'Cart', 'itemDetailsAction');
        $route = $route->setRequirement('groupKey', '[^/]+');
        $route = $route->setMethods(Request::METHOD_GET);
        $routeCollection->add(static::ROUTE_NAME_CART_ITEM_DETAILS, $route);

        return $routeCollection;
    }
}
