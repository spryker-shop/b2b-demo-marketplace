<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CategoriesBackend\BackendApi\Integration;

use PyzTest\Glue\CategoriesBackend\AbstractCategoriesBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * The `category-products` sub-resource over a booted GLUE_BACKEND kernel: assign, read (collection
 * with top-level pagination, item with a resolvable self link), unassign and the request guards.
 *

 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CategoriesBackend
 * @group BackendApi
 * @group Integration
 * @group CategoryProductsBackendApiTest
 * Add your own group annotations below this line
 */
class CategoryProductsBackendApiTest extends AbstractCategoriesBackendApiTestCase
{
    protected const string RESPONSE_CODE_BAD_REQUEST = '400';

    /**
     * @uses category-products.validation.yml `post.skus` Count max
     */
    protected const int MAX_SKUS_PER_REQUEST = 100;

    public function testGivenAssignedProductsWhenRetrievingCollectionThenItemsAreOrderedByPosition(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryTransfer = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()]);
        $categoryKey = $categoryTransfer->getCategoryKeyOrFail();
        $secondProductAbstractTransfer = $this->tester->haveProductAbstract([], true);
        $firstProductAbstractTransfer = $this->tester->haveProductAbstract([], true);
        // Assigned in reverse order with explicit positions to prove the position sort.
        $this->tester->haveProductAssignedToCategory($categoryTransfer->getIdCategoryOrFail(), $secondProductAbstractTransfer->getIdProductAbstractOrFail(), 20);
        $this->tester->haveProductAssignedToCategory($categoryTransfer->getIdCategoryOrFail(), $firstProductAbstractTransfer->getIdProductAbstractOrFail(), 10);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryProductCollectionUrl($categoryKey));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$firstProductAbstractTransfer->getSkuOrFail(), $secondProductAbstractTransfer->getSkuOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testGivenThreeAssignedProductsWhenRetrievingSecondPageThenOnlyTheMiddleItemIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryTransfer = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()]);
        $categoryKey = $categoryTransfer->getCategoryKeyOrFail();
        $skusIndexedByPosition = [];
        foreach ([30, 10, 20] as $position) {
            $productAbstractTransfer = $this->tester->haveProductAbstract([], true);
            $this->tester->haveProductAssignedToCategory($categoryTransfer->getIdCategoryOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail(), $position);
            $skusIndexedByPosition[$position] = $productAbstractTransfer->getSkuOrFail();
        }

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryProductCollectionUrl($categoryKey, [
            static::QUERY_PAGE => [static::QUERY_LIMIT => 1, static::QUERY_OFFSET => 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$skusIndexedByPosition[20]], $this->getResourceIds($response));
        $pagination = $this->getMetaPagination($response);
        $this->assertSame(3, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(3, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_CURRENT_PAGE]);
    }

    public function testGivenCategoryWithoutAssignmentsWhenRetrievingCollectionThenEmptyCollectionIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryProductCollectionUrl($categoryKey));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response));
    }

    public function testGivenAssignedProductWhenRetrievingItemThenItemWithNameAndPositionIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryTransfer = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()]);
        $categoryKey = $categoryTransfer->getCategoryKeyOrFail();
        $productAbstractTransfer = $this->tester->haveProductAbstract([], true);
        $this->tester->haveProductAssignedToCategory($categoryTransfer->getIdCategoryOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail(), 7);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryProductUrl($categoryKey, $productAbstractTransfer->getSkuOrFail()));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $attributes = $this->getSingleResourceAttributes($response);
        $this->assertSame($productAbstractTransfer->getSkuOrFail(), $attributes[static::ATTRIBUTE_SKU]);
        $this->assertSame(7, $attributes[static::ATTRIBUTE_POSITION]);
        $this->assertNotNull($attributes['name'], 'The localized product name must be exposed.');
    }

    public function testGivenUnknownCategoryWhenRetrievingItemThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $productAbstractTransfer = $this->tester->haveProductAbstract([], true);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryProductUrl(static::UNKNOWN_CATEGORY_KEY, $productAbstractTransfer->getSkuOrFail()));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenProductNotAssignedToCategoryWhenRetrievingItemThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $unassignedProductAbstractTransfer = $this->tester->haveProductAbstract([], true);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryProductUrl($categoryKey, $unassignedProductAbstractTransfer->getSkuOrFail()));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenUnknownSkuWhenRetrievingItemThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryProductUrl($categoryKey, static::UNKNOWN_SKU));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenExistingProductsWhenAssigningThenMappingsAreCreated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryTransfer = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()]);
        $productAbstractTransfer = $this->tester->haveProductAbstract([], true);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getAssignProductsUrl($categoryTransfer->getCategoryKeyOrFail()),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => [$productAbstractTransfer->getSkuOrFail()]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertTrue($this->tester->isProductAssignedToCategory($categoryTransfer->getIdCategoryOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail()));
    }

    public function testGivenAlreadyAssignedProductWhenAssigningAgainThenRequestIsIdempotentWithoutDuplicates(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryTransfer = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()]);
        $productAbstractTransfer = $this->tester->haveProductAbstract([], true);
        $this->tester->haveProductAssignedToCategory($categoryTransfer->getIdCategoryOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail());

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getAssignProductsUrl($categoryTransfer->getCategoryKeyOrFail()),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => [$productAbstractTransfer->getSkuOrFail()]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertSame(
            1,
            $this->tester->countProductCategoryAssignments($categoryTransfer->getIdCategoryOrFail(), $productAbstractTransfer->getIdProductAbstractOrFail()),
            'Re-assigning must not create a duplicate mapping row.',
        );
    }

    /**
     * @dataProvider invalidAssignBodyDataProvider
     *
     * @param array<string, mixed> $body
     */
    public function testGivenInvalidBodyWhenAssigningThenBadRequestIsReturned(array $body): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getAssignProductsUrl($categoryKey), $this->tester->buildCategoryProductsRequestBody($body));

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_BAD_REQUEST, static::RESPONSE_CODE_BAD_REQUEST);
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public static function invalidAssignBodyDataProvider(): array
    {
        return [
            'missing skus' => [[]],
            'empty skus' => [['skus' => []]],
            'blank skus' => [['skus' => ['', ' ']]],
            'all is not allowed on assign' => [['skus' => ['001'], 'all' => true]],
        ];
    }

    public function testGivenMoreSkusThanTheBatchLimitWhenAssigningThenUnprocessableEntityIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $skus = array_map(static fn (int $index): string => sprintf('%s-%d', static::UNKNOWN_SKU, $index), range(1, static::MAX_SKUS_PER_REQUEST + 1));

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getAssignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => $skus]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(1, $this->getErrorDetails($response), 'The batch-size violation is reported once, before any SKU is resolved.');
    }

    public function testGivenUnknownCategoryWhenAssigningThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getAssignProductsUrl(static::UNKNOWN_CATEGORY_KEY),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => ['pm-test-any-sku']]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider invalidUnassignBodyDataProvider
     *
     * @param array<string, mixed> $body
     */
    public function testGivenInvalidBodyWhenUnassigningThenBadRequestIsReturned(array $body): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getUnassignProductsUrl($categoryKey), $this->tester->buildCategoryProductsRequestBody($body));

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_BAD_REQUEST, static::RESPONSE_CODE_BAD_REQUEST);
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public static function invalidUnassignBodyDataProvider(): array
    {
        return [
            'neither skus nor all' => [[]],
            'empty skus' => [['skus' => []]],
            'blank skus' => [['skus' => [' ', '']]],
            'all false without skus' => [['all' => false]],
        ];
    }

    public function testGivenCategoryWithoutAssignmentsWhenUnassigningAllThenRequestIsIdempotent(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getUnassignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_ALL => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
    }

    public function testGivenUnknownCategoryWhenUnassigningThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getUnassignProductsUrl(static::UNKNOWN_CATEGORY_KEY),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => ['pm-test-any-sku']]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenPartiallyInvalidSkusWhenUnassigningThenNothingIsRemoved(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryTransfer = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()]);
        $assignedProductAbstractTransfer = $this->tester->haveProductAbstract([], true);
        $notAssignedProductAbstractTransfer = $this->tester->haveProductAbstract([], true);
        $this->tester->haveProductAssignedToCategory($categoryTransfer->getIdCategoryOrFail(), $assignedProductAbstractTransfer->getIdProductAbstractOrFail());

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getUnassignProductsUrl($categoryTransfer->getCategoryKeyOrFail()),
            $this->tester->buildCategoryProductsRequestBody([
            static::ATTRIBUTE_SKUS => [
                $assignedProductAbstractTransfer->getSkuOrFail(),
                $notAssignedProductAbstractTransfer->getSkuOrFail(),
                'pm-test-unknown-sku',
            ]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = $this->getErrorDetails($response);
        $this->assertCount(2, $errorDetails, 'One structured error per offending SKU, the assigned SKU is not reported.');
        $this->assertStringContainsString($notAssignedProductAbstractTransfer->getSkuOrFail(), $errorDetails[0]);
        $this->assertStringContainsString('pm-test-unknown-sku', $errorDetails[1]);
        $this->assertTrue(
            $this->tester->isProductAssignedToCategory($categoryTransfer->getIdCategoryOrFail(), $assignedProductAbstractTransfer->getIdProductAbstractOrFail()),
            'All-or-nothing: the valid SKU must still be assigned after the rejected batch.',
        );
    }

    public function testGivenAssignedProductsWhenRoundTrippingAssignAndUnassignThenNoMappingsRemain(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryTransfer = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()]);
        $firstProductAbstractTransfer = $this->tester->haveProductAbstract([], true);
        $secondProductAbstractTransfer = $this->tester->haveProductAbstract([], true);
        $skus = [$firstProductAbstractTransfer->getSkuOrFail(), $secondProductAbstractTransfer->getSkuOrFail()];

        // Act
        $this->handleApiRequest('POST', $this->tester->getAssignProductsUrl($categoryTransfer->getCategoryKeyOrFail()), $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => $skus]));
        $this->handleApiRequest('POST', $this->tester->getUnassignProductsUrl($categoryTransfer->getCategoryKeyOrFail()), $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => $skus]));

        // Assert
        $idCategory = $categoryTransfer->getIdCategoryOrFail();
        $this->assertSame(0, $this->tester->countProductCategoryAssignments($idCategory, $firstProductAbstractTransfer->getIdProductAbstractOrFail()));
        $this->assertSame(0, $this->tester->countProductCategoryAssignments($idCategory, $secondProductAbstractTransfer->getIdProductAbstractOrFail()));
    }

    public function testGivenAssignedProductsWhenReadingThenCollectionAndItemAreConsistent(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $firstSku = $this->tester->haveProductAbstract([], true)->getSkuOrFail();
        $secondSku = $this->tester->haveProductAbstract([], true)->getSkuOrFail();

        // Act
        $assignResponse = $this->handleApiRequest(
            'POST',
            $this->tester->getAssignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => [$firstSku, $secondSku]]),
        );
        $collectionResponse = $this->handleApiRequest('GET', $this->tester->getCategoryProductCollectionUrl($categoryKey, [
            static::QUERY_PAGE => [static::QUERY_LIMIT => 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($assignResponse, Response::HTTP_NO_CONTENT);
        $this->assertRespondsWithStatus($collectionResponse, Response::HTTP_OK);
        $this->assertCount(1, $this->getResourceIds($collectionResponse));
        $pagination = $this->getMetaPagination($collectionResponse);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
        $this->assertNoPaginationInsideMembers($collectionResponse);
        $this->assertArrayHasKey(static::LINK_NEXT, $this->getLinks($collectionResponse));

        $firstMember = $this->getJsonApiMembers($collectionResponse, static::JSON_API_KEY_DATA)[0];
        $this->assertContains($firstMember[static::JSON_API_KEY_ID], [$firstSku, $secondSku]);
        $selfLink = (string)($firstMember[static::JSON_API_KEY_LINKS][static::JSON_API_KEY_SELF] ?? '');
        $this->assertStringContainsString($this->tester->getCategoryProductUrl($categoryKey, $firstMember[static::JSON_API_KEY_ID]), $selfLink, 'The member self link must point at the sub-resource item route.');

        $itemResponse = $this->handleApiRequest('GET', $selfLink);
        $this->assertRespondsWithStatus($itemResponse, Response::HTTP_OK);
        $this->assertSame($firstMember[static::JSON_API_KEY_ID], $this->getSingleResourceAttributes($itemResponse)[static::ATTRIBUTE_SKU]);
    }

    public function testGivenAssignedProductsWhenUnassigningThenCollectionShrinksAndEmptiesWithAll(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $firstSku = $this->tester->haveProductAbstract([], true)->getSkuOrFail();
        $secondSku = $this->tester->haveProductAbstract([], true)->getSkuOrFail();
        $this->assertRespondsWithStatus($this->handleApiRequest(
            'POST',
            $this->tester->getAssignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => [$firstSku, $secondSku]]),
        ), Response::HTTP_NO_CONTENT);

        // Act
        $unassignSingleResponse = $this->handleApiRequest(
            'POST',
            $this->tester->getUnassignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => [$firstSku]]),
        );
        $afterSingle = $this->getResourceIds($this->handleApiRequest('GET', $this->tester->getCategoryProductCollectionUrl($categoryKey)));
        $unassignAllResponse = $this->handleApiRequest(
            'POST',
            $this->tester->getUnassignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_ALL => true]),
        );
        $afterAllResponse = $this->handleApiRequest('GET', $this->tester->getCategoryProductCollectionUrl($categoryKey));

        // Assert
        $this->assertRespondsWithStatus($unassignSingleResponse, Response::HTTP_NO_CONTENT);
        $this->assertSame([$secondSku], $afterSingle);
        $this->assertRespondsWithStatus($unassignAllResponse, Response::HTTP_NO_CONTENT);
        $this->assertSame([], $this->getResourceIds($afterAllResponse));
        $this->assertSame(0, $this->getMetaPagination($afterAllResponse)[static::PAGINATION_KEY_NUM_FOUND]);
    }

    public function testGivenUnknownSkuWhenAssigningThenNothingIsAssigned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $knownSku = $this->tester->haveProductAbstract([], true)->getSkuOrFail();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getAssignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => [$knownSku, static::UNKNOWN_SKU]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = $this->getErrorDetails($response);
        $this->assertCount(1, $errorDetails, 'Only the unknown SKU is reported, as its own error entry.');
        $this->assertStringContainsString(static::UNKNOWN_SKU, $errorDetails[0]);
        $this->assertSame([], $this->getResourceIds($this->handleApiRequest('GET', $this->tester->getCategoryProductCollectionUrl($categoryKey))), 'All-or-nothing: the known SKU must not be assigned either.');
    }

    public function testGivenSkusAndAllTogetherWhenUnassigningThenBadRequestIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getUnassignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => ['001'], static::ATTRIBUTE_ALL => true]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_BAD_REQUEST, static::RESPONSE_CODE_BAD_REQUEST);
    }

    public function testGivenUnknownCategoryWhenReadingProductsThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryProductCollectionUrl(static::UNKNOWN_CATEGORY_KEY));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenAssigningThenUnauthorizedIsReturned(): void
    {
        // Arrange
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getAssignProductsUrl($categoryKey),
            $this->tester->buildCategoryProductsRequestBody([static::ATTRIBUTE_SKUS => ['001']]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
