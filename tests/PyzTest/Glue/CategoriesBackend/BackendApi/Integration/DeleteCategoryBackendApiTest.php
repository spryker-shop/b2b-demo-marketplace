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
 * `DELETE /categories/{categoryKey}` over a booted GLUE_BACKEND kernel.
 *

 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CategoriesBackend
 * @group BackendApi
 * @group Integration
 * @group DeleteCategoryBackendApiTest
 * Add your own group annotations below this line
 */
class DeleteCategoryBackendApiTest extends AbstractCategoriesBackendApiTestCase
{
    protected const string MESSAGE_ROOT_CANNOT_BE_DELETED = 'root category cannot be deleted';

    public function testGivenCategoryWithChildAndExtraPlacementWhenDeleteThenItIsGoneAndTheChildIsReParented(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $extraPlacementParentKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();
        $parentCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();
        $categoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $parentCategoryKey,
            'extraParentCategoryKeys' => [$extraPlacementParentKey],
        ])->getCategoryKeyOrFail();
        $childCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $categoryKey])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCategoryUrl($categoryKey));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertRespondsWithStatus($this->handleApiRequest('GET', $this->tester->getCategoryUrl($categoryKey)), Response::HTTP_NOT_FOUND);

        $childResponse = $this->handleApiRequest('GET', $this->tester->getCategoryUrl($childCategoryKey));
        $this->assertRespondsWithStatus($childResponse, Response::HTTP_OK);
        $childAttributes = $this->getSingleResourceAttributes($childResponse);
        $this->assertSame($parentCategoryKey, $childAttributes[static::ATTRIBUTE_PARENT_CATEGORY_KEY], 'The child must be re-parented to the deleted category\'s parent.');
        $this->assertSame([], $childAttributes[static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS], 'The child must not inherit any extra placements.');
    }

    public function testGivenRootCategoryWhenDeleteThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCategoryUrl($rootCategoryKey));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_ROOT_CANNOT_BE_DELETED, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenUnknownCategoryWhenDeleteThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCategoryUrl(static::UNKNOWN_CATEGORY_KEY));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenDeleteThenUnauthorizedIsReturned(): void
    {
        // Arrange
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCategoryUrl($categoryKey));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
