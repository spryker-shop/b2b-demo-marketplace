<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CategoriesBackend;

use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * PEM-specific arrangement for the backend integration suite: the JSON:API envelope lives in
 * {@see JsonApiResponseAssertionsTrait}; what remains here is the category domain, the backend
 * pagination contract (top-level `meta.pagination`) and this suite's actor.
 */
abstract class AbstractCategoriesBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string UNKNOWN_CATEGORY_KEY = 'pm-backend-api-does-not-exist';

    protected const string UNKNOWN_SKU = 'pm-backend-api-no-such-sku';

    protected const string ATTRIBUTE_CATEGORY_KEY = 'categoryKey';

    protected const string ATTRIBUTE_PARENT_CATEGORY_KEY = 'parentCategoryKey';

    protected const string ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS = 'extraParentCategoryKeys';

    protected const string ATTRIBUTE_LOCALIZED_ATTRIBUTES = 'localizedAttributes';

    protected const string ATTRIBUTE_IS_ACTIVE = 'isActive';

    protected const string ATTRIBUTE_POSITION = 'position';

    protected const string ATTRIBUTE_SKU = 'sku';

    protected const string ATTRIBUTE_SKUS = 'skus';

    protected const string ATTRIBUTE_ALL = 'all';

    protected const string QUERY_SORT = 'sort';

    protected const string QUERY_PARENT_CATEGORY_KEY = 'parentCategoryKey';

    protected CategoriesBackendApiIntegrationTester $tester;

    /**
     * Provisions a category through the API as the acting operator and returns its categoryKey.
     *
     * @param array<string, mixed> $attributes
     */
    protected function haveCategoryViaApi(array $attributes = []): string
    {
        $attributes = $this->tester->buildValidCategoryAttributes($attributes);
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCategoryCollectionUrl(),
            $this->tester->buildCategoryRequestBody($attributes),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the category under test: %s', (string)$response->getContent()),
        );

        return (string)$attributes[static::ATTRIBUTE_CATEGORY_KEY];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSingleResourceAttributes(Response $response): array
    {
        return (array)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ATTRIBUTES] ?? []);
    }

    /**
     * @param array<string, mixed> $localizedAttributes
     */
    protected function findLocalizedName(array $localizedAttributes, string $localeName): ?string
    {
        foreach ($localizedAttributes as $localizedAttribute) {
            if (($localizedAttribute['localeName'] ?? null) === $localeName) {
                return $localizedAttribute['name'] ?? null;
            }
        }

        return null;
    }
}
