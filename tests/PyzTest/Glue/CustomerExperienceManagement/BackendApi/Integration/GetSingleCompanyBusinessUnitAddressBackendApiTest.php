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
 * @group GetSingleCompanyBusinessUnitAddressBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnitAddresses
 */
class GetSingleCompanyBusinessUnitAddressBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_ISO2_CODE = 'iso2Code';

    protected const string ATTRIBUTE_CITY = 'city';

    protected const string ATTRIBUTE_ID_COMPANY_UNIT_ADDRESS = 'idCompanyUnitAddress';

    public function testGivenAnExistingAddressWhenGetAddressThenItIsReturnedByUuid(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitAddressViaApi();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($uuid, $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID]);
    }

    public function testGivenAnExistingAddressWhenGetAddressThenTheRelationDerivedFieldsArePopulated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitAddressViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $resourceAttributes = $this->getResourceAttributes($response);
        $this->assertSame($companyUuid, $resourceAttributes[static::ATTRIBUTE_COMPANY_UUID] ?? null);
        $this->assertNotNull($resourceAttributes[static::ATTRIBUTE_ISO2_CODE] ?? null);
        $this->assertNotNull($resourceAttributes[static::ATTRIBUTE_CITY] ?? null);
    }

    public function testGivenAnExistingAddressWhenGetAddressThenTheInternalIdIsNotExposed(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitAddressViaApi();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertArrayNotHasKey(
            static::ATTRIBUTE_ID_COMPANY_UNIT_ADDRESS,
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnUnknownUuidWhenGetAddressThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyBusinessUnitAddressUrl(static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenGetAddressThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyBusinessUnitAddressUrl(static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
