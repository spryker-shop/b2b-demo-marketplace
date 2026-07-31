<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\CustomerPage\Controller;

use Generated\Shared\Transfer\CustomerTransfer;
use SprykerShop\Shared\CustomerPage\CustomerPageConfig;
use SprykerShop\Yves\CustomerPage\Controller\PasswordController as SprykerPasswordController;
use SprykerShop\Yves\CustomerPage\Form\RestorePasswordForm;
use SprykerShop\Yves\CustomerPage\Plugin\Router\CustomerPageRouteProviderPlugin;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pyz\Yves\CustomerPage\CustomerPageFactory getFactory()
 */
class PasswordController extends SprykerPasswordController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    protected function executeRestorePasswordAction(Request $request): RedirectResponse|array
    {
        if ($request->query->get(CustomerPageConfig::URL_PARAM_LOCALE)) {
            return $this->redirectWithLocale(
                CustomerPageRouteProviderPlugin::ROUTE_NAME_PASSWORD_RESTORE,
                (string)$request->query->get(CustomerPageConfig::URL_PARAM_LOCALE),
                ['token' => $request->query->get('token')],
            );
        }

        if ($this->isLoggedInCustomer()) {
            $this->addErrorMessage('customer.reset.password.error.already.loggedIn');

            return $this->redirectResponseInternal('home');
        }

        $form = $this
            ->getFactory()
            ->createCustomerFormFactory()
            ->getFormRestorePassword()
            ->setData([
                RestorePasswordForm::FIELD_RESTORE_PASSWORD_KEY => $request->query->get('token'),
            ])
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerTransfer = new CustomerTransfer();
            $customerTransfer->fromArray($form->getData());

            $customerResponseTransfer = $this->getFactory()
                ->getCustomerClient()
                ->restorePassword($customerTransfer);

            if ($customerResponseTransfer->getIsSuccess()) {
                $this->getFactory()
                    ->getCustomerClient()
                    ->logout();

                $this->getFactory()->createAuditLogger()->addPasswordUpdatedAfterResetAuditLog($customerResponseTransfer);

                return [
                    'form' => $form->createView(),
                    'isSuccess' => true,
                ];
            }

            $this->processResponseErrors($customerResponseTransfer);
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
