<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\CompanyTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group UpdateCompanyBackendApiTest
 * Add your own group annotations below this line
 * @group Companies
 */
class UpdateCompanyBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string UNSUPPORTED_STATUS = 'on_hold';

    protected const string RENAMED = 'CxmCompanyRenamed';

    protected const int NAME_MAX_LENGTH = 100;

    public function testGivenAStatusOnlyPatchWhenUpdateCompanyThenOnlyTheStatusChanges(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes();
        $uuid = $this->haveCompanyViaApi($attributes);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                CompanyTransfer::NAME => $attributes[CompanyTransfer::NAME],
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED,
                static::ATTRIBUTE_IS_ACTIVE => false,
            ],
            $this->getResourceAttributes($response),
            'Approving a company leaves its name and active state alone.',
        );
    }

    public function testGivenAnIsActiveOnlyPatchWhenUpdateCompanyThenOnlyTheActiveStateChanges(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes([
            static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED,
        ]);
        $uuid = $this->haveCompanyViaApi($attributes);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([static::ATTRIBUTE_IS_ACTIVE => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                CompanyTransfer::NAME => $attributes[CompanyTransfer::NAME],
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED,
                static::ATTRIBUTE_IS_ACTIVE => true,
            ],
            $this->getResourceAttributes($response),
            'Activating a company leaves its name and status alone.',
        );
    }

    public function testGivenIsActiveFalseWhenUpdateCompanyThenItIsDeactivated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi([static::ATTRIBUTE_IS_ACTIVE => true]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([static::ATTRIBUTE_IS_ACTIVE => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [static::ATTRIBUTE_IS_ACTIVE => false],
            $this->getResourceAttributes($response),
            'Deactivation must apply even though false is falsy.',
        );
    }

    public function testGivenANameOnlyPatchWhenUpdateCompanyThenOnlyTheNameChanges(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi([
            static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_DENIED,
            static::ATTRIBUTE_IS_ACTIVE => true,
        ]);
        $renamed = uniqid(static::RENAMED, false);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([CompanyTransfer::NAME => $renamed]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                CompanyTransfer::NAME => $renamed,
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_DENIED,
                static::ATTRIBUTE_IS_ACTIVE => true,
            ],
            $this->getResourceAttributes($response),
            'Renaming a company leaves its state alone.',
        );
    }

    public function testGivenAnUpdatedCompanyWhenReadBackThenTheChangeWasPersisted(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi();
        $renamed = uniqid(static::RENAMED, false);

        $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([
                CompanyTransfer::NAME => $renamed,
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED,
            ]),
        );

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                CompanyTransfer::NAME => $renamed,
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED,
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnEmptyPatchWhenUpdateCompanyThenNothingChanges(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes();
        $uuid = $this->haveCompanyViaApi($attributes);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                CompanyTransfer::NAME => $attributes[CompanyTransfer::NAME],
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_PENDING,
                static::ATTRIBUTE_IS_ACTIVE => false,
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenPendingStatusWhenUpdateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi([static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_PENDING]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_STATUS);
    }

    public function testGivenAStatusOutsideTheAcceptedSetWhenUpdateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([static::ATTRIBUTE_STATUS => static::UNSUPPORTED_STATUS]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_STATUS);
    }

    public function testGivenABlankNameWhenUpdateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
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

    public function testGivenANameOverTheLimitWhenUpdateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([
                CompanyTransfer::NAME => str_repeat('x', static::NAME_MAX_LENGTH + 1),
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
        $this->assertValidationFailedForAttribute($response, CompanyTransfer::NAME);
    }

    public function testGivenANameAtTheLimitWhenUpdateCompanyThenItIsApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi();
        $renamed = str_repeat('y', static::NAME_MAX_LENGTH);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([CompanyTransfer::NAME => $renamed]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [CompanyTransfer::NAME => $renamed],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenABlankStatusWhenUpdateCompanyThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            $this->tester->buildCompanyRequestBody([static::ATTRIBUTE_STATUS => '']),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_STATUS);
    }

    public function testGivenAMalformedJsonBodyWhenUpdateCompanyThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl($uuid),
            static::MALFORMED_JSON_BODY,
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_BAD_REQUEST);
    }

    public function testGivenAnUnknownUuidWhenUpdateCompanyThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyRequestBody([CompanyTransfer::NAME => static::RENAMED]),
        );

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_NOT_FOUND,
            sprintf(
                CustomerExperienceManagementConfig::RESPONSE_DETAILS_COMPANY_NOT_FOUND,
                static::UNKNOWN_UUID,
            ),
        );
    }

    public function testGivenNoAuthenticationWhenUpdateCompanyThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyRequestBody([CompanyTransfer::NAME => static::RENAMED]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
