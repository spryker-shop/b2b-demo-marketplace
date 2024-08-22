<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonPage\Controller;

use Generated\Shared\Transfer\ProductComparisonTransfer;
use Pyz\Yves\ProductComparisonPage\Plugin\Router\ProductComparisonPageRouteProviderPlugin;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * @method \Pyz\Yves\ProductComparisonPage\ProductComparisonPageFactory getFactory()
 */
class ProductComparisonController extends AbstractController
{
    private const PARAM_REFERER = 'referer';

    private const REQUEST_PARAMETER_TOKEN = '_token';

    private const ID_CSRF_TOKEN_ADD_FORM = 'product_comparison_add_item_form';

    private const ID_CSRF_TOKEN_REMOVE_FORM = 'product_comparison_remove_item_form';

    private const ID_CSRF_TOKEN_REMOVE_ALL_FORM = 'product_comparison_remove_all_form';

    private const GLOSSARY_KEY_ERROR_MESSAGE_RESTRICT_ERROR = 'product_comparison.error_message.restrict_error';

    private const GLOSSARY_KEY_FORM_CSRF_VALIDATION_ERROR = 'form.csrf.error.text';

    private const GLOSSARY_KEY_SUCCESS_MESSAGE_ADD_SUCCESS = 'product_comparison.success_message.add_success';

    private const GLOSSARY_KEY_SUCCESS_MESSAGE_REMOVE_SUCCESS = 'product_comparison.success_message.remove_success';

    private const GLOSSARY_KEY_SUCCESS_ALL_ITEMS_REMOVED = 'product_comparison.success_message.remove_all_items';

    public function indexAction(): View
    {
        $productComparisonTransfer = $this->getProductComparisonTransfer();

        $viewData = $this->getFactory()
            ->createProductComparisonPageViewBuilder()
            ->getViewData($productComparisonTransfer);

        return $this->view(
            ['comparisonData' => $viewData],
            [],
            '@ProductComparisonPage/views/product-comparison/product-comparison.twig',
        );
    }

    public function addAction(Request $request, int $idProductAbstract): RedirectResponse
    {
        if (!$this->isValidCsrfToken($request, self::ID_CSRF_TOKEN_ADD_FORM)) {
            $this->addErrorMessage(self::GLOSSARY_KEY_FORM_CSRF_VALIDATION_ERROR);

            return $this->redirectToReferer($request);
        }

        $productComparisonTransfer = $this->getProductComparisonTransfer();
        if (!$this->getFactory()->createComparisonValidator()->isValidNumberOfProductsForComparison($productComparisonTransfer->getProductAbstractIds())) {
            $this->addErrorMessage(self::GLOSSARY_KEY_ERROR_MESSAGE_RESTRICT_ERROR);

            return $this->redirectToReferer($request);
        }

        if (!in_array($idProductAbstract, $productComparisonTransfer->getProductAbstractIds())) {
            $productComparisonTransfer->addIdProductAbstract($idProductAbstract);
        }

        $this->getFactory()
            ->getProductComparisonClient()
            ->save($productComparisonTransfer);

        $this->addSuccessMessage(self::GLOSSARY_KEY_SUCCESS_MESSAGE_ADD_SUCCESS);

        return $this->redirectToReferer($request);
    }

    public function removeAction(Request $request, int $idProductAbstract): RedirectResponse
    {
        if (!$this->isValidCsrfToken($request, self::ID_CSRF_TOKEN_REMOVE_FORM)) {
            $this->addErrorMessage(self::GLOSSARY_KEY_FORM_CSRF_VALIDATION_ERROR);

            return $this->redirectToReferer($request);
        }

        $productComparisonTransfer = $this->getProductComparisonTransfer();
        $productAbstractIds = $productComparisonTransfer->getProductAbstractIds();
        $productAbstractIds = array_filter($productAbstractIds, function ($existingIdProductAbstract) use ($idProductAbstract) {
            return (int)$existingIdProductAbstract !== $idProductAbstract;
        });

        $productComparisonTransfer->setProductAbstractIds($productAbstractIds);

        $this->getFactory()->getProductComparisonClient()->save($productComparisonTransfer);
        $this->addSuccessMessage(self::GLOSSARY_KEY_SUCCESS_MESSAGE_REMOVE_SUCCESS);

        return $this->redirectToReferer($request);
    }

    public function removeAllAction(Request $request): RedirectResponse
    {
        if (!$this->isValidCsrfToken($request, self::ID_CSRF_TOKEN_REMOVE_ALL_FORM)) {
            $this->addErrorMessage(self::GLOSSARY_KEY_FORM_CSRF_VALIDATION_ERROR);

            return $this->redirectToReferer($request);
        }

        $productComparisonTransfer = $this->prepareProductComparisonTransfer();

        $this->getFactory()->getProductComparisonClient()->delete($productComparisonTransfer);
        $this->addSuccessMessage(self::GLOSSARY_KEY_SUCCESS_ALL_ITEMS_REMOVED);

        return $this->redirectToReferer($request);
    }

    private function getProductComparisonTransfer(): ProductComparisonTransfer
    {
        $productComparisonTransfer = $this->prepareProductComparisonTransfer();

        return $this->getFactory()
            ->getProductComparisonClient()
            ->get($productComparisonTransfer);
    }

    private function getIdCustomer(): ?int
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer) {
            return $customerTransfer->getIdCustomer();
        }

        return null;
    }

    private function prepareProductComparisonTransfer(): ProductComparisonTransfer
    {
        return (new ProductComparisonTransfer())
            ->setIdCustomer($this->getIdCustomer());
    }

    private function redirectToReferer(Request $request): RedirectResponse
    {
        return $request->headers->has(self::PARAM_REFERER) ?
            $this->redirectResponseExternal($request->headers->get(self::PARAM_REFERER))
            : $this->redirectResponseInternal(ProductComparisonPageRouteProviderPlugin::ROUTE_NAME_PRODUCT_COMPARISON_LIST);
    }

    private function isValidCsrfToken(Request $request, string $idCsrfToken): bool
    {
        $csrfToken = new CsrfToken($idCsrfToken, $request->get(self::REQUEST_PARAMETER_TOKEN));

        return $this->getFactory()->getCsrfTokenManager()->isTokenValid($csrfToken);
    }
}
