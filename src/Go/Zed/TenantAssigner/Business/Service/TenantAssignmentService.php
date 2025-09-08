<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantAssigner\Business\Service;

use Generated\Shared\Transfer\TenantAssignmentResponseTransfer;
use Generated\Shared\Transfer\TenantAssignmentTransfer;
use Go\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface;
use Go\Zed\TenantAssigner\TenantAssignerConfig;

class TenantAssignmentService implements TenantAssignmentServiceInterface
{
    /**
     * @param \Go\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface $repository
     * @param \Go\Zed\TenantAssigner\TenantAssignerConfig $config
     */
    public function __construct(
        protected TenantAssignerRepositoryInterface $repository,
        protected TenantAssignerConfig $config,
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\TenantAssignmentTransfer $tenantAssignmentTransfer
     *
     * @return \Generated\Shared\Transfer\TenantAssignmentResponseTransfer
     */
    public function assignTenantToRow(TenantAssignmentTransfer $tenantAssignmentTransfer): TenantAssignmentResponseTransfer
    {
        $response = new TenantAssignmentResponseTransfer();

        // Validate input
        $validationErrors = $this->validateTenantAssignment($tenantAssignmentTransfer);
        if (!empty($validationErrors)) {
            $response->setIsSuccess(false);
            $response->setMessage('Validation failed');
            $response->setErrorMessages($validationErrors);

            return $response;
        }

        // Perform the assignment
        $success = $this->repository->assignTenantToRow(
            $tenantAssignmentTransfer->getTableName(),
            $tenantAssignmentTransfer->getRowId(),
            $tenantAssignmentTransfer->getTenantId(),
            $tenantAssignmentTransfer->getIdColumnName(),
            $this->config->getTenantColumnName(),
        );

        if ($success) {
            $response->setIsSuccess(true);
            $response->setMessage('Tenant assigned successfully');
        } else {
            $response->setIsSuccess(false);
            $response->setMessage('Failed to assign tenant');
            $response->setErrorMessages(['Database update failed']);
        }

        return $response;
    }

    /**
     * @param \Generated\Shared\Transfer\TenantAssignmentTransfer $tenantAssignmentTransfer
     *
     * @return array<string>
     */
    protected function validateTenantAssignment(TenantAssignmentTransfer $tenantAssignmentTransfer): array
    {
        $errors = [];

        if (!$tenantAssignmentTransfer->getTableName()) {
            $errors[] = 'Table name is required';
        }

        if (!$tenantAssignmentTransfer->getRowId()) {
            $errors[] = 'Row ID is required';
        }

        if (!$tenantAssignmentTransfer->getTenantId()) {
            $errors[] = 'Tenant ID is required';
        }

        if (!$tenantAssignmentTransfer->getIdColumnName()) {
            $errors[] = 'ID column name is required';
        }

        if (!(new \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacade())->findTenantByIdentifier($tenantAssignmentTransfer->getTenantId())) {
            $errors[] = 'Invalid tenant ID';
        }

        return $errors;
    }
}
