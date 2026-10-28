<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
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
 * @group GetCollectionCompanyBusinessUnitsBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnits
 */
class GetCollectionCompanyBusinessUnitsBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string QUERY_PARAM_SEARCH = 'q';

    protected const string FILTER_KEY_NAME = 'company-business-units.name';

    protected const string SORT_FIELD_COMPANY_NAME = 'companyName';

    protected const string SORT_FIELD_PARENT_NAME = 'parentName';

    protected const string UNSUPPORTED_SORT_FIELD = 'idCompanyBusinessUnit';

    protected const string UNSUPPORTED_FILTER_KEY = 'company-business-units.companyUuid';

    protected const string TERM_MATCHING_NOTHING = 'CxmNoBusinessUnitCarriesThisTerm';

    protected const int LISTED_BUSINESS_UNIT_COUNT = 2;

    public function testGivenNoSearchTermWhenSearchBusinessUnitsThenAUnitMatchingNoTermIsStillReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $uuid,
            $this->getResourceIds($response)[0] ?? null,
            'With no search term the collection is unfiltered, so the newest business unit leads.',
        );
    }

    public function testGivenAnEmptySearchTermWhenSearchBusinessUnitsThenItIsTreatedAsNoTerm(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => '',
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $uuid,
            $this->getResourceIds($response)[0] ?? null,
            'An empty term is discarded, so the collection is returned unfiltered.',
        );
    }

    public function testGivenNoSearchTermWhenSearchBusinessUnitsThenTheTotalIsNotSmallerThanAFilteredTotal(): void
    {
        // Arrange
        [$token] = $this->haveTwoListedBusinessUnits();

        // Act
        $unfiltered = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl());
        $filtered = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
        ]));

        // Assert
        $this->assertGreaterThanOrEqual(
            $this->getMetaPagination($filtered)[static::PAGINATION_KEY_NUM_FOUND],
            $this->getMetaPagination($unfiltered)[static::PAGINATION_KEY_NUM_FOUND],
            'The unfiltered collection is a superset of any search result.',
        );
    }

    public function testGivenASearchTermWhenSearchBusinessUnitsThenOnlyTheMatchingUnitsAreReturned(): void
    {
        // Arrange
        [$token, $firstUuid, $secondUuid] = $this->haveTwoListedBusinessUnits();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertCount(static::LISTED_BUSINESS_UNIT_COUNT, $uuids);
        $this->assertContains($firstUuid, $uuids);
        $this->assertContains($secondUuid, $uuids);
    }

    public function testGivenASearchTermInADifferentCaseWhenSearchBusinessUnitsThenTheMatchIsStillFound(): void
    {
        // Arrange
        [$token, $firstUuid] = $this->haveTwoListedBusinessUnits();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => strtoupper($token),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains($firstUuid, $this->getResourceIds($response));
    }

    public function testGivenASearchTermOnTheOwningCompanyNameWhenSearchBusinessUnitsThenItsUnitIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $token = $this->tester->buildListedCompanyNameToken();
        $companyUuid = $this->haveCompanyViaApi([CompanyTransfer::NAME => $token . 'Owner']);
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains(
            $uuid,
            $this->getResourceIds($response),
            'The term searches the owning company name as well as the unit name.',
        );
    }

    public function testGivenASearchTermOnTheParentUnitNameWhenSearchBusinessUnitsThenTheChildIsReturned(): void
    {
        // Arrange
        [$token, , $childUuid] = $this->haveAParentAndItsChildBusinessUnit();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains(
            $childUuid,
            $this->getResourceIds($response),
            'The term searches the parent unit name, so a child is found by its parent.',
        );
    }

    public function testGivenASearchTermMatchingNothingWhenSearchBusinessUnitsThenTheCollectionIsEmpty(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => static::TERM_MATCHING_NOTHING,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response));
        $this->assertSame(0, $this->getMetaPagination($response)[static::PAGINATION_KEY_NUM_FOUND]);
    }

    public function testGivenANameFilterWhenSearchBusinessUnitsThenOnlyTheMatchingUnitsAreReturned(): void
    {
        // Arrange
        [$token, $firstUuid, $secondUuid] = $this->haveTwoListedBusinessUnits();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertCount(static::LISTED_BUSINESS_UNIT_COUNT, $uuids);
        $this->assertContains($firstUuid, $uuids);
        $this->assertContains($secondUuid, $uuids);
    }

    public function testGivenBothANameFilterAndASearchTermWhenSearchBusinessUnitsThenBothAreApplied(): void
    {
        // Arrange
        [$token, $firstUuid, $secondUuid] = $this->haveTwoListedBusinessUnits();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
            'filter' => [static::FILTER_KEY_NAME => $token . 'Alpha'],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertContains($firstUuid, $uuids);
        $this->assertNotContains(
            $secondUuid,
            $uuids,
            'The name filter narrows the search result rather than being ignored beside it.',
        );
    }

    public function testGivenAnAscendingNameSortWhenSearchBusinessUnitsThenTheAlphabeticallyFirstNameLeads(): void
    {
        // Arrange
        [$token, $firstUuid] = $this->haveTwoListedBusinessUnits();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
            'sort' => CompanyBusinessUnitTransfer::NAME,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($firstUuid, $this->getResourceIds($response)[0] ?? null);
    }

    public function testGivenADescendingNameSortWhenSearchBusinessUnitsThenTheOrderIsReversed(): void
    {
        // Arrange
        [$token, , $secondUuid] = $this->haveTwoListedBusinessUnits();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
            'sort' => '-' . CompanyBusinessUnitTransfer::NAME,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($secondUuid, $this->getResourceIds($response)[0] ?? null);
    }

    public function testGivenACompanyNameSortWhenSearchBusinessUnitsThenTheUnitsAreOrderedByTheirOwner(): void
    {
        // Arrange
        [$token, $firstUuid, $secondUuid] = $this->haveTwoBusinessUnitsUnderDifferentlyNamedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
            'sort' => static::SORT_FIELD_COMPANY_NAME,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertSame(
            [$firstUuid, $secondUuid],
            array_values(array_intersect($uuids, [$firstUuid, $secondUuid])),
            'The unit under the alphabetically first company leads, even though its own name sorts last.',
        );
    }

    public function testGivenAPageLimitWhenSearchBusinessUnitsThenOnePageAndItsPaginationMetadataAreReturned(): void
    {
        // Arrange
        [$token] = $this->haveTwoListedBusinessUnits();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
            'page' => ['limit' => 1],
            'sort' => CompanyBusinessUnitTransfer::NAME,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(1, $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA]);

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(static::LISTED_BUSINESS_UNIT_COUNT, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(static::LISTED_BUSINESS_UNIT_COUNT, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
    }

    public function testGivenTwoPagesWhenBothAreReadThenNoBusinessUnitIsRepeatedOrSkipped(): void
    {
        // Arrange
        [$token, $firstUuid, $secondUuid] = $this->haveTwoListedBusinessUnits();

        // Act
        $firstPage = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
            'page' => ['limit' => 1, 'offset' => 0],
        ]));
        $secondPage = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            static::QUERY_PARAM_SEARCH => $token,
            'page' => ['limit' => 1, 'offset' => 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($firstPage, Response::HTTP_OK);
        $this->assertRespondsWithStatus($secondPage, Response::HTTP_OK);

        $paged = array_merge($this->getResourceIds($firstPage), $this->getResourceIds($secondPage));
        $this->assertCount(static::LISTED_BUSINESS_UNIT_COUNT, array_unique($paged));
        $this->assertContains($firstUuid, $paged);
        $this->assertContains($secondUuid, $paged);
    }

    public function testGivenAnUnsupportedSortFieldWhenSearchBusinessUnitsThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            'sort' => static::UNSUPPORTED_SORT_FIELD,
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    public function testGivenAFilterOnAnUnsupportedPropertyWhenSearchBusinessUnitsThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            'filter' => [static::UNSUPPORTED_FILTER_KEY => static::UNKNOWN_UUID],
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD,
        );
    }

    public function testGivenAnArrayValuedFilterWhenSearchBusinessUnitsThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => ['first', 'second']],
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD,
        );
    }

    public function testGivenNoAuthenticationWhenSearchBusinessUnitsThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenSearchBusinessUnitsThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array{0: string, 1: string, 2: string} Token, uuid of the first name, uuid of the second.
     */
    protected function haveTwoListedBusinessUnits(): array
    {
        $this->tester->actingAsUser();
        $token = $this->tester->buildListedCompanyNameToken();
        $companyUuid = $this->haveCompanyViaApi();

        return [
            $token,
            $this->haveCompanyBusinessUnitViaApi([
                static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
                CompanyBusinessUnitTransfer::NAME => $token . 'Alpha',
            ]),
            $this->haveCompanyBusinessUnitViaApi([
                static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
                CompanyBusinessUnitTransfer::NAME => $token . 'Beta',
            ]),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string} Token, uuid under the first company, uuid under the second.
     */
    protected function haveTwoBusinessUnitsUnderDifferentlyNamedCompanies(): array
    {
        $this->tester->actingAsUser();
        $token = $this->tester->buildListedCompanyNameToken();

        return [
            $token,
            $this->haveCompanyBusinessUnitViaApi([
                static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi([
                    CompanyTransfer::NAME => $token . 'Alpha',
                ]),
                CompanyBusinessUnitTransfer::NAME => $token . 'Beta',
            ]),
            $this->haveCompanyBusinessUnitViaApi([
                static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi([
                    CompanyTransfer::NAME => $token . 'Beta',
                ]),
                CompanyBusinessUnitTransfer::NAME => $token . 'Alpha',
            ]),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string} Token, parent uuid, child uuid.
     */
    protected function haveAParentAndItsChildBusinessUnit(): array
    {
        $this->tester->actingAsUser();
        $token = $this->tester->buildListedCompanyNameToken();
        $companyUuid = $this->haveCompanyViaApi();

        $parentUuid = $this->haveCompanyBusinessUnitViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
            CompanyBusinessUnitTransfer::NAME => $token . 'Parent',
        ]);

        return [
            $token,
            $parentUuid,
            $this->haveCompanyBusinessUnitViaApi([
                static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
                static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => $parentUuid,
            ]),
        ];
    }
}
