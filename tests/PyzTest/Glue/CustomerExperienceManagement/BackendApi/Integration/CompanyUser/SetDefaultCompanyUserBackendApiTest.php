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
 * `POST /company-users/{uuid}/set-default` over a booted GLUE_BACKEND kernel.
 *
 * Takes an `isDefault` boolean, the same body shape `/set-status` takes. A customer has at most one
 * default company user, so setting one unsets whichever was the default before; unsetting leaves
 * the customer with no default at all. *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyUser
 * @group SetDefaultCompanyUserBackendApiTest
 * Add your own group annotations below this line
 */
class SetDefaultCompanyUserBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenACompanyUserWhenSetDefaultThenItBecomesTheDefault(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetDefaultUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue($this->getResourceAttributes($response)[CompanyUserTransfer::IS_DEFAULT]);
    }

    public function testGivenTheDefaultCompanyUserWhenUnsetThenTheCustomerHasNoDefaultLeft(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();
        $uuid = $companyUserTransfer->getUuidOrFail();

        $this->assertRespondsWithStatus(
            $this->handleApiRequest(
                'POST',
                $this->tester->getCompanyUserSetDefaultUrl($uuid),
                $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => true]),
            ),
            Response::HTTP_OK,
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetDefaultUrl($uuid),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertFalse($this->getResourceAttributes($response)[CompanyUserTransfer::IS_DEFAULT]);
    }

    public function testGivenACompanyUserThatIsNotTheDefaultWhenUnsetThenItSucceedsWithoutClearingAnotherDefault(): void
    {
        // Arrange
        [$firstCompanyUserTransfer, $secondCompanyUserTransfer] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();
        $firstUuid = $firstCompanyUserTransfer->getUuidOrFail();

        $this->assertRespondsWithStatus(
            $this->handleApiRequest(
                'POST',
                $this->tester->getCompanyUserSetDefaultUrl($firstUuid),
                $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => true]),
            ),
            Response::HTTP_OK,
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetDefaultUrl($secondCompanyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertFalse($this->getResourceAttributes($response)[CompanyUserTransfer::IS_DEFAULT]);

        $firstResponse = $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl($firstUuid));
        $this->assertTrue(
            $this->getResourceAttributes($firstResponse)[CompanyUserTransfer::IS_DEFAULT],
            'Unsetting a company user that is not the default must leave the real default alone.',
        );
    }

    public function testGivenNoIsDefaultWhenSetDefaultThenItRespondsBadRequest(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetDefaultUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_USER_DEFAULT_INVALID,
        );
    }

    public function testGivenANonBooleanIsDefaultWhenSetDefaultThenItRespondsBadRequest(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetDefaultUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => 'true']),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_USER_DEFAULT_INVALID,
        );
    }

    public function testGivenACustomerWithTwoCompanyUsersWhenSetDefaultThenThePreviousDefaultIsUnset(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $secondBusinessUnitTransfer = $this->tester->haveBusinessUnitFor(
            $companyContext['company']->getIdCompanyOrFail(),
        );
        $firstCompanyUserTransfer = $this->tester->haveCompanyUserFor(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
        );
        $customerReference = $firstCompanyUserTransfer->getCustomerOrFail()->getCustomerReferenceOrFail();
        $this->tester->actingAsUser();

        $secondUuid = (string)($this->decodeJsonApi($this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $customerReference,
                static::ATTRIBUTE_COMPANY_UUID => $companyContext['company']->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $secondBusinessUnitTransfer->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => [$companyContext['role']->getUuidOrFail()],
            ]),
        ))[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');

        $firstUuid = $firstCompanyUserTransfer->getUuidOrFail();

        $this->assertRespondsWithStatus(
            $this->handleApiRequest(
                'POST',
                $this->tester->getCompanyUserSetDefaultUrl($firstUuid),
                $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => true]),
            ),
            Response::HTTP_OK,
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetDefaultUrl($secondUuid),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue($this->getResourceAttributes($response)[CompanyUserTransfer::IS_DEFAULT]);

        $firstResponse = $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl($firstUuid));
        $this->assertFalse(
            $this->getResourceAttributes($firstResponse)[CompanyUserTransfer::IS_DEFAULT],
            'A customer has at most one default, so the previous one is unset in the same request.',
        );
    }

    public function testGivenAnUnknownUuidWhenSetDefaultThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetDefaultUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenSetDefaultThenItRespondsUnauthorized(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserSetDefaultUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyUserRequestBody([CompanyUserTransfer::IS_DEFAULT => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
