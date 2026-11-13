<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\CartPage\Controller;

use Spryker\Yves\Kernel\View\View;
use SprykerShop\Yves\CartPage\Controller\CartController as SprykerCartController;
use SprykerShop\Yves\CartPage\Plugin\Router\CartPageRouteProviderPlugin;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \Pyz\Yves\CartPage\CartPageFactory getFactory()
 */
class CartController extends SprykerCartController
{
    /**
     * @var string
     */
    protected const PARAM_REFERER = 'referer';

    /**
     * @param array<string, mixed> $selectedAttributes
     *
     * @return array<string, mixed>
     */
    protected function executeIndexAction(array $selectedAttributes = [], bool $withItems = true): array
    {
        $viewData = parent::executeIndexAction($selectedAttributes, $withItems);
        $cartItems = $viewData['cartItems'];

        $viewData['products'] = $this->getFactory()
            ->createCartItemsProductsProvider()
            ->getItemsProducts($cartItems, $this->getLocale());

        return $viewData;
    }

    protected const VIEW_CART_ITEM_DETAILS = '@CartPage/views/cart-item-details/cart-item-details.twig';

    /**
     * Renders the lazy-loaded details panel (comment, asset, service point, options, bundle) of a single cart item.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function itemDetailsAction(string $groupKey): View
    {
        $quoteTransfer = $this->getFactory()->getCartClient()->getQuote();
        $listItemTransfer = null;

        foreach ($this->getFactory()->createCartItemReader()->getCartItems($quoteTransfer) as $itemTransfer) {
            if ($itemTransfer->getGroupKey() === $groupKey) {
                $listItemTransfer = $itemTransfer;

                break;
            }
        }

        if ($listItemTransfer === null) {
            throw new NotFoundHttpException();
        }

        return $this->view(
            [
                'cart' => $quoteTransfer,
                'listItem' => $listItemTransfer,
                'isQuoteEditable' => $this->getFactory()->getQuoteClient()->isQuoteEditable($quoteTransfer),
                'isCartActionsAsyncModeEnabled' => $this->getFactory()->getConfig()->isCartActionsAsyncModeEnabled(),
            ],
            [],
            static::VIEW_CART_ITEM_DETAILS,
        );
    }

    /**
     * @param string $sku
     */
    public function addAction(Request $request, $sku): RedirectResponse // phpcs:ignore
    {
        parent::addAction($request, $sku);

        return $this->redirectToReferer($request);
    }

    protected function redirectToReferer(Request $request): RedirectResponse
    {
        return $request->headers->has(static::PARAM_REFERER) ?
            $this->redirectResponseExternal($request->headers->get(static::PARAM_REFERER))
            : $this->redirectResponseInternal(CartPageRouteProviderPlugin::ROUTE_NAME_CART);
    }
}
