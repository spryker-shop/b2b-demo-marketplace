<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\ProductExperienceManagement\BackendApi\Integration;

use PyzTest\Glue\ProductExperienceManagement\AbstractProductExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /products` over a booted GLUE_BACKEND kernel.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetCollectionProductsBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionProductsBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    /**
     * @uses \SprykerFeature\Glue\ProductExperienceManagement\resources\api\backend\products.resource.yml paginationItemsPerPage
     */
    protected const int ITEMS_PER_PAGE = 10;

    protected const string UNKNOWN_ABSTRACT_SKU = 'pxm-non-existent-abstract-sku';

    public function testGivenMoreProductsThanOnePageWhenGetCollectionThenTheFirstPageIsCappedAndEveryMemberIsWellFormed(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->haveProductsForPagination();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $members = $this->getJsonApiMembers($response, static::JSON_API_KEY_DATA);
        $this->assertCount(static::ITEMS_PER_PAGE, $members);

        foreach ($members as $member) {
            $this->assertSame(static::RESOURCE_TYPE, $member[static::JSON_API_KEY_TYPE] ?? null);
            $attributes = (array)($member[static::JSON_API_KEY_ATTRIBUTES] ?? []);
            $this->assertSame($member[static::JSON_API_KEY_ID] ?? null, $attributes[static::ATTRIBUTE_SKU] ?? null);

            foreach (static::ALWAYS_SERIALISED_ATTRIBUTES as $attributeName) {
                $this->assertArrayHasKey($attributeName, $attributes);
            }
        }
    }

    public function testGivenMoreProductsThanOnePageWhenGetSecondPageThenItIsDisjointFromTheFirst(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->haveProductsForPagination();

        // Act
        $firstPageResponse = $this->handleApiRequest(
            'GET',
            $this->tester->getProductCollectionUrl([
                static::QUERY_PAGE => [static::QUERY_LIMIT => static::ITEMS_PER_PAGE, static::QUERY_OFFSET => 0],
            ]),
        );
        $secondPageResponse = $this->handleApiRequest(
            'GET',
            $this->tester->getProductCollectionUrl([
                static::QUERY_PAGE => [static::QUERY_LIMIT => static::ITEMS_PER_PAGE, static::QUERY_OFFSET => static::ITEMS_PER_PAGE],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($firstPageResponse, Response::HTTP_OK);
        $this->assertRespondsWithStatus($secondPageResponse, Response::HTTP_OK);

        $firstPageIds = $this->getResourceIds($firstPageResponse);
        $secondPageIds = $this->getResourceIds($secondPageResponse);

        $this->assertNotSame([], $secondPageIds, 'The seeded floor guarantees a non-empty second page.');
        $this->assertLessThanOrEqual(static::ITEMS_PER_PAGE, count($secondPageIds));
        $this->assertSame([], array_values(array_intersect($firstPageIds, $secondPageIds)), 'Pages must not overlap.');
    }

    /**
     * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Provider\ProductsBackendProvider::buildConditionsFromRequest()
     */
    public function testGivenTwoProductsWithDistinctAbstractDataWhenFilteringThenEachFilterNarrowsAndEachMemberKeepsItsOwnAbstract(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $storeNames = $this->tester->getExistingStoreNames();

        if (count($storeNames) < 2) {
            $this->markTestSkipped('The cross-wiring regression needs two stores to tell the two abstracts apart.');
        }

        $firstAttributes = $this->haveProductViaApi([
            static::ATTRIBUTE_STORES => [$storeNames[0]],
            static::ATTRIBUTE_NEW_FROM => '2027-01-01 00:00:00',
            static::ATTRIBUTE_NEW_TO => '2027-12-31 00:00:00',
        ]);
        $secondAttributes = $this->haveProductViaApi([
            static::ATTRIBUTE_STORES => [$storeNames[0], $storeNames[1]],
            static::ATTRIBUTE_NEW_FROM => '2029-01-01 00:00:00',
            static::ATTRIBUTE_NEW_TO => '2029-12-31 00:00:00',
        ]);
        $firstSku = (string)$firstAttributes[static::ATTRIBUTE_SKU];
        $secondSku = (string)$secondAttributes[static::ATTRIBUTE_SKU];

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductCollectionUrlFilteredBySku($firstSku));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$firstSku], $this->getResourceIds($response));

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductCollectionUrlFilteredBySkus([$firstSku, $secondSku]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributesBySku = $this->getMemberAttributesBySku($response);
        $this->assertCount(2, $attributesBySku);
        $this->assertSame(sprintf('%s-abstract', $firstSku), $attributesBySku[$firstSku][static::ATTRIBUTE_ABSTRACT_SKU] ?? null);
        $this->assertSame(sprintf('%s-abstract', $secondSku), $attributesBySku[$secondSku][static::ATTRIBUTE_ABSTRACT_SKU] ?? null);
        $this->assertSame('2027-01-01 00:00:00', $attributesBySku[$firstSku][static::ATTRIBUTE_NEW_FROM] ?? null);
        $this->assertSame('2029-01-01 00:00:00', $attributesBySku[$secondSku][static::ATTRIBUTE_NEW_FROM] ?? null);
        $this->assertNotContains($storeNames[1], $attributesBySku[$firstSku][static::ATTRIBUTE_STORES] ?? []);
        $this->assertContains($storeNames[1], $attributesBySku[$secondSku][static::ATTRIBUTE_STORES] ?? []);

        // Act
        $abstractSku = sprintf('%s-abstract', $firstSku);

        // Assert
        $response = $this->handleApiRequest('GET', $this->tester->getProductCollectionUrlFilteredByAbstractSku($abstractSku));
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $members = $this->getMemberAttributesBySku($response);
        $this->assertArrayHasKey($firstSku, $members);

        foreach ($members as $memberAttributes) {
            $this->assertSame($abstractSku, $memberAttributes[static::ATTRIBUTE_ABSTRACT_SKU] ?? null);
        }
    }

    public function testGivenUnknownAbstractSkuWhenFilteringThenAnEmptyCollectionIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getProductCollectionUrlFilteredByAbstractSku(static::UNKNOWN_ABSTRACT_SKU),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getJsonApiMembers($response, static::JSON_API_KEY_DATA));
    }

    public function testGivenMoreProductsThanOnePageWhenGetCollectionThenPaginationIsTopLevelMetaWithLinks(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->haveProductsForPagination();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $pagination = $this->getMetaPagination($response);
        $numFound = $pagination[static::PAGINATION_KEY_NUM_FOUND] ?? 0;
        $this->assertGreaterThanOrEqual(static::ITEMS_PER_PAGE + 2, $numFound);
        $this->assertSame(1, $pagination[static::PAGINATION_KEY_CURRENT_PAGE] ?? null);
        $this->assertSame(static::ITEMS_PER_PAGE, $pagination[static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE] ?? null);
        $this->assertSame((int)ceil($numFound / static::ITEMS_PER_PAGE), $pagination[static::PAGINATION_KEY_MAX_PAGE] ?? null);

        $links = $this->getLinks($response);
        $this->assertArrayHasKey(static::LINK_FIRST, $links);
        $this->assertArrayHasKey(static::LINK_LAST, $links);
        $this->assertArrayHasKey(static::LINK_NEXT, $links);
        $this->assertArrayNotHasKey(static::LINK_PREV, $links, 'The first page has no previous page.');

        $this->assertStringContainsString(
            sprintf('page[limit]=%d&page[offset]=0', static::ITEMS_PER_PAGE),
            $links[static::LINK_FIRST],
        );
        $this->assertStringContainsString(
            sprintf('page[limit]=%d&page[offset]=%d', static::ITEMS_PER_PAGE, static::ITEMS_PER_PAGE),
            $links[static::LINK_NEXT],
        );
    }

    protected function haveProductsForPagination(): void
    {
        for ($index = 0; $index < static::ITEMS_PER_PAGE + 2; $index++) {
            $this->tester->haveFullProduct();
        }
    }
}
