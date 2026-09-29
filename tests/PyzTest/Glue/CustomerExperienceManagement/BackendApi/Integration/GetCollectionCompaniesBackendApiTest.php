<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\CompanyTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use Spryker\Glue\GlueJsonApiConvention\GlueJsonApiConventionConfig;
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
 * @group GetCollectionCompaniesBackendApiTest
 * Add your own group annotations below this line
 * @group Companies
 */
class GetCollectionCompaniesBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string UNSUPPORTED_SORT_FIELD = 'idCompany';

    protected const string FILTER_KEY_NAME = 'companies.name';

    protected const int LISTED_COMPANY_COUNT = 2;

    protected const int SECOND_PAGE = 2;

    protected const int OFFSET_BEYOND_THE_LAST_PAGE = 990;

    protected const int DEFAULT_ITEMS_PER_PAGE = 10;

    protected const string UNPREFIXED_FILTER_KEY = 'name';

    public function testGivenANameFilterWhenSearchCompaniesThenOnlyTheMatchingCompaniesAreReturned(): void
    {
        // Arrange
        [$token, $firstUuid, $secondUuid] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertCount(
            static::LISTED_COMPANY_COUNT,
            $uuids,
            'The name filter isolates exactly the two companies this test created.',
        );
        $this->assertContains($firstUuid, $uuids);
        $this->assertContains($secondUuid, $uuids);
    }

    public function testGivenANameFilterInADifferentCaseWhenSearchCompaniesThenTheMatchIsStillFound(): void
    {
        // Arrange
        [$token, $firstUuid] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => strtoupper($token)],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains($firstUuid, $this->getResourceIds($response));
    }

    public function testGivenAnAuthenticatedOperatorWhenSearchCompaniesThenTheInternalCompanyIdIsNotExposed(): void
    {
        // Arrange
        [$token] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertStringNotContainsString(
            static::UNSUPPORTED_SORT_FIELD,
            (string)$response->getContent(),
            'The surrogate key stays internal — companies are addressed by uuid alone.',
        );
    }

    public function testGivenAnAscendingSortWhenSearchCompaniesThenTheAlphabeticallyFirstNameLeads(): void
    {
        // Arrange
        [$token, $firstUuid] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'sort' => CompanyTransfer::NAME,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $firstUuid,
            $this->getResourceIds($response)[0] ?? null,
            'Ascending sort puts the alphabetically first name first.',
        );
    }

    public function testGivenADescendingSortWhenSearchCompaniesThenTheOrderIsReversed(): void
    {
        // Arrange
        [$token, , $secondUuid] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'sort' => '-' . CompanyTransfer::NAME,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $secondUuid,
            $this->getResourceIds($response)[0] ?? null,
            'Descending sort puts the alphabetically last name first.',
        );
    }

    public function testGivenAPageLimitWhenSearchCompaniesThenOnePageAndItsPaginationMetadataAreReturned(): void
    {
        // Arrange
        [$token] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'page' => ['limit' => 1],
            'sort' => CompanyTransfer::NAME,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $payload = $this->decodeJsonApi($response);
        $this->assertCount(
            1,
            $payload[static::JSON_API_KEY_DATA],
            'A limit of one truncates the page to a single company.',
        );

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(
            static::LISTED_COMPANY_COUNT,
            $pagination[static::PAGINATION_KEY_NUM_FOUND],
            'The pagination metadata reports both matches.',
        );
        $this->assertSame(
            static::LISTED_COMPANY_COUNT,
            $pagination[static::PAGINATION_KEY_MAX_PAGE],
            'Two matches at one per page is two pages.',
        );
        $this->assertNotEmpty(
            $payload[static::JSON_API_KEY_LINKS][static::LINK_NEXT] ?? null,
            'The second page is reachable from the response links.',
        );
    }

    public function testGivenTwoPagesWhenBothAreReadThenNoCompanyIsRepeatedOrSkipped(): void
    {
        // Arrange
        [$token, $firstUuid, $secondUuid] = $this->haveTwoListedCompanies();

        // Act
        $firstPage = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'page' => ['limit' => 1, 'offset' => 0],
        ]));
        $secondPage = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'page' => ['limit' => 1, 'offset' => 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($firstPage, Response::HTTP_OK);
        $this->assertRespondsWithStatus($secondPage, Response::HTTP_OK);

        $paged = array_merge($this->getResourceIds($firstPage), $this->getResourceIds($secondPage));
        $this->assertCount(static::LISTED_COMPANY_COUNT, array_unique($paged));
        $this->assertContains($firstUuid, $paged);
        $this->assertContains($secondUuid, $paged);
    }

    public function testGivenAnUnsupportedSortFieldWhenSearchCompaniesThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'sort' => static::UNSUPPORTED_SORT_FIELD,
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    public function testGivenNoAuthenticationWhenSearchCompaniesThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenSearchCompaniesThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenSearchCompaniesThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * Verified over HTTP (400 with code 011), but not observable here: the refusal comes from
     * {@see \Spryker\Glue\GlueJsonApiConvention\Validator\Request\FilterRequestValidator}, which
     * sits in the Glue convention request pipeline rather than the API Platform kernel this lane
     * boots. In this lane the unprefixed key instead reaches the provider and is read as a bare
     * property name.
     */
    public function testGivenAFilterKeyWithoutTheResourcePrefixWhenSearchCompaniesThenItRespondsBadRequest(): void
    {
        $this->markTestSkipped(
            'The JSON:API filter-format validator is not part of the booted API Platform kernel; '
            . 'this response is covered over HTTP only.',
        );

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::UNPREFIXED_FILTER_KEY => 'anything'],
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            GlueJsonApiConventionConfig::ERROR_CODE_UNSUPPORTED_FILTER_FORMAT,
        );
    }

    public function testGivenAFilterOnAnUnsupportedPropertyWhenSearchCompaniesThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => ['companies.status' => static::COMPANY_STATUS_APPROVED],
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD,
        );
    }

    public function testGivenAFilterAddressingAnotherResourceWhenSearchCompaniesThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => ['customers.email' => 'someone@spryker.local'],
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD,
        );
    }

    public function testGivenANameFilterOfZeroWhenSearchCompaniesThenItIsAppliedRatherThanDropped(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $token = str_replace('0', 'z', $this->tester->buildListedCompanyNameToken());
        $matchingUuid = $this->haveCompanyViaApi([CompanyTransfer::NAME => $token . 'Zero0']);
        $nonMatchingUuid = $this->haveCompanyViaApi([CompanyTransfer::NAME => $token . 'None']);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => '0'],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertSame(
            $matchingUuid,
            $uuids[0] ?? null,
            'A filter of "0" is applied rather than discarded as falsy, so the newest matching company leads.',
        );
        $this->assertNotContains(
            $nonMatchingUuid,
            $uuids,
            'A company whose name holds no zero is excluded, proving the filter really ran.',
        );
    }

    public function testGivenAStatusSortWhenSearchCompaniesThenTheCompaniesAreOrderedByStatus(): void
    {
        // Arrange
        [$token, $pendingUuid, $approvedUuid, $deniedUuid] = $this->haveThreeCompaniesDifferingByStatus();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'sort' => static::ATTRIBUTE_STATUS,
        ]));

        // Assert
        $this->assertSame(
            [$pendingUuid, $approvedUuid, $deniedUuid],
            $this->getResourceIds($response),
            'Status sorts by the lifecycle order pending, approved, denied — not alphabetically.',
        );
    }

    public function testGivenADescendingStatusSortWhenSearchCompaniesThenTheOrderIsReversed(): void
    {
        // Arrange
        [$token, $pendingUuid, $approvedUuid, $deniedUuid] = $this->haveThreeCompaniesDifferingByStatus();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'sort' => '-' . static::ATTRIBUTE_STATUS,
        ]));

        // Assert
        $this->assertSame(
            [$deniedUuid, $approvedUuid, $pendingUuid],
            $this->getResourceIds($response),
        );
    }

    public function testGivenAnIsActiveSortWhenSearchCompaniesThenTheInactiveCompanyLeads(): void
    {
        // Arrange
        [$token, $inactiveUuid, $activeUuid] = $this->haveTwoCompaniesDifferingByActiveState();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'sort' => static::ATTRIBUTE_IS_ACTIVE,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$inactiveUuid, $activeUuid], $this->getResourceIds($response));
    }

    public function testGivenADescendingIsActiveSortWhenSearchCompaniesThenTheActiveCompanyLeads(): void
    {
        // Arrange
        [$token, $inactiveUuid, $activeUuid] = $this->haveTwoCompaniesDifferingByActiveState();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'sort' => '-' . static::ATTRIBUTE_IS_ACTIVE,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$activeUuid, $inactiveUuid], $this->getResourceIds($response));
    }

    public function testGivenAnOffsetSelectingTheSecondPageWhenSearchCompaniesThenTheReportedPageIsTheSecond(): void
    {
        // Arrange
        [$token] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'page' => ['limit' => 1, 'offset' => 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(
            static::SECOND_PAGE,
            $pagination[static::PAGINATION_KEY_CURRENT_PAGE],
            'An offset of one whole page reports the second page, not the first.',
        );
        $this->assertSame(static::LISTED_COMPANY_COUNT, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
    }

    public function testGivenAWindowBeyondTheLastPageWhenSearchCompaniesThenTheReportedPageIsTheLastOne(): void
    {
        // Arrange
        [$token] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
            'page' => ['limit' => 1, 'offset' => static::OFFSET_BEYOND_THE_LAST_PAGE],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(
            $pagination[static::PAGINATION_KEY_MAX_PAGE],
            $pagination[static::PAGINATION_KEY_CURRENT_PAGE],
            'An over-range window reports the page actually served, never the one requested.',
        );
    }

    public function testGivenNoPageParameterWhenSearchCompaniesThenTheDefaultWindowIsReported(): void
    {
        // Arrange
        [$token] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            static::DEFAULT_ITEMS_PER_PAGE,
            $this->getMetaPagination($response)[static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE],
            'The resource declares a default window of ten items.',
        );
    }

    public function testGivenNoSortWhenSearchCompaniesThenTheMostRecentlyCreatedLeads(): void
    {
        // Arrange
        [$token, , $secondUuid] = $this->haveTwoListedCompanies();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyCollectionUrl([
            'filter' => [static::FILTER_KEY_NAME => $token],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $secondUuid,
            $this->getResourceIds($response)[0] ?? null,
            'The company created second leads when no sort is requested.',
        );
    }

    /**
     * One company per status, so an ordinal ordering is distinguishable from an alphabetical one.
     *
     * @return array{0: string, 1: string, 2: string, 3: string} Token, then the pending, approved and denied uuids.
     */
    protected function haveThreeCompaniesDifferingByStatus(): array
    {
        $this->tester->actingAsUser();
        $token = $this->tester->buildListedCompanyNameToken();

        return [
            $token,
            $this->haveCompanyViaApi([
                CompanyTransfer::NAME => $token . 'Pending',
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_PENDING,
            ]),
            $this->haveCompanyViaApi([
                CompanyTransfer::NAME => $token . 'Approved',
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_APPROVED,
            ]),
            $this->haveCompanyViaApi([
                CompanyTransfer::NAME => $token . 'Denied',
                static::ATTRIBUTE_STATUS => static::COMPANY_STATUS_DENIED,
            ]),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string} Token, uuid of the inactive one, uuid of the active one.
     */
    protected function haveTwoCompaniesDifferingByActiveState(): array
    {
        $this->tester->actingAsUser();
        $token = $this->tester->buildListedCompanyNameToken();

        return [
            $token,
            $this->haveCompanyViaApi([
                CompanyTransfer::NAME => $this->tester->buildFirstListedCompanyName($token),
                static::ATTRIBUTE_IS_ACTIVE => false,
            ]),
            $this->haveCompanyViaApi([
                CompanyTransfer::NAME => $this->tester->buildSecondListedCompanyName($token),
                static::ATTRIBUTE_IS_ACTIVE => true,
            ]),
        ];
    }

    /**
     * Two companies sharing a name token unique to this test method, so a name filter isolates
     * exactly them from the demo data the shared database already holds.
     *
     * @return array{0: string, 1: string, 2: string} Token, uuid of the first name, uuid of the second.
     */
    protected function haveTwoListedCompanies(): array
    {
        $this->tester->actingAsUser();
        $token = $this->tester->buildListedCompanyNameToken();

        return [
            $token,
            $this->haveCompanyViaApi([CompanyTransfer::NAME => $this->tester->buildFirstListedCompanyName($token)]),
            $this->haveCompanyViaApi([CompanyTransfer::NAME => $this->tester->buildSecondListedCompanyName($token)]),
        ];
    }
}
