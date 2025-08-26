<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Communication\Controller;

use Generated\Shared\Transfer\TenantAssignmentTransfer;
use Generated\Shared\Transfer\TenantDuplicationTransfer;
use Generated\Shared\Transfer\TenantTableRowsRequestTransfer;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pyz\Zed\TenantAssigner\Business\TenantAssignerFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantAssigner\Communication\TenantAssignerCommunicationFactory getFactory()
 */
class IndexController extends AbstractController
{
    /**
     * @return array<string, mixed>
     */
    public function indexAction(): array
    {
        $tables = $this->getFacade()->getTablesWithTenantColumn();

        return $this->viewResponse([
            'tables' => $tables,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array<string, mixed>
     */
    public function tableAction(Request $request): array
    {
        $tableName = $request->query->get('name');
        if (!$tableName) {
            $this->addErrorMessage('Table name is required');

            return $this->viewResponse(['error' => 'Table name is required']);
        }

        $tenantTableRowsRequest = new TenantTableRowsRequestTransfer();
        $tenantTableRowsRequest->setTableName($tableName);
        $tenantTableRowsRequest->setPage((int)$request->query->get('page', 1));
        $tenantTableRowsRequest->setPageSize((int)$request->query->get('pageSize', 20));
        $tenantTableRowsRequest->setTenantFilter($request->query->get('tenantFilter'));
        $tenantTableRowsRequest->setShowUnassignedOnly((bool)$request->query->get('showUnassignedOnly', false));

        $response = $this->getFacade()->getTableRowsWithPagination($tenantTableRowsRequest);
        $tenantTransfers = (new \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacade())->getTenants(
            (new \Generated\Shared\Transfer\TenantCriteriaTransfer())
        );

        $availableTenants = [];
        foreach ($tenantTransfers->getTenants() as $tenantTransfer) {
            $data = json_decode($tenantTransfer->getData(), true);
            $availableTenants[$tenantTransfer->getIdentifier()] = $data['companyName'];
        }

        return $this->viewResponse([
            'tableResponse' => $response,
            'availableTenants' => $availableTenants,
            'currentFilters' => [
                'tenantFilter' => $request->query->get('tenantFilter'),
                'showUnassignedOnly' => (bool)$request->query->get('showUnassignedOnly', false),
                'pageSize' => (int)$request->query->get('pageSize', 20),
            ],
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function assignTenantAction(Request $request): JsonResponse
    {
        $tableName = $request->request->get('tableName');
        $rowId = $request->request->get('rowId');
        $tenantId = $request->request->get('tenantId');
        $idColumnName = $request->request->get('idColumnName');

        if (!$tableName || !$rowId || !$tenantId || !$idColumnName) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Missing required parameters',
            ]);
        }

        $tenantAssignment = new TenantAssignmentTransfer();
        $tenantAssignment->setTableName($tableName);
        $tenantAssignment->setRowId($rowId);
        $tenantAssignment->setTenantId($tenantId);
        $tenantAssignment->setIdColumnName($idColumnName);

        $response = $this->getFacade()->assignTenantToRow($tenantAssignment);

        if ($response->getIsSuccess()) {
            $this->addSuccessMessage($response->getMessage());
        } else {
            $this->addErrorMessage($response->getMessage());
            foreach ($response->getErrorMessages() as $errorMessage) {
                $this->addErrorMessage($errorMessage);
            }
        }

        return $this->jsonResponse([
            'success' => $response->getIsSuccess(),
            'message' => $response->getMessage(),
            'errors' => $response->getErrorMessages(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function bulkAssignTenantAction(Request $request): JsonResponse
    {
        $tableName = $request->request->get('tableName');
        $rowIds = $request->request->all('rowIds');
        if (!is_array($rowIds)) {
            $rowIds = [];
        }
        $tenantId = $request->request->get('tenantId');
        $idColumnName = $request->request->get('idColumnName');

        if (!$tableName || empty($rowIds) || !$tenantId || !$idColumnName) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Missing required parameters',
            ]);
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($rowIds as $rowId) {
            $tenantAssignment = new TenantAssignmentTransfer();
            $tenantAssignment->setTableName($tableName);
            $tenantAssignment->setRowId($rowId);
            $tenantAssignment->setTenantId($tenantId);
            $tenantAssignment->setIdColumnName($idColumnName);

            $response = $this->getFacade()->assignTenantToRow($tenantAssignment);

            if ($response->getIsSuccess()) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Row {$rowId}: " . $response->getMessage();
            }
        }

        $message = "Successfully assigned {$successCount} rows.";
        if ($errorCount > 0) {
            $message .= " {$errorCount} failed.";
        }

        if ($successCount > 0) {
            $this->addSuccessMessage($message);
        }
        if ($errorCount > 0) {
            $this->addErrorMessage($message);
        }

        return $this->jsonResponse([
            'success' => $errorCount === 0,
            'message' => $message,
            'successCount' => $successCount,
            'errorCount' => $errorCount,
            'errors' => $errors,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function duplicateRowForTenantAction(Request $request): JsonResponse
    {
        $tableName = $request->request->get('tableName');
        $sourceRowId = $request->request->get('sourceRowId');
        $targetTenantId = $request->request->get('targetTenantId');
        $idColumnName = $request->request->get('idColumnName');

        if (!$tableName || !$sourceRowId || !$targetTenantId || !$idColumnName) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Missing required parameters',
            ]);
        }

        $tenantDuplication = new TenantDuplicationTransfer();
        $tenantDuplication->setTableName($tableName);
        $tenantDuplication->setSourceRowId($sourceRowId);
        $tenantDuplication->setTargetTenantId($targetTenantId);
        $tenantDuplication->setIdColumnName($idColumnName);

        $response = $this->getFacade()->duplicateRowForTenant($tenantDuplication);

        if ($response->getIsSuccess()) {
            $this->addSuccessMessage($response->getMessage());
        } else {
            $this->addErrorMessage($response->getMessage());
        }

        return $this->jsonResponse([
            'success' => $response->getIsSuccess(),
            'message' => $response->getMessage(),
            'newRowId' => $response->getNewRowId(),
            'errors' => $response->getErrorMessages(),
        ]);
    }
}
