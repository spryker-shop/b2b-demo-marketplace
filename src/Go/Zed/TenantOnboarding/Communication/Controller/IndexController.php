<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class IndexController extends AbstractController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function indexAction(Request $request): array
    {
        $table = $this->getFactory()->createTenantRegistrationTable();

        return $this->viewResponse([
            'tenantRegistrationTable' => $table->render(),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function tableAction(): JsonResponse
    {
        $table = $this->getFactory()->createTenantRegistrationTable();

        return $this->jsonResponse(
            $table->fetchData()
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function approveAction(Request $request)
    {
        $idTenantRegistration = (int)$request->get('id');

        $responseTransfer = $this->getFacade()->acceptRegistration($idTenantRegistration);

        if ($request->isXmlHttpRequest()) {
            // AJAX request - return JSON
            return $this->jsonResponse([
                'success' => $responseTransfer->getIsSuccessful(),
                'message' => $responseTransfer->getIsSuccessful()
                    ? 'Tenant registration approved successfully'
                    : 'Failed to approve tenant registration',
                'redirect' => $responseTransfer->getIsSuccessful() ? '/tenant-onboarding' : null
            ]);
        }

        // Regular browser request - redirect with flash message
        if ($responseTransfer->getIsSuccessful()) {
            $this->addSuccessMessage('Tenant registration approved successfully');
        } else {
            $this->addErrorMessage('Failed to approve tenant registration');
        }

        return $this->redirectResponse('/tenant-onboarding');
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function declineAction(Request $request)
    {
        $idTenantRegistration = (int)$request->get('id');
        $reason = $request->get('reason', 'Registration declined by administrator');

        $responseTransfer = $this->getFacade()->declineRegistration($idTenantRegistration, $reason);

        if ($request->isXmlHttpRequest()) {
            // AJAX request - return JSON
            return $this->jsonResponse([
                'success' => $responseTransfer->getIsSuccessful(),
                'message' => $responseTransfer->getIsSuccessful()
                    ? 'Tenant registration declined successfully'
                    : 'Failed to decline tenant registration',
                'redirect' => $responseTransfer->getIsSuccessful() ? '/tenant-onboarding' : null
            ]);
        }

        // Regular browser request - redirect with flash message
        if ($responseTransfer->getIsSuccessful()) {
            $this->addSuccessMessage('Tenant registration declined successfully');
        } else {
            $this->addErrorMessage('Failed to decline tenant registration');
        }

        return $this->redirectResponse('/tenant-onboarding');
    }
}
