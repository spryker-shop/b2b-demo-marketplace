<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyUser;

use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * `DELETE /company-users/{uuid}` over a booted GLUE_BACKEND kernel.
 *
 * The company user is removed and the customer is kept — the difference from the customers resource,
 * whose DELETE anonymizes. The retained customer can be given a new company user afterwards, which
 * is the clearest executable statement of that contract. *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyUser
 * @group DeleteCompanyUserBackendApiTest
 * Add your own group annotations below this line
 */
class DeleteCompanyUserBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenACompanyUserWhenDeleteCompanyUserThenItIsRemoved(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();
        $uuid = $companyUserTransfer->getUuidOrFail();

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCompanyUserUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertRespondsWithStatus(
            $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl($uuid)),
            Response::HTTP_NOT_FOUND,
        );
    }

    public function testGivenACompanyUserWhenDeleteCompanyUserThenTheCustomerIsKeptAndReusable(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();
        $customerReference = $companyUserTransfer->getCustomerOrFail()->getCustomerReferenceOrFail();

        $this->assertRespondsWithStatus(
            $this->handleApiRequest('DELETE', $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail())),
            Response::HTTP_NO_CONTENT,
        );

        // Act
        $customerResponse = $this->handleApiRequest('GET', $this->tester->getCustomerUrl($customerReference));
        $recreateResponse = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $customerReference,
                static::ATTRIBUTE_COMPANY_UUID => $companyUserTransfer->getCompanyOrFail()->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $companyUserTransfer
                    ->getCompanyBusinessUnitOrFail()
                    ->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => $this->getCompanyRoleUuids($companyUserTransfer),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($customerResponse, Response::HTTP_OK);
        $this->assertRespondsWithStatus($recreateResponse, Response::HTTP_CREATED);
        $this->assertSame(
            $customerReference,
            $this->getResourceAttributes($recreateResponse)[static::ATTRIBUTE_CUSTOMER_REFERENCE],
        );
    }

    public function testGivenAnUnknownUuidWhenDeleteCompanyUserThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCompanyUserUrl(static::UNKNOWN_UUID));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenDeleteCompanyUserThenItRespondsUnauthorized(): void
    {
        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCompanyUserUrl(static::UNKNOWN_UUID));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
