<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyUser;

use Generated\Shared\Transfer\CompanyUserTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /company-users/{uuid}/set-status` over a booted GLUE_BACKEND kernel.
 *
 * The operation is declared `deserialize: false` so `isActive` can stay read-only on the resource,
 * which is why the processor reads the flag from the raw body and rejects a non-boolean itself. *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyUser
 * @group SetCompanyUserStatusBackendApiTest
 * Add your own group annotations below this line
 */
class SetCompanyUserStatusBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenAnActiveCompanyUserWhenDeactivateThenItBecomesInactive(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetStatusUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_ACTIVE => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertFalse($this->getResourceAttributes($response)[CompanyUserTransfer::IS_ACTIVE]);
    }

    public function testGivenAnInactiveCompanyUserWhenActivateThenItBecomesActive(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();
        $uuid = $companyUserTransfer->getUuidOrFail();

        $this->assertRespondsWithStatus(
            $this->handleApiRequest(
                'POST',
                $this->tester->getCompanyUserSetStatusUrl($uuid),
                $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_ACTIVE => false]),
            ),
            Response::HTTP_OK,
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetStatusUrl($uuid),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_ACTIVE => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue($this->getResourceAttributes($response)[CompanyUserTransfer::IS_ACTIVE]);
    }

    public function testGivenTheStatusItAlreadyHasWhenSetStatusThenItSucceedsWithoutChangingAnything(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetStatusUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_ACTIVE => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue($this->getResourceAttributes($response)[CompanyUserTransfer::IS_ACTIVE]);
    }

    public function testGivenNoIsActiveWhenSetStatusThenItRespondsBadRequest(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetStatusUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_USER_STATUS_INVALID,
        );
    }

    public function testGivenANonBooleanIsActiveWhenSetStatusThenItRespondsBadRequest(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetStatusUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_ACTIVE => 'false']),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_USER_STATUS_INVALID,
        );
    }

    public function testGivenAnUnknownUuidWhenSetStatusThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetStatusUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_ACTIVE => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenSetStatusThenItRespondsUnauthorized(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetStatusUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_ACTIVE => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
