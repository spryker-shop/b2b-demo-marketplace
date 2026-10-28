<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\GlossaryBackend;

use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared plumbing of the `glossary-keys` Backend API integration tests: JSON:API decoding comes
 * from {@see JsonApiResponseAssertionsTrait}; what remains here is the glossary vocabulary and the
 * top-level pagination accessors.
 */
abstract class AbstractGlossaryBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string UNKNOWN_GLOSSARY_KEY = 'glossary-backend-api.does-not-exist';

    protected const string UNKNOWN_LOCALE_NAME = 'xx_XX';

    protected const string JSON_API_KEY_META = 'meta';

    protected const string META_PAGINATION = 'pagination';

    protected const string PAGINATION_KEY_NUM_FOUND = 'numFound';

    protected const string PAGINATION_KEY_CURRENT_PAGE = 'currentPage';

    protected const string PAGINATION_KEY_MAX_PAGE = 'maxPage';

    protected const string PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE = 'currentItemsPerPage';

    protected const string LINK_FIRST = 'first';

    protected const string LINK_LAST = 'last';

    protected const string LINK_PREV = 'prev';

    protected const string LINK_NEXT = 'next';

    protected const string ATTRIBUTE_KEY = 'key';

    protected const string ATTRIBUTE_TRANSLATIONS = 'translations';

    protected const string ATTRIBUTE_LOCALE_NAME = 'localeName';

    protected const string ATTRIBUTE_VALUE = 'value';

    protected const string QUERY_PAGE = 'page';

    protected const string QUERY_LIMIT = 'limit';

    protected const string QUERY_OFFSET = 'offset';

    protected const string QUERY_SORT = 'sort';

    protected const string QUERY_FILTER = 'filter';

    protected const string FILTER_KEY = 'glossary-keys.key';

    protected const string FILTER_VALUE = 'glossary-keys.value';

    protected GlossaryBackendApiIntegrationTester $tester;

    /**
     * @return array<string, int>
     */
    protected function getMetaPagination(Response $response): array
    {
        $payload = $this->decodeJsonApi($response);

        $this->assertArrayHasKey(static::JSON_API_KEY_META, $payload, 'The collection document must carry a top-level `meta` member.');
        $this->assertArrayHasKey(static::META_PAGINATION, $payload[static::JSON_API_KEY_META], 'Pagination must be reported as `meta.pagination`.');

        return $payload[static::JSON_API_KEY_META][static::META_PAGINATION];
    }

    /**
     * @return array<string, string>
     */
    protected function getLinks(Response $response): array
    {
        return $this->decodeJsonApi($response)[static::JSON_API_KEY_LINKS] ?? [];
    }

    /**
     * @param array<array<string, mixed>> $translations
     *
     * @return array<string, string|null> Translation values indexed by locale name.
     */
    protected function indexTranslationValuesByLocaleName(array $translations): array
    {
        $valuesIndexedByLocaleName = [];
        foreach ($translations as $translation) {
            $valuesIndexedByLocaleName[$translation[static::ATTRIBUTE_LOCALE_NAME]] = $translation[static::ATTRIBUTE_VALUE];
        }

        return $valuesIndexedByLocaleName;
    }
}
