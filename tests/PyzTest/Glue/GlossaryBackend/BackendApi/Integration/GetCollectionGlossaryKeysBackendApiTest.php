<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\GlossaryBackend\BackendApi\Integration;

use PyzTest\Glue\GlossaryBackend\AbstractGlossaryBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /glossary-keys` over a booted GLUE_BACKEND kernel: top-level pagination, sorting, the
 * `filter[glossary-keys.key]` / `filter[glossary-keys.value]` filters and the request guards.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group GlossaryBackend
 * @group BackendApi
 * @group Integration
 * @group GetCollectionGlossaryKeysBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionGlossaryKeysBackendApiTest extends AbstractGlossaryBackendApiTestCase
{
    protected const string UNSUPPORTED_SORT_FIELD = 'idGlossaryKey';

    /**
     * @uses glossary-keys.resource.yml `paginationItemsPerPage`
     */
    protected const int DEFAULT_PAGE_LIMIT = 10;

    /**
     * @uses glossary-keys.resource.yml `paginationMaximumItemsPerPage`
     */
    protected const int MAXIMUM_PAGE_LIMIT = 100;

    public function testGivenMoreKeysThanPageLimitWhenListingThenPaginationIsTopLevelMetaWithLinks(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveGlossaryKey();
        $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl([
            static::QUERY_PAGE => [static::QUERY_LIMIT => 1, static::QUERY_OFFSET => 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(1, $this->getResourceIds($response));

        $pagination = $this->getMetaPagination($response);
        $this->assertGreaterThanOrEqual(2, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_CURRENT_PAGE]);
        $this->assertSame(1, $pagination[static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE]);

        $links = $this->getLinks($response);
        foreach ([static::JSON_API_KEY_SELF, static::LINK_FIRST, static::LINK_LAST, static::LINK_PREV, static::LINK_NEXT] as $link) {
            $this->assertArrayHasKey($link, $links);
        }
        $this->assertArrayNotHasKey(
            static::META_PAGINATION,
            $this->getFirstResourceAttributes($response),
            'Pagination must not be duplicated as a resource attribute.',
        );
    }

    public function testGivenNoPaginationParametersWhenListingThenDefaultsAreApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $pagination = $this->getMetaPagination($response);
        $this->assertSame(static::DEFAULT_PAGE_LIMIT, $pagination[static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE]);
        $this->assertSame(1, $pagination[static::PAGINATION_KEY_CURRENT_PAGE]);
        $this->assertLessThanOrEqual(static::DEFAULT_PAGE_LIMIT, count($this->getResourceIds($response)));
    }

    public function testGivenPageLimitAboveMaximumWhenListingThenLimitIsReducedToMaximum(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl([
            static::QUERY_PAGE => [static::QUERY_LIMIT => static::MAXIMUM_PAGE_LIMIT + 1],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(static::MAXIMUM_PAGE_LIMIT, $this->getMetaPagination($response)[static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE]);
        $this->assertLessThanOrEqual(static::MAXIMUM_PAGE_LIMIT, count($this->getResourceIds($response)));
    }

    public function testGivenSortByKeyWhenListingThenKeysAreOrderedAscendingAndDescending(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $fragment = uniqid('sort', false);
        $laterKey = $this->tester->haveGlossaryKey(null, sprintf('glossary-backend-api.%s.zzz', $fragment));
        $earlierKey = $this->tester->haveGlossaryKey(null, sprintf('glossary-backend-api.%s.aaa', $fragment));
        $filter = [static::QUERY_FILTER => [static::FILTER_KEY => $fragment]];

        // Act
        $ascendingKeys = $this->getResourceIds($this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl(
            $filter + [static::QUERY_SORT => static::ATTRIBUTE_KEY],
        )));
        $descendingKeys = $this->getResourceIds($this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl(
            $filter + [static::QUERY_SORT => '-' . static::ATTRIBUTE_KEY],
        )));

        // Assert
        $this->assertSame([$earlierKey, $laterKey], $ascendingKeys);
        $this->assertSame([$laterKey, $earlierKey], $descendingKeys);
    }

    public function testGivenUnsupportedSortFieldWhenListingThenBadRequestIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl([static::QUERY_SORT => static::UNSUPPORTED_SORT_FIELD]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(static::UNSUPPORTED_SORT_FIELD, implode(' ', $this->getErrorDetails($response)));
        $this->assertSame([], $this->getErrorCodes($response), 'New backend resources answer with status and detail only, without a Glue error code.');
    }

    public function testGivenKeyFilterWhenListingThenKeysAreMatchedBySubstringCaseInsensitively(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $fragment = uniqid('keyfilter', false);
        $matchingKey = $this->tester->haveGlossaryKey(null, sprintf('glossary-backend-api.%s.match', $fragment));
        $otherKey = $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl([
            static::QUERY_FILTER => [static::FILTER_KEY => strtoupper($fragment)],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $keys = $this->getResourceIds($response);
        $this->assertSame([$matchingKey], $keys);
        $this->assertNotContains($otherKey, $keys);
    }

    public function testGivenValueFilterWhenListingThenOnlyKeysWithActiveMatchingTranslationAreReturnedWithAllLocales(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        $uniqueValue = uniqid('Glossary value filter ', false);

        $matchingKey = $this->tester->haveGlossaryKey([$localeNames[0] => $uniqueValue, $localeNames[1] => $this->tester->generateTranslationValue()]);
        $deactivatedKey = $this->tester->haveGlossaryKey([$localeNames[0] => $uniqueValue]);
        $this->tester->haveDeactivatedTranslation($deactivatedKey, $localeNames[0]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl([
            static::QUERY_FILTER => [static::FILTER_VALUE => strtoupper($uniqueValue)],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$matchingKey], $this->getResourceIds($response), 'Deactivated translations must never match the value filter.');
        $this->assertCount(
            count($localeNames),
            $this->getFirstResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS],
            'A matched key still carries every configured locale, not only the matching one.',
        );
    }

    public function testGivenKeyAndValueFiltersWhenListingThenTheyCombineWithAnd(): void
    {
        // Arrange: two keys share the value, only one matches the key fragment.
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $uniqueValue = uniqid('Glossary combined filter ', false);
        $keyFragment = uniqid('combined', false);
        $matchingKey = $this->tester->haveGlossaryKey([$localeName => $uniqueValue], sprintf('glossary-backend-api.%s.match', $keyFragment));
        $otherKey = $this->tester->haveGlossaryKey([$localeName => $uniqueValue]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl([
            static::QUERY_FILTER => [static::FILTER_KEY => $keyFragment, static::FILTER_VALUE => $uniqueValue],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $keys = $this->getResourceIds($response);
        $this->assertSame([$matchingKey], $keys, 'Both filters must apply at once.');
        $this->assertNotContains($otherKey, $keys);
    }

    public function testGivenKeysWhenListingThenEveryKeyCarriesAllLocalesAndNoInternalFields(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        sort($localeNames);
        $key = $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl([
            static::QUERY_FILTER => [static::FILTER_KEY => $key],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $attributes = $this->getFirstResourceAttributes($response);
        $this->assertSame([static::ATTRIBUTE_KEY, static::ATTRIBUTE_TRANSLATIONS], array_keys($attributes), 'Only key and translations are exposed; no isActive, no database ids.');
        $this->assertSame($localeNames, array_column($attributes[static::ATTRIBUTE_TRANSLATIONS], static::ATTRIBUTE_LOCALE_NAME), 'One entry per configured locale, ordered by localeName.');
        foreach ($attributes[static::ATTRIBUTE_TRANSLATIONS] as $translation) {
            $this->assertSame([static::ATTRIBUTE_LOCALE_NAME, static::ATTRIBUTE_VALUE], array_keys($translation), 'A translation entry exposes only localeName and value.');
        }
    }

    public function testGivenKeyContainingSlashWhenListingThenCollectionIsReturnedWithItsSelfLink(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $key = $this->tester->haveGlossaryKey(null, sprintf('%s.n/a', $this->tester->generateGlossaryKey()));

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl([
            static::QUERY_FILTER => [static::FILTER_KEY => $key],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$key], $this->getResourceIds($response), 'A key with a slash must not break the IRI generation of the collection.');
    }

    public function testGivenNoAuthenticationWhenListingThenUnauthorizedIsReturned(): void
    {
        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenOperatorWithoutAclAccessWhenListingThenForbiddenIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
