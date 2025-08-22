<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Controller;

use Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function approveAction(Request $request): JsonResponse
    {
        $idTenantRegistration = (int)$request->get('id');
        
        $responseTransfer = $this->getFacade()->acceptRegistration($idTenantRegistration);

        return $this->jsonResponse([
            'success' => $responseTransfer->getIsSuccessful(),
            'message' => $responseTransfer->getIsSuccessful() 
                ? 'Tenant registration approved successfully'
                : 'Failed to approve tenant registration'
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function declineAction(Request $request): JsonResponse
    {
        $idTenantRegistration = (int)$request->get('id');
        $reason = $request->get('reason', 'Registration declined by administrator');
        
        $responseTransfer = $this->getFacade()->declineRegistration($idTenantRegistration, $reason);

        return $this->jsonResponse([
            'success' => $responseTransfer->getIsSuccessful(),
            'message' => $responseTransfer->getIsSuccessful() 
                ? 'Tenant registration declined successfully'
                : 'Failed to decline tenant registration'
        ]);
    }
}