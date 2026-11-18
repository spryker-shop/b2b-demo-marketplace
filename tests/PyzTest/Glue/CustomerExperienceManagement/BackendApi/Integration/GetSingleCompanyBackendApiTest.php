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
 * @group GetSingleCompanyBackendApiTest
 * Add your own group annotations below this line
 * @group Companies
 */
class GetSingleCompanyBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string MALFORMED_UUID = 'not-a-uuid';

    public function testGivenAnAuthenticatedOperatorWhenGetCompanyByUuidThenTheCompanyIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyAttributes();
        $uuid = $this->haveCompanyViaApi($attributes);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $data = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA];
        $this->assertSame($uuid, $data[static::JSON_API_KEY_ID]);
        $this->assertAttributesMatch(
            [
                CompanyTransfer::NAME => $attributes[CompanyTransfer::NAME],
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_PENDING,
                static::ATTRIBUTE_IS_ACTIVE => false,
            ],
            $this->getResourceAttributes($response),
        );
        $this->assertStringEndsWith(
            $this->tester->getCompanyUrl($uuid),
            (string)($data[static::JSON_API_KEY_LINKS][static::JSON_API_KEY_SELF] ?? ''),
            'The resource carries the self link a client follows to re-read it.',
        );
    }

    public function testGivenAnAuthenticatedOperatorWhenGetCompanyThenNoPaginationAttributeIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyViaApi();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertArrayNotHasKey(
            static::ATTRIBUTE_PAGINATION,
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnUnknownUuidWhenGetCompanyThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUrl(static::UNKNOWN_UUID));

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

    public function testGivenAMalformedUuidWhenGetCompanyThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUrl(static::MALFORMED_UUID));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenGetCompanyThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUrl(static::UNKNOWN_UUID));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenGetCompanyThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUrl(static::UNKNOWN_UUID));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
