<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 * @method \Go\Zed\TenantOnboarding\Persistence\TenantOnboardingRepositoryInterface getRepository()
 */
class TenantController extends AbstractController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function indexAction(Request $request): array
    {
        $table = $this->getFactory()->createTenantTable();

        return $this->viewResponse([
            'tenantsTable' => $table->render(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function tableAction(Request $request)
    {
        $table = $this->getFactory()->createTenantTable();

        return $this->jsonResponse($table->fetchData());
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function viewAction(Request $request): array
    {
        $idTenant = $this->castId($request->query->get('id-tenant'));
        $tenantTransfer = $this->getFacade()->findTenantById($idTenant);

        if (!$tenantTransfer) {
            $this->addErrorMessage('Tenant not found.');

            return $this->viewResponse([
                'error' => 'Tenant not found'
            ]);
        }

        // Decode JSON data for display
        $data = [];
        if ($tenantTransfer->getData()) {
            $data = json_decode($tenantTransfer->getData(), true) ?: [];
        }

        return $this->viewResponse([
            'tenant' => $tenantTransfer,
            'tenantData' => $data,
        ]);
    }
}
