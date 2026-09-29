<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\CompanyTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CreateCompanyBackendApiTest
 * Add your own group annotations below this line
 * @group Companies
 */
class CreateCompanyBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const int NAME_MAX_LENGTH = 100;

    protected const string UNSUPPORTED_STATUS = 'on_hold';

    protected const string CLIENT_SUPPLIED_UUID = '99999999-8888-7777-6666-555555555555';

    protected const string ATTRIBUTE_UUID = 'uuid';

    public function testGivenOnlyANameWhenCreateCompanyThenItIsCreatedPendingAndInactive(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $this->assertAttributesMatch(
            [
                CompanyTransfer::NAME => $attributes[CompanyTransfer::NAME],
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_PENDING,
                static::ATTRIBUTE_IS_ACTIVE => false,
            ],
            $this->getResourceAttributes($response),
            'A company with no state supplied takes the database defaults.',
        );
        $this->assertNotEmpty(
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? null,
            'The uuid behaviour assigns the identifier the response is addressed by.',
        );
    }

    public function testGivenAnExplicitStateWhenCreateCompanyThenThatStateIsPersisted(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes([
            static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED,
            static::ATTRIBUTE_IS_ACTIVE => true,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED,
                static::ATTRIBUTE_IS_ACTIVE => true,
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnExplicitStateWhenCreateCompanyThenItIsReadBackWithThatState(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi([
            static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_DENIED,
            static::ATTRIBUTE_IS_ACTIVE => true,
        ]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_DENIED,
                static::ATTRIBUTE_IS_ACTIVE => true,
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenNoNameWhenCreateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
        $this->assertValidationFailedForAttribute($response, CompanyTransfer::NAME);
    }

    public function testGivenABlankNameWhenCreateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody([CompanyTransfer::NAME => '']),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
        $this->assertValidationFailedForAttribute($response, CompanyTransfer::NAME);
    }

    public function testGivenANameOverTheLimitWhenCreateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes([
            CompanyTransfer::NAME => str_repeat('x', static::NAME_MAX_LENGTH + 1),
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
        $this->assertValidationFailedForAttribute($response, CompanyTransfer::NAME);
    }

    public function testGivenANameAtTheLimitWhenCreateCompanyThenItIsCreated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes([
            CompanyTransfer::NAME => str_repeat('y', static::NAME_MAX_LENGTH),
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
    }

    public function testGivenAStatusOutsideTheAcceptedSetWhenCreateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes([
            static::ATTRIBUTE_STATUS => static::UNSUPPORTED_STATUS,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_STATUS);
    }

    public function testGivenAClientSuppliedUuidWhenCreateCompanyThenItIsIgnored(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes([
            static::ATTRIBUTE_UUID => static::CLIENT_SUPPLIED_UUID,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $assignedUuid = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $this->assertNotSame(
            static::CLIENT_SUPPLIED_UUID,
            $assignedUuid,
            'The database assigns the uuid; a client-supplied one is discarded.',
        );
        $this->assertNotSame('', $assignedUuid);
    }

    public function testGivenAMalformedJsonBodyWhenCreateCompanyThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            static::MALFORMED_JSON_BODY,
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_BAD_REQUEST);
    }

    public function testGivenNoAuthenticationWhenCreateCompanyThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody($this->tester->buildValidCompanyAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
