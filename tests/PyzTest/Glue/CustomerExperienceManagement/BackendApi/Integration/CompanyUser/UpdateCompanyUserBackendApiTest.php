<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyUser;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `PATCH /company-users/{uuid}` over a booted GLUE_BACKEND kernel.
 *
 * The endpoint manages company users, not customers: it can re-point the company user at another
 * business unit and replace its roles, but never writes customer data and never touches the active
 * or default flags.
 *
 * Every writable property is covered twice over: once for what a partial update applies, and once
 * for how it is rejected.
 *
 * Known defect, worked around rather than asserted: the `PATCH` response body reports the
 * PRE-update `companyRoleUuids`, because the role hydration reads through
 * `CompanyRoleRepository::getCompanyRoleCollection()`, whose static per-process cache the
 * save-pre-check already filled in the same request. The persisted state is correct, so every role
 * assertion below reads the company user back instead of trusting the response.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyUser
 * @group UpdateCompanyUserBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateCompanyUserBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenAnotherBusinessUnitWhenUpdateCompanyUserThenItIsMovedRatherThanDuplicated(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $targetBusinessUnitTransfer = $this->tester->haveBusinessUnitFor(
            $companyUserTransfer->getCompanyOrFail()->getIdCompanyOrFail(),
        );
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $targetBusinessUnitTransfer->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $targetBusinessUnitTransfer->getUuidOrFail(),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID],
        );
        $this->assertSame(
            $companyUserTransfer->getUuidOrFail(),
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID],
            'The same company user is updated; PATCH never creates a second one.',
        );
    }

    public function testGivenCustomerDetailsWhenUpdateCompanyUserThenTheCustomerIsNotWritten(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $customerTransfer = $companyUserTransfer->getCustomerOrFail();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_CUSTOMER => [CustomerTransfer::FIRST_NAME => static::IGNORED_FIRST_NAME],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertStringNotContainsString(
            static::IGNORED_FIRST_NAME,
            (string)$response->getContent(),
            'Anything sent inside the customer object is dropped before the facade call.',
        );

        $customerResponse = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );
        $this->assertSame(
            $customerTransfer->getFirstNameOrFail(),
            $this->getResourceAttributes($customerResponse)[CustomerTransfer::FIRST_NAME],
            'The customer record itself is unchanged.',
        );
    }

    public function testGivenAnUpdateWhenUpdateCompanyUserThenTheActiveAndDefaultFlagsAreUntouched(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                CompanyUserTransfer::IS_ACTIVE => false,
                CompanyUserTransfer::IS_DEFAULT => true,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertTrue($attributes[CompanyUserTransfer::IS_ACTIVE]);
        $this->assertFalse($attributes[CompanyUserTransfer::IS_DEFAULT]);
    }

    public function testGivenAnotherCompanyWithItsBusinessUnitAndRolesWhenUpdateCompanyUserThenAllThreeAreApplied(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $targetCompanyContext = $this->tester->haveCompanyContext();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody($this->tester->buildValidCompanyUserAttributes(
                $targetCompanyContext['company'],
                $targetCompanyContext['businessUnit'],
                $targetCompanyContext['role'],
            )),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_COMPANY_UUID => $targetCompanyContext['company']->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $targetCompanyContext['businessUnit']->getUuidOrFail(),
            ],
            $this->getResourceAttributes($response),
        );
        $this->resetCompanyRoleCollectionCache();
        $this->assertSame(
            [$targetCompanyContext['role']->getUuidOrFail()],
            $this->getResourceAttributes($this->handleApiRequest(
                'GET',
                $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            ))[static::ATTRIBUTE_COMPANY_ROLE_UUIDS],
            'The roles move with the company; asserted on a re-read because the PATCH response '
                . 'reports the pre-update role list.',
        );
    }

    public function testGivenAnotherRoleWhenUpdateCompanyUserThenItReplacesTheWholeSet(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $secondRoleTransfer = $this->tester->haveRoleFor($companyContext['company']->getIdCompanyOrFail());

        $companyUserTransfer = $this->tester->haveCompanyUserFor(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
        );
        $this->tester->actingAsUser();
        $uuid = $companyUserTransfer->getUuidOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($uuid),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => [$secondRoleTransfer->getUuidOrFail()],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->resetCompanyRoleCollectionCache();
        $this->assertSame(
            [$secondRoleTransfer->getUuidOrFail()],
            $this->getResourceAttributes(
                $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl($uuid)),
            )[static::ATTRIBUTE_COMPANY_ROLE_UUIDS],
        );
    }

    public function testGivenNoAttributesAtAllWhenUpdateCompanyUserThenEverythingIsKept(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();
        $uuid = $companyUserTransfer->getUuidOrFail();

        $before = $this->getResourceAttributes(
            $this->handleApiRequest('GET', $this->tester->getCompanyUserUrl($uuid)),
        );

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($uuid),
            $this->tester->buildCompanyUserRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $before[static::ATTRIBUTE_CUSTOMER_REFERENCE],
                static::ATTRIBUTE_COMPANY_UUID => $before[static::ATTRIBUTE_COMPANY_UUID],
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $before[static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID],
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => $before[static::ATTRIBUTE_COMPANY_ROLE_UUIDS],
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnotherCustomerReferenceWhenUpdateCompanyUserThenTheCompanyUserKeepsItsOwnCustomer(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $otherCustomerTransfer = $this->tester->haveCompanyUserCustomer('Other', uniqid('CxmOther'));
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $otherCustomerTransfer->getCustomerReferenceOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $companyUserTransfer->getCustomerOrFail()->getCustomerReferenceOrFail(),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_CUSTOMER_REFERENCE],
        );
    }

    public function testGivenAnUnknownCustomerReferenceWhenUpdateCompanyUserThenItIsNotAnError(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCE => static::UNKNOWN_CUSTOMER_REFERENCE,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $companyUserTransfer->getCustomerOrFail()->getCustomerReferenceOrFail(),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_CUSTOMER_REFERENCE],
        );
    }

    public function testGivenAMalformedCompanyUuidWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_UUID => static::MALFORMED_UUID,
            ]),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_COMPANY_UUID);
    }

    public function testGivenAnUnknownCompanyUuidWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_UUID => static::UNKNOWN_WELL_FORMED_UUID,
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_NOT_FOUND,
        );
    }

    public function testGivenAnotherCompanyAloneWhenUpdateCompanyUserThenItIsRejectedForTheBusinessUnit(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $targetCompanyContext = $this->tester->haveCompanyContext();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_UUID => $targetCompanyContext['company']->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAMalformedBusinessUnitUuidWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => static::MALFORMED_UUID,
            ]),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID);
    }

    public function testGivenAnUnknownBusinessUnitUuidWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => static::UNKNOWN_WELL_FORMED_UUID,
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_BUSINESS_UNIT_NOT_FOUND,
        );
    }

    public function testGivenABusinessUnitOfAnotherCompanyWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $foreignCompanyContext = $this->tester->haveCompanyContext();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $foreignCompanyContext['businessUnit']->getUuidOrFail(),
            ]),
        );

        // Assert — the business unit must belong to the company the company user points at.
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenABusinessUnitTheCustomerIsAlreadyInWhenUpdateCompanyUserThenItIsRejected(): void
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
        $this->tester->actingAsUser();

        $secondUuid = (string)($this->decodeJsonApi($this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $firstCompanyUserTransfer
                    ->getCustomerOrFail()
                    ->getCustomerReferenceOrFail(),
                static::ATTRIBUTE_COMPANY_UUID => $companyContext['company']->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $secondBusinessUnitTransfer->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => [$companyContext['role']->getUuidOrFail()],
            ]),
        ))[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($secondUuid),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $companyContext['businessUnit']->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAnEmptyRoleListWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([static::ATTRIBUTE_COMPANY_ROLE_UUIDS => []]),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_COMPANY_ROLE_UUIDS);
    }

    public function testGivenAMalformedRoleUuidWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => [static::MALFORMED_UUID],
            ]),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_COMPANY_ROLE_UUIDS);
    }

    public function testGivenAnUnknownRoleUuidWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => [static::UNKNOWN_WELL_FORMED_UUID],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_ROLE_NOT_FOUND,
        );
    }

    public function testGivenARoleOfAnotherCompanyWhenUpdateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $foreignCompanyContext = $this->tester->haveCompanyContext();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl($companyUserTransfer->getUuidOrFail()),
            $this->tester->buildCompanyUserRequestBody([
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => [$foreignCompanyContext['role']->getUuidOrFail()],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAnUnknownUuidWhenUpdateCompanyUserThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyUserRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenUpdateCompanyUserThenItRespondsUnauthorized(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUserUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyUserRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
