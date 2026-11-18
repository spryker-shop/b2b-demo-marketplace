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
 * `POST /company-users` over a booted GLUE_BACKEND kernel: both create paths and every rejection
 * the resource distinguishes.
 *
 * The two paths differ in what identifies the customer — an existing `customerReference`, or a
 * `customer` object describing one to register — and the rejections come from different layers: the
 * framework's constraint validation, the reference resolver, and the save-pre-check plugin stack. *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyUser
 * @group CreateCompanyUserBackendApiTest
 * Add your own group annotations below this line
 */
class CreateCompanyUserBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenAnExistingCustomerReferenceWhenCreateCompanyUserThenTheCompanyUserIsCreated(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $customerTransfer = $this->tester->haveCompanyUserCustomer('Attached', uniqid('CxmAttached'));
        $this->tester->actingAsUser();

        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
            [static::ATTRIBUTE_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail()],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
                static::ATTRIBUTE_COMPANY_UUID => $companyContext['company']->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $companyContext['businessUnit']->getUuidOrFail(),
                CompanyUserTransfer::IS_ACTIVE => true,
                CompanyUserTransfer::IS_DEFAULT => false,
            ],
            $this->getResourceAttributes($response),
            'A new company user is always created active and never default.',
        );
    }

    public function testGivenACustomerObjectWhenCreateCompanyUserThenTheCustomerIsRegisteredAndAttached(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $this->tester->actingAsUser();

        $customerAttributes = $this->tester->buildNewCustomerAttributes();
        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
            [static::ATTRIBUTE_CUSTOMER => $customerAttributes],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertNotEmpty(
            $this->getResourceAttributes($response)[static::ATTRIBUTE_CUSTOMER_REFERENCE] ?? null,
            'Registering the customer assigns the reference the company user is attached by.',
        );
    }

    public function testGivenNoCustomerAtAllWhenCreateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $this->tester->actingAsUser();

        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_CUSTOMER);
    }

    public function testGivenACustomerObjectWithoutANameWhenCreateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $this->tester->actingAsUser();

        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
            [
            static::ATTRIBUTE_CUSTOMER => [
                CustomerTransfer::EMAIL => uniqid('cxm.incomplete.', true) . '@spryker.local',
            ]],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, CustomerTransfer::FIRST_NAME);
        $this->assertValidationFailedForAttribute($response, CustomerTransfer::LAST_NAME);
        $this->assertValidationFailedForAttribute($response, CustomerTransfer::SALUTATION);
    }

    public function testGivenNoRolesWhenCreateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $customerTransfer = $this->tester->haveCompanyUserCustomer('NoRole', uniqid('CxmNoRole'));
        $this->tester->actingAsUser();

        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
            [
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
                static::ATTRIBUTE_COMPANY_ROLE_UUIDS => [],
            ],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_COMPANY_ROLE_UUIDS);
    }

    public function testGivenAnUnknownCompanyUuidWhenCreateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $customerTransfer = $this->tester->haveCompanyUserCustomer('NoCompany', uniqid('CxmNoCompany'));
        $this->tester->actingAsUser();

        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
            [
                static::ATTRIBUTE_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
                static::ATTRIBUTE_COMPANY_UUID => static::UNKNOWN_WELL_FORMED_UUID,
            ],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_NOT_FOUND,
        );
    }

    public function testGivenABusinessUnitOfAnotherCompanyWhenCreateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $foreignCompanyContext = $this->tester->haveCompanyContext();
        $customerTransfer = $this->tester->haveCompanyUserCustomer('Foreign', uniqid('CxmForeign'));
        $this->tester->actingAsUser();

        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $foreignCompanyContext['businessUnit'],
            $companyContext['role'],
            [static::ATTRIBUTE_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail()],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenARoleOfAnotherCompanyWhenCreateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $foreignCompanyContext = $this->tester->haveCompanyContext();
        $customerTransfer = $this->tester->haveCompanyUserCustomer('ForeignRole', uniqid('CxmForeignRole'));
        $this->tester->actingAsUser();

        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $foreignCompanyContext['role'],
            [static::ATTRIBUTE_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail()],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertContains(
            static::ERROR_MESSAGE_KEY_ROLE_NOT_IN_COMPANY,
            $this->getErrorDetails($response),
            'The rejection must name the offending role, not some other validation failure.',
        );
    }

    public function testGivenANewCustomerMissingRequiredFieldsWhenCreateCompanyUserThenNoErrorDetailCarriesAGlossaryKey(): void
    {
        // Arrange
        $companyContext = $this->tester->haveCompanyContext();
        $this->tester->actingAsUser();

        $attributes = $this->tester->buildValidCompanyUserAttributes(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
            [static::ATTRIBUTE_CUSTOMER => [CustomerTransfer::EMAIL => 'postman-incomplete@example.com']],
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
            [static::HEADER_ACCEPT_LANGUAGE => static::LANGUAGE_ENGLISH],
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);

        $errorDetails = $this->getErrorDetails($response);
        $this->assertNotEmpty($errorDetails, 'The incomplete customer must be reported field by field.');

        foreach ($errorDetails as $errorDetail) {
            $this->assertStringNotContainsString(
                static::GLOSSARY_KEY_PREFIX,
                $errorDetail,
                sprintf('A validation message reached the client as a glossary key: "%s".', $errorDetail),
            );
        }
    }

    public function testGivenACustomerAlreadyInThatBusinessUnitWhenCreateCompanyUserThenItIsRejected(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();

        $attributes = [
            static::ATTRIBUTE_CUSTOMER_REFERENCE => $companyUserTransfer
                ->getCustomerOrFail()
                ->getCustomerReferenceOrFail(),
            static::ATTRIBUTE_COMPANY_UUID => $companyUserTransfer->getCompanyOrFail()->getUuidOrFail(),
            static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $companyUserTransfer
                ->getCompanyBusinessUnitOrFail()
                ->getUuidOrFail(),
            static::ATTRIBUTE_COMPANY_ROLE_UUIDS => $this->getCompanyRoleUuids($companyUserTransfer),
        ];

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenNoAuthenticationWhenCreateCompanyUserThenItRespondsUnauthorized(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyUserCollectionUrl(),
            $this->tester->buildCompanyUserRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
