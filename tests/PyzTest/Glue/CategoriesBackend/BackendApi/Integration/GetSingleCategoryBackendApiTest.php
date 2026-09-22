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
 * `GET /categories/{categoryKey}` over a booted GLUE_BACKEND kernel.
 *

 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CategoriesBackend
 * @group BackendApi
 * @group Integration
 * @group GetSingleCategoryBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleCategoryBackendApiTest extends AbstractCategoriesBackendApiTestCase
{
    public function testGivenExistingCategoryWhenGetByKeyThenTheResourceIsReturnedWithoutPagination(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $categoryName = $this->tester->generateCategoryName();
        $categoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'localizedAttributes' => [['localeName' => $localeName, 'name' => $categoryName]],
        ])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryUrl($categoryKey));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $attributes = $this->getSingleResourceAttributes($response);
        $this->assertSame($categoryKey, $attributes[static::ATTRIBUTE_CATEGORY_KEY]);
        $this->assertSame($rootCategoryKey, $attributes[static::ATTRIBUTE_PARENT_CATEGORY_KEY]);
        $this->assertSame($categoryName, $this->findLocalizedName($attributes[static::ATTRIBUTE_LOCALIZED_ATTRIBUTES], $localeName));
        $this->assertArrayNotHasKey(static::META_PAGINATION, $attributes);
        $this->assertArrayNotHasKey(static::JSON_API_KEY_META, $this->decodeJsonApi($response));
    }

    public function testGivenCategoryKeyInDifferentCaseWhenGetByKeyThenTheStoredCategoryIsReturned(): void
    {
        // Arrange: categoryKey lookups are case-insensitive on the project database collation.
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $this->assertNotSame(strtoupper($categoryKey), $categoryKey);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryUrl(strtoupper($categoryKey)));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($categoryKey, $this->getSingleResourceAttributes($response)[static::ATTRIBUTE_CATEGORY_KEY]);
    }

    public function testGivenUnknownCategoryKeyWhenGetByKeyThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryUrl(static::UNKNOWN_CATEGORY_KEY));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenGetByKeyThenUnauthorizedIsReturned(): void
    {
        // Arrange
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCategoryUrl($categoryKey));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
