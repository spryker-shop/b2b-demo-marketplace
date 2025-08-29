<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Business\Service;

use Generated\Shared\Transfer\TenantDuplicationResponseTransfer;
use Generated\Shared\Transfer\TenantDuplicationTransfer;
use Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface;
use Pyz\Zed\TenantAssigner\TenantAssignerConfig;

class TenantDuplicationService implements TenantDuplicationServiceInterface
{
    /**
     * @param \Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface $repository
     * @param \Pyz\Zed\TenantAssigner\TenantAssignerConfig $config
     */
    public function __construct(
        protected TenantAssignerRepositoryInterface $repository,
        protected TenantAssignerConfig $config,
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\TenantDuplicationTransfer $tenantDuplicationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantDuplicationResponseTransfer
     */
    public function duplicateRowForTenant(TenantDuplicationTransfer $tenantDuplicationTransfer): TenantDuplicationResponseTransfer
    {
        $response = new TenantDuplicationResponseTransfer();

        // Validate input
        $validationErrors = $this->validateTenantDuplication($tenantDuplicationTransfer);
        if (!empty($validationErrors)) {
            $response->setIsSuccess(false);
            $response->setMessage('Validation failed');
            $response->setErrorMessages($validationErrors);

            return $response;
        }

        // Perform the duplication
        $result = $this->repository->duplicateRowForTenant(
            $tenantDuplicationTransfer->getTableName(),
            $tenantDuplicationTransfer->getSourceRowId(),
            $tenantDuplicationTransfer->getTargetTenantId(),
            $tenantDuplicationTransfer->getIdColumnName(),
            $this->config->getTenantColumnName(),
        );

        if ($result['success']) {
            $response->setIsSuccess(true);
            $response->setNewRowId($result['newRowId']);
            $response->setMessage('Row duplicated successfully for tenant');
        } else {
            $response->setIsSuccess(false);
            $response->setMessage('Failed to duplicate row');
            $response->setErrorMessages([$result['error'] ?? 'Unknown error']);
        }

        return $response;
    }

    /**
     * @param \Generated\Shared\Transfer\TenantDuplicationTransfer $tenantDuplicationTransfer
     *
     * @return array<string>
     */
    protected function validateTenantDuplication(TenantDuplicationTransfer $tenantDuplicationTransfer): array
    {
        $errors = [];

        if (!$tenantDuplicationTransfer->getTableName()) {
            $errors[] = 'Table name is required';
        }

        if (!$tenantDuplicationTransfer->getSourceRowId()) {
            $errors[] = 'Source row ID is required';
        }

        if (!$tenantDuplicationTransfer->getTargetTenantId()) {
            $errors[] = 'Target tenant ID is required';
        }

        if (!$tenantDuplicationTransfer->getIdColumnName()) {
            $errors[] = 'ID column name is required';
        }

        if (!(new \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacade())->findTenantByIdentifier($tenantDuplicationTransfer->getTargetTenantId())) {
            $errors[] = 'Invalid target tenant ID';
        }

        return $errors;
    }
}
