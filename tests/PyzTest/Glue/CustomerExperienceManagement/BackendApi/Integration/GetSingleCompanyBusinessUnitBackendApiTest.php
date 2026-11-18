<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

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
 * @group GetSingleCompanyBusinessUnitBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnits
 */
class GetSingleCompanyBusinessUnitBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_ID_COMPANY_BUSINESS_UNIT = 'idCompanyBusinessUnit';

    public function testGivenAnExistingBusinessUnitWhenGetBusinessUnitThenItIsReturnedByUuid(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($uuid, $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID]);
    }

    public function testGivenAnExistingBusinessUnitWhenGetBusinessUnitThenTheOwningCompanyIsExposedByUuid(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $companyUuid,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_COMPANY_UUID] ?? null,
        );
    }

    public function testGivenAnExistingBusinessUnitWhenGetBusinessUnitThenTheInternalIdIsNotExposed(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertArrayNotHasKey(
            static::ATTRIBUTE_ID_COMPANY_BUSINESS_UNIT,
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnUnknownUuidWhenGetBusinessUnitThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyBusinessUnitUrl(static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_BUSINESS_UNIT_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenGetBusinessUnitThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyBusinessUnitUrl(static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
