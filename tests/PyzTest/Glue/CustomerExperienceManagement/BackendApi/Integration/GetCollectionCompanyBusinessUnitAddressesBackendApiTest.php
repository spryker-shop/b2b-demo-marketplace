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
 * @group GetCollectionCompanyBusinessUnitAddressesBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnitAddresses
 */
class GetCollectionCompanyBusinessUnitAddressesBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string QUERY_PARAM_SEARCH = 'q';

    protected const string FILTER_KEY_COMPANY_UUID = 'company-business-unit-addresses.companyUuid';

    protected const string FILTER_KEY_BUSINESS_UNIT_UUID = 'company-business-unit-addresses.companyBusinessUnitUuid';

    protected const string UNSUPPORTED_FILTER_KEY = 'company-business-unit-addresses.city';

    protected const string UNSUPPORTED_SORT_FIELD = 'street';

    protected const string ATTRIBUTE_CITY = 'city';

    protected const int OWNED_ADDRESS_COUNT = 2;

    public function testGivenACompanyFilterWhenSearchAddressesThenOnlyThatCompanysAddressesAreReturned(): void
    {
        // Arrange
        [$companyUuid, $firstUuid, $secondUuid] = $this->haveCompanyWithTwoAddresses();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'filter' => [static::FILTER_KEY_COMPANY_UUID => $companyUuid],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertCount(static::OWNED_ADDRESS_COUNT, $uuids);
        $this->assertContains($firstUuid, $uuids);
        $this->assertContains($secondUuid, $uuids);
    }

    public function testGivenABusinessUnitFilterWhenSearchAddressesThenOnlyTheAssignedAddressesAreReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $assignedUuid = $this->haveCompanyBusinessUnitAddressViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $unassignedUuid = $this->haveCompanyBusinessUnitAddressViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $businessUnitUuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);

        $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($businessUnitUuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [$assignedUuid],
            ]),
        );

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'filter' => [static::FILTER_KEY_BUSINESS_UNIT_UUID => $businessUnitUuid],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertContains($assignedUuid, $uuids);
        $this->assertNotContains(
            $unassignedUuid,
            $uuids,
            'An address of the same company that is not assigned to the business unit must not be listed.',
        );
    }

    public function testGivenASearchTermWhenSearchAddressesThenOnlyTheMatchingAddressesAreReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $city = 'CxmCity' . uniqid();
        $matchingUuid = $this->haveCompanyBusinessUnitAddressViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
            static::ATTRIBUTE_CITY => $city,
        ]);
        $otherUuid = $this->haveCompanyBusinessUnitAddressViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            static::QUERY_PARAM_SEARCH => $city,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertContains($matchingUuid, $uuids);
        $this->assertNotContains($otherUuid, $uuids);
    }

    public function testGivenNoSearchTermWhenSearchAddressesThenNothingIsFilteredAway(): void
    {
        // Arrange
        [$companyUuid] = $this->haveCompanyWithTwoAddresses();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'filter' => [static::FILTER_KEY_COMPANY_UUID => $companyUuid],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(static::OWNED_ADDRESS_COUNT, $this->getResourceIds($response));
    }

    public function testGivenASortByCityWhenSearchAddressesThenTheCitiesAreAscending(): void
    {
        // Arrange
        [$companyUuid] = $this->haveCompanyWithTwoAddresses();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'filter' => [static::FILTER_KEY_COMPANY_UUID => $companyUuid],
            'sort' => static::ATTRIBUTE_CITY,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $cities = array_map(
            static fn (array $resource): string => (string)($resource['attributes']['city'] ?? ''),
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA],
        );
        $sortedCities = $cities;
        sort($sortedCities);
        $this->assertSame($sortedCities, $cities);
    }

    public function testGivenAnUnsupportedSortFieldWhenSearchAddressesThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'sort' => static::UNSUPPORTED_SORT_FIELD,
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    public function testGivenAFilterOnAnUnsupportedPropertyWhenSearchAddressesThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'filter' => [static::UNSUPPORTED_FILTER_KEY => 'Berlin'],
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD,
        );
    }

    public function testGivenAnArrayValuedFilterWhenSearchAddressesThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'filter' => [static::FILTER_KEY_COMPANY_UUID => [static::UNKNOWN_UUID, static::UNKNOWN_UUID]],
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD,
        );
    }

    public function testGivenAFilterOnAnUnknownCompanyWhenSearchAddressesThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'filter' => [static::FILTER_KEY_COMPANY_UUID => static::UNKNOWN_UUID],
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_NOT_FOUND,
        );
    }

    public function testGivenAPageLimitWhenSearchAddressesThenOnePageAndItsPaginationMetadataAreReturned(): void
    {
        // Arrange
        [$companyUuid] = $this->haveCompanyWithTwoAddresses();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl([
            'filter' => [static::FILTER_KEY_COMPANY_UUID => $companyUuid],
            'page' => ['limit' => 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(1, $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA]);

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(static::OWNED_ADDRESS_COUNT, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(static::OWNED_ADDRESS_COUNT, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
    }

    public function testGivenNoAuthenticationWhenSearchAddressesThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array{0: string, 1: string, 2: string} Company uuid, then the two address uuids.
     */
    protected function haveCompanyWithTwoAddresses(): array
    {
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();

        return [
            $companyUuid,
            $this->haveCompanyBusinessUnitAddressViaApi([
                static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
                static::ATTRIBUTE_CITY => 'Aachen',
            ]),
            $this->haveCompanyBusinessUnitAddressViaApi([
                static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
                static::ATTRIBUTE_CITY => 'Zwickau',
            ]),
        ];
    }
}
