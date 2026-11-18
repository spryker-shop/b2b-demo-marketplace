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
 * `GET /company-users/{uuid}` over a booted GLUE_BACKEND kernel.
 *
 * Inactive company users are returned too, so an operator can inspect one before re-activating it. *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyUser
 * @group GetSingleCompanyUserBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleCompanyUserBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenAnAuthenticatedOperatorWhenRetrieveCompanyUserThenItsAttributesAreReturned(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $companyUserTransfer
                    ->getCustomerOrFail()
                    ->getCustomerReferenceOrFail(),
                static::ATTRIBUTE_COMPANY_UUID => $companyUserTransfer->getCompanyOrFail()->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $companyUserTransfer
                    ->getCompanyBusinessUnitOrFail()
                    ->getUuidOrFail(),
                CompanyUserTransfer::IS_ACTIVE => true,
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenADeactivatedCompanyUserWhenRetrieveCompanyUserThenItIsStillReturned(): void
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
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertFalse($this->getResourceAttributes($response)[CompanyUserTransfer::IS_ACTIVE]);
    }

    public function testGivenAnUnknownUuidWhenRetrieveCompanyUserThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl(static::UNKNOWN_UUID));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenAnAnonymizedCustomerWhenRetrieveCompanyUserThenItIsNoLongerReadable(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();
        $this->anonymizeCustomerOfCompanyUser($companyUserTransfer);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_USER_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenRetrieveCompanyUserThenItRespondsUnauthorized(): void
    {
        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl(static::UNKNOWN_UUID));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
