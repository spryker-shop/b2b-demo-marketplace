<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyRole;

use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyRole
 * @group DeleteCompanyRoleBackendApiTest
 * Add your own group annotations below this line
 */
class DeleteCompanyRoleBackendApiTest extends AbstractCompanyRoleBackendApiTestCase
{
    public function testDeletesCompanyRole(): void
    {
        // Arrange
        $companyRoleTransfer = $this->haveCompanyRoleFor($this->haveCompany());

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCompanyRoleUrl($companyRoleTransfer->getUuidOrFail()));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertFalse($this->tester->hasCompanyRole($companyRoleTransfer->getUuidOrFail()));
    }

    public function testRejectsDeletingTheDefaultCompanyRole(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $defaultCompanyRoleUuid = $this->tester->getDefaultCompanyRoleUuid($companyTransfer->getIdCompanyOrFail());

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCompanyRoleUrl($defaultCompanyRoleUuid));

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_COMPANY_ROLE_IS_DEFAULT);
        $this->assertTrue($this->tester->hasCompanyRole($defaultCompanyRoleUuid));
    }

    public function testRejectsDeletingCompanyRoleWithAssignedCompanyUsers(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $this->tester->haveCompanyUserFor(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
        );
        $companyRoleUuid = $companyContext['role']->getUuidOrFail();

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCompanyRoleUrl($companyRoleUuid));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_COMPANY_ROLE_HAS_COMPANY_USERS,
        );
        $this->assertTrue($this->tester->hasCompanyRole($companyRoleUuid));
    }

    public function testReturnsNotFoundForUnknownUuid(): void
    {
        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCompanyRoleUrl(static::UNKNOWN_WELL_FORMED_UUID));

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_NOT_FOUND, static::RESPONSE_CODE_COMPANY_ROLE_NOT_FOUND);
    }
}
