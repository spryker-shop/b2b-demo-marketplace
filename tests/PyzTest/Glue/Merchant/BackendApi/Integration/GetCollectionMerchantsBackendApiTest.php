<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Merchant\BackendApi\Integration;

use PyzTest\Glue\Merchant\AbstractMerchantBackendApiTestCase;
use Spryker\Zed\Merchant\MerchantConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Merchant
 * @group BackendApi
 * @group Integration
 * @group GetCollectionMerchantsBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionMerchantsBackendApiTest extends AbstractMerchantBackendApiTestCase
{
    protected const string FILTER_SEARCH_TERM = 'merchants.q';

    protected const string FILTER_IS_ACTIVE = 'merchants.isActive';

    protected const string FILTER_STATUSES = 'merchants.statuses';

    protected const string FILTER_STORES = 'merchants.stores';

    protected const string QUERY_PARAM_SORT = 'sort';

    protected const string QUERY_PARAM_FILTER = 'filter';

    protected const string QUERY_PARAM_PAGE = 'page';

    protected const string PAGE_PARAM_LIMIT = 'limit';

    protected const string PAGE_PARAM_OFFSET = 'offset';

    protected const string PAGINATION_KEY_MAX_PAGE = 'maxPage';

    protected const string PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE = 'currentItemsPerPage';

    protected const string UNSUPPORTED_SORT_FIELD = 'registrationNumber';

    protected const string UNSUPPORTED_FILTER = 'merchants.active';

    protected const int ABOVE_MAXIMUM_PAGE_LIMIT = 1000;

    protected const int MAXIMUM_PAGE_LIMIT = 100;

    protected const string NON_BOOLEAN_FILTER_VALUE = 'maybe';

    protected const string FALSY_SEARCH_TERM = '0';

    protected const string DIGIT_FREE_NAME_PREFIX = 'MerchantBackendApiNoDigits';

    public function testGivenNoAuthenticationWhenGetMerchantCollectionThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getMerchantCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAMerchantUserTokenWhenGetMerchantCollectionThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsMerchantUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getMerchantCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenTwoMerchantsWhenFilteredBySearchTermThenBothAreReturnedWithPagination(): void
    {
        // Arrange
        [$searchTerm, $firstMerchantTransfer, $secondMerchantTransfer] = $this->tester->haveTwoListedMerchants();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->searchUrl($searchTerm));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [
                $firstMerchantTransfer->getMerchantReferenceOrFail(),
                $secondMerchantTransfer->getMerchantReferenceOrFail(),
            ],
            $this->getResourceIds($response),
            'The default order is by merchant name, ascending.',
        );
        $this->assertSame(2, $this->getMetaPagination($response)[static::PAGINATION_KEY_NUM_FOUND] ?? null);
    }

    /**
     * `"0"` is the one search term PHP reads as falsy. It used to be dropped as an absent filter,
     * so the request answered the whole collection instead of the merchants matching it.
     */
    public function testGivenASearchTermOfZeroWhenGetMerchantCollectionThenItIsAppliedAsAFilter(): void
    {
        // Arrange - a name without a digit, so this merchant cannot match the search term "0".
        $this->tester->haveListedMerchant(
            static::DIGIT_FREE_NAME_PREFIX . strtr(uniqid(), '0123456789', 'ghijklmnop'),
            MerchantConfig::STATUS_APPROVED,
            true,
        );
        $this->tester->actingAsUser();

        // Act
        $unfilteredResponse = $this->handleApiRequest('GET', $this->tester->getMerchantCollectionUrl());
        $filteredResponse = $this->handleApiRequest('GET', $this->searchUrl(static::FALSY_SEARCH_TERM));

        // Assert
        $this->assertRespondsWithStatus($filteredResponse, Response::HTTP_OK);
        $this->assertLessThan(
            $this->getMetaPagination($unfilteredResponse)[static::PAGINATION_KEY_NUM_FOUND] ?? 0,
            $this->getMetaPagination($filteredResponse)[static::PAGINATION_KEY_NUM_FOUND] ?? 0,
            'The search term "0" must narrow the collection, not be read as an absent filter.',
        );
    }

    public function testGivenTwoMerchantsWhenFilteredByIsActiveThenOnlyTheActiveOneIsReturned(): void
    {
        // Arrange
        [$searchTerm, $activeMerchantTransfer] = $this->tester->haveTwoListedMerchants();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->searchUrl($searchTerm, [static::FILTER_IS_ACTIVE => 'true']));

        // Assert
        $this->assertSame(
            [$activeMerchantTransfer->getMerchantReferenceOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testGivenTwoMerchantsWhenFilteredByStatusesThenOnlyTheMatchingOneIsReturned(): void
    {
        // Arrange
        [$searchTerm, , $deniedMerchantTransfer] = $this->tester->haveTwoListedMerchants();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->searchUrl($searchTerm, [static::FILTER_STATUSES => MerchantConfig::STATUS_DENIED]),
        );

        // Assert
        $this->assertSame(
            [$deniedMerchantTransfer->getMerchantReferenceOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testGivenAMerchantReferenceMatchingTheSearchTermWhenGetMerchantCollectionThenItIsReturned(): void
    {
        // Arrange - the name deliberately does not contain the search term, only the reference does.
        $searchTerm = uniqid(static::DIGIT_FREE_NAME_PREFIX);
        $merchantTransfer = $this->tester->haveListedMerchantWithMerchantReference($searchTerm);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->searchUrl($searchTerm));

        // Assert
        $this->assertSame(
            [$merchantTransfer->getMerchantReferenceOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testGivenAnUnsupportedStatusFilterValueWhenGetMerchantCollectionThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantCollectionUrl([
                static::QUERY_PARAM_FILTER => [static::FILTER_STATUSES => static::UNSUPPORTED_STATUS],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            static::RESPONSE_CODE_UNKNOWN_STATUS,
        );
    }

    public function testGivenTwoMerchantsWhenFilteredByStoresThenBothAreReturnedOnce(): void
    {
        // Arrange
        [$searchTerm] = $this->tester->haveTwoListedMerchants();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->searchUrl($searchTerm, [static::FILTER_STORES => $this->tester->getStoreName()]),
        );

        // Assert — the store join must not duplicate a merchant.
        $this->assertCount(2, $this->getResourceIds($response));
    }

    public function testGivenTwoMerchantsWhenAskedForOneItemPerPageThenOnlyTheFirstPageIsReturned(): void
    {
        // Arrange
        [$searchTerm, $firstMerchantTransfer] = $this->tester->haveTwoListedMerchants();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantCollectionUrl([
                static::QUERY_PARAM_FILTER => [static::FILTER_SEARCH_TERM => $searchTerm],
                static::QUERY_PARAM_PAGE => [static::PAGE_PARAM_LIMIT => 1],
            ]),
        );

        // Assert
        $this->assertSame(
            [$firstMerchantTransfer->getMerchantReferenceOrFail()],
            $this->getResourceIds($response),
        );
        $this->assertSame(2, $this->getMetaPagination($response)[static::PAGINATION_KEY_MAX_PAGE] ?? null);
    }

    public function testGivenTwoMerchantsWhenSortedByNameDescendingThenTheOrderIsReversed(): void
    {
        // Arrange
        [$searchTerm, $firstMerchantTransfer, $secondMerchantTransfer] = $this->tester->haveTwoListedMerchants();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->searchUrl($searchTerm, [], ['-' . static::ATTRIBUTE_NAME]),
        );

        // Assert
        $this->assertSame(
            [
                $secondMerchantTransfer->getMerchantReferenceOrFail(),
                $firstMerchantTransfer->getMerchantReferenceOrFail(),
            ],
            $this->getResourceIds($response),
        );
    }

    /**
     * The item window is addressed by offset, not by a page number. The offset is deliberately not a
     * multiple of the limit, because that is the only case in which rounding it to the containing
     * page answers different rows than the ones asked for.
     */
    public function testGivenAnItemOffsetWhenGetMerchantCollectionThenTheWindowStartsAtThatItem(): void
    {
        // Arrange
        [$searchTerm, , $secondMerchantTransfer, $thirdMerchantTransfer] = $this->tester->haveThreeListedMerchants();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantCollectionUrl([
                static::QUERY_PARAM_FILTER => [static::FILTER_SEARCH_TERM => $searchTerm],
                static::QUERY_PARAM_SORT => static::ATTRIBUTE_NAME,
                static::QUERY_PARAM_PAGE => [static::PAGE_PARAM_LIMIT => 2, static::PAGE_PARAM_OFFSET => 1],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [
                $secondMerchantTransfer->getMerchantReferenceOrFail(),
                $thirdMerchantTransfer->getMerchantReferenceOrFail(),
            ],
            $this->getResourceIds($response),
        );
        $this->assertSame(3, $this->getMetaPagination($response)[static::PAGINATION_KEY_NUM_FOUND] ?? null);
    }

    public function testGivenAPaginationLimitAboveTheMaximumWhenGetMerchantCollectionThenItIsReducedToTheMaximum(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantCollectionUrl([
                static::QUERY_PARAM_PAGE => [static::PAGE_PARAM_LIMIT => static::ABOVE_MAXIMUM_PAGE_LIMIT],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            static::MAXIMUM_PAGE_LIMIT,
            $this->getMetaPagination($response)[static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE] ?? null,
        );
    }

    public function testGivenAnUnknownFilterWhenGetMerchantCollectionThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantCollectionUrl([
                static::QUERY_PARAM_FILTER => [static::UNSUPPORTED_FILTER => 'true'],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            static::RESPONSE_CODE_UNKNOWN_FILTER,
        );
    }

    /**
     * An unrecognised boolean is refused rather than read as `false`, which would answer a
     * plausible-looking list of inactive merchants to a request that asked for something else.
     */
    public function testGivenANonBooleanIsActiveFilterWhenGetMerchantCollectionThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantCollectionUrl([
                static::QUERY_PARAM_FILTER => [static::FILTER_IS_ACTIVE => static::NON_BOOLEAN_FILTER_VALUE],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            static::RESPONSE_CODE_INVALID_FILTER_VALUE,
        );
    }

    public function testGivenAnUnsupportedSortFieldWhenGetMerchantCollectionThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantCollectionUrl([static::QUERY_PARAM_SORT => static::UNSUPPORTED_SORT_FIELD]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            static::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    public function testGivenSeveralSortFieldsWhenGetMerchantCollectionThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act — the merchant collection orders by a single column, so this is refused rather than trimmed.
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantCollectionUrl([
                static::QUERY_PARAM_SORT => static::ATTRIBUTE_NAME . ',' . static::ATTRIBUTE_STATUS,
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            static::RESPONSE_CODE_MULTIPLE_SORT_FIELDS,
        );
    }

    /**
     * @param array<string, string> $filters
     * @param array<int, string> $sortFields
     */
    protected function searchUrl(string $searchTerm, array $filters = [], array $sortFields = []): string
    {
        $query = [
            static::QUERY_PARAM_FILTER => [static::FILTER_SEARCH_TERM => $searchTerm] + $filters,
        ];

        if ($sortFields !== []) {
            $query[static::QUERY_PARAM_SORT] = implode(',', $sortFields);
        }

        return $this->tester->getMerchantCollectionUrl($query);
    }
}
