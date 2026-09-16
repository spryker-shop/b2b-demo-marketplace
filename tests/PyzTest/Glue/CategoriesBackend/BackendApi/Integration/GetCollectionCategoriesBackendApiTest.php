<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CategoriesBackend\BackendApi\Integration;

use PyzTest\Glue\CategoriesBackend\AbstractCategoriesBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /categories` over a booted GLUE_BACKEND kernel: pagination contract, sorting, the
 * parentCategoryKey filter and both authorization layers.
 *

 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CategoriesBackend
 * @group BackendApi
 * @group Integration
 * @group GetCollectionCategoriesBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionCategoriesBackendApiTest extends AbstractCategoriesBackendApiTestCase
{
    protected const string UNSUPPORTED_SORT_FIELD = 'idCategory';

    protected const string RESPONSE_CODE_BAD_REQUEST = '400';

    /**
     * @uses categories.resource.yml `paginationItemsPerPage`
     */
    protected const int DEFAULT_PAGE_LIMIT = 10;

    public function testGivenMoreCategoriesThanPageLimitWhenListingThenPaginationIsTopLevelMetaWithLinks(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey]);
        $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_PAGE => [static::QUERY_LIMIT => 1, static::QUERY_OFFSET => 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(1, $this->getResourceIds($response));

        $pagination = $this->getMetaPagination($response);
        $this->assertGreaterThanOrEqual(2, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_CURRENT_PAGE]);
        $this->assertSame(1, $pagination[static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE]);
        $this->assertGreaterThanOrEqual(2, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
        $this->assertNoPaginationInsideMembers($response);

        $links = $this->getLinks($response);
        foreach ([static::JSON_API_KEY_SELF, static::LINK_FIRST, static::LINK_LAST, static::LINK_PREV, static::LINK_NEXT] as $link) {
            $this->assertArrayHasKey($link, $links);
        }
        $this->assertStringContainsString('page[offset]=0', $links[static::LINK_PREV]);
        $this->assertStringContainsString('page[offset]=2', $links[static::LINK_NEXT]);
    }

    public function testGivenParentCategoryKeyFilterWhenListingThenOnlyDirectChildrenAreReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $parentCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $childCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $parentCategoryKey])->getCategoryKeyOrFail();
        $grandchildCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $childCategoryKey])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_PARENT_CATEGORY_KEY => $parentCategoryKey,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$childCategoryKey], $this->getResourceIds($response));
        $this->assertNotContains($grandchildCategoryKey, $this->getResourceIds($response));
    }

    public function testGivenParentCategoryKeyFilterCombinedWithPositionSortWhenListingThenChildrenAreOrdered(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $parentCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();
        $secondChildCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $parentCategoryKey, 'position' => 20])->getCategoryKeyOrFail();
        $firstChildCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $parentCategoryKey, 'position' => 10])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_PARENT_CATEGORY_KEY => $parentCategoryKey,
            static::QUERY_SORT => 'position',
            static::QUERY_PAGE => [static::QUERY_LIMIT => 500],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$firstChildCategoryKey, $secondChildCategoryKey], $this->getResourceIds($response));
    }

    public function testGivenSortByCategoryKeyWhenListingThenCategoriesAreOrderedAscendingAndDescending(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $categoryKeyBase = 'pm-int-sort-' . uniqid();
        $firstCategoryKey = $categoryKeyBase . '-a';
        $secondCategoryKey = $categoryKeyBase . '-b';
        $this->tester->haveCategoryViaFacade(['categoryKey' => $secondCategoryKey, 'parentCategoryKey' => $rootCategoryKey]);
        $this->tester->haveCategoryViaFacade(['categoryKey' => $firstCategoryKey, 'parentCategoryKey' => $rootCategoryKey]);

        // Act
        $ascendingCategoryKeys = $this->getResourceIds($this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_SORT => 'categoryKey',
            static::QUERY_PAGE => [static::QUERY_LIMIT => 500],
        ])));
        $descendingCategoryKeys = $this->getResourceIds($this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_SORT => '-categoryKey',
            static::QUERY_PAGE => [static::QUERY_LIMIT => 500],
        ])));

        // Assert
        $this->assertLessThan(
            array_search($secondCategoryKey, $ascendingCategoryKeys, true),
            array_search($firstCategoryKey, $ascendingCategoryKeys, true),
            'Ascending categoryKey sort must return the alphabetically smaller key first.',
        );
        $this->assertLessThan(
            array_search($firstCategoryKey, $descendingCategoryKeys, true),
            array_search($secondCategoryKey, $descendingCategoryKeys, true),
            'Descending categoryKey sort must return the alphabetically greater key first.',
        );
    }

    public function testGivenSortByPositionWhenListingThenCategoriesAreOrderedByNodeOrder(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $lowPositionCategoryKey = 'pm-int-sort-pos-' . uniqid() . '-low';
        $highPositionCategoryKey = 'pm-int-sort-pos-' . uniqid() . '-high';
        $this->tester->haveCategoryViaFacade(['categoryKey' => $highPositionCategoryKey, 'parentCategoryKey' => $rootCategoryKey, 'position' => 99992]);
        $this->tester->haveCategoryViaFacade(['categoryKey' => $lowPositionCategoryKey, 'parentCategoryKey' => $rootCategoryKey, 'position' => 99991]);

        // Act
        $ascendingCategoryKeys = $this->getResourceIds($this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_SORT => 'position',
            static::QUERY_PAGE => [static::QUERY_LIMIT => 500],
        ])));
        $descendingCategoryKeys = $this->getResourceIds($this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_SORT => '-position',
            static::QUERY_PAGE => [static::QUERY_LIMIT => 500],
        ])));

        // Assert
        $this->assertLessThan(
            array_search($highPositionCategoryKey, $ascendingCategoryKeys, true),
            array_search($lowPositionCategoryKey, $ascendingCategoryKeys, true),
            'Ascending position sort must return the lower node order first.',
        );
        $this->assertLessThan(
            array_search($lowPositionCategoryKey, $descendingCategoryKeys, true),
            array_search($highPositionCategoryKey, $descendingCategoryKeys, true),
            'Descending position sort must return the higher node order first.',
        );
    }

    public function testGivenSortByNameWhenListingThenCategoriesAreOrderedByLocalizedName(): void
    {
        // Arrange: keys sort in the opposite order to the names, proving the sort uses the name.
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $nameBase = 'pmintname' . uniqid();
        $laterNameCategoryKey = 'pm-int-name-' . uniqid() . '-a';
        $earlierNameCategoryKey = 'pm-int-name-' . uniqid() . '-b';
        $this->tester->haveCategoryViaFacade(['categoryKey' => $laterNameCategoryKey, 'parentCategoryKey' => $rootCategoryKey, 'localizedAttributes' => $this->buildLocalizedAttributes($nameBase . '-zzz')]);
        $this->tester->haveCategoryViaFacade(['categoryKey' => $earlierNameCategoryKey, 'parentCategoryKey' => $rootCategoryKey, 'localizedAttributes' => $this->buildLocalizedAttributes($nameBase . '-aaa')]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_SORT => 'name',
            static::QUERY_PAGE => [static::QUERY_LIMIT => 500],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $categoryKeys = $this->getResourceIds($response);
        $this->assertLessThan(
            array_search($laterNameCategoryKey, $categoryKeys, true),
            array_search($earlierNameCategoryKey, $categoryKeys, true),
            'Ascending name sort must return the alphabetically smaller name first, regardless of categoryKey.',
        );
    }

    public function testGivenSortByNameWhenCategoryHasNoNameInRequestLocaleThenItIsExcludedFromTheSortedCollectionOnly(): void
    {
        // Arrange: the name sort joins the attribute of the request locale only, so a category
        // without a name in that locale has nothing to sort by and drops out of the sorted list.
        // Each category is named in exactly one (different) locale, so whichever locale the
        // request resolves to, exactly one of them must be excluded — no assumption on the locale.
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $firstLocaleOnlyCategoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'localizedAttributes' => [['localeName' => $localeNames[0], 'name' => $this->tester->generateCategoryName()]],
        ])->getCategoryKeyOrFail();
        $secondLocaleOnlyCategoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'localizedAttributes' => [['localeName' => $localeNames[1], 'name' => $this->tester->generateCategoryName()]],
        ])->getCategoryKeyOrFail();

        // Act
        $sortedResponse = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_SORT => 'name',
            static::QUERY_PAGE => [static::QUERY_LIMIT => 500],
        ]));
        $unsortedResponse = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([
            static::QUERY_PAGE => [static::QUERY_LIMIT => 500],
        ]));

        // Assert
        $this->assertRespondsWithStatus($sortedResponse, Response::HTTP_OK);
        $sortedCategoryKeys = $this->getResourceIds($sortedResponse);
        $this->assertCount(
            1,
            array_intersect([$firstLocaleOnlyCategoryKey, $secondLocaleOnlyCategoryKey], $sortedCategoryKeys),
            'Exactly the category named in the request locale must be part of the name-sorted collection; the other one has nothing to sort by.',
        );

        $this->assertRespondsWithStatus($unsortedResponse, Response::HTTP_OK);
        $unsortedCategoryKeys = $this->getResourceIds($unsortedResponse);
        $this->assertContains($firstLocaleOnlyCategoryKey, $unsortedCategoryKeys, 'Without the name sort both categories must be listed.');
        $this->assertContains($secondLocaleOnlyCategoryKey, $unsortedCategoryKeys, 'Without the name sort both categories must be listed.');
    }

    public function testGivenNoPaginationParametersWhenListingThenDefaultsAreApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->getRootCategoryKey();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $pagination = $this->getMetaPagination($response);
        $this->assertSame(static::DEFAULT_PAGE_LIMIT, $pagination[static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE], 'page[limit] must default to the documented value.');
        $this->assertSame(1, $pagination[static::PAGINATION_KEY_CURRENT_PAGE], 'page[offset] must default to 0, which is page 1.');
        $this->assertLessThanOrEqual(static::DEFAULT_PAGE_LIMIT, count($this->getResourceIds($response)));
        $this->assertArrayHasKey(static::JSON_API_KEY_SELF, $this->getLinks($response));
    }

    public function testGivenUnsupportedSortFieldWhenListingThenBadRequestIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([static::QUERY_SORT => static::UNSUPPORTED_SORT_FIELD]));

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_BAD_REQUEST, static::RESPONSE_CODE_BAD_REQUEST);
    }

    public function testGivenUnknownParentCategoryKeyFilterWhenListingThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl([static::QUERY_PARENT_CATEGORY_KEY => static::UNKNOWN_CATEGORY_KEY]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenListingThenUnauthorizedIsReturned(): void
    {
        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenOperatorWithoutAclAccessWhenListingThenForbiddenIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * @return array<array<string, string>>
     */
    protected function buildLocalizedAttributes(string $name): array
    {
        $localizedAttributes = [];
        foreach ($this->tester->getAvailableLocaleNames() as $localeName) {
            $localizedAttributes[] = ['localeName' => $localeName, 'name' => $name];
        }

        return $localizedAttributes;
    }
}
