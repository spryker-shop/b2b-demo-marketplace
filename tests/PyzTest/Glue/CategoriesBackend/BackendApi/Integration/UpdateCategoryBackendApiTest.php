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
 * `PATCH /categories/{categoryKey}` over a booted GLUE_BACKEND kernel: partial update, tree move
 * and the cycle guard.
 *

 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CategoriesBackend
 * @group BackendApi
 * @group Integration
 * @group UpdateCategoryBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateCategoryBackendApiTest extends AbstractCategoriesBackendApiTestCase
{
    protected const string MESSAGE_PARENT_CYCLE = 'must not be the category itself';

    protected const string MESSAGE_ROOT_STATUS_IMMUTABLE = 'Root status of a category cannot be changed';

    protected const string MESSAGE_LOCALE_NOT_AVAILABLE = 'is not available';

    protected const string UNKNOWN_LOCALE_NAME = 'xx_XX';

    protected const string UNKNOWN_STORE_NAME = 'PM_UNKNOWN_STORE';

    protected const string ATTRIBUTE_STORES = 'stores';

    protected const string ATTRIBUTE_IMAGE_SETS = 'imageSets';

    public function testGivenMetaTitleOnlyForLocaleWhenPatchCategoryThenLocalizedNameIsKeptAndMetaTitleIsUpdated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $categoryName = $this->tester->generateCategoryName();
        $categoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $this->tester->getRootCategoryKey(),
            'localizedAttributes' => [['localeName' => $localeName, 'name' => $categoryName, 'metaTitle' => 'Old Meta Title']],
        ])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [['localeName' => $localeName, 'metaTitle' => 'New Meta Title']]], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $localizedAttributes = $this->findLocalizedAttributesEntry($this->getSingleResourceAttributes($response)[static::ATTRIBUTE_LOCALIZED_ATTRIBUTES], $localeName);
        $this->assertSame($categoryName, $localizedAttributes['name'], 'PATCH must merge by localeName and keep the stored name.');
        $this->assertSame('New Meta Title', $localizedAttributes['metaTitle']);
    }

    public function testGivenNewLocalizedNameWhenPatchCategoryThenUrlIsRegenerated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $newCategoryName = $this->tester->generateCategoryName();
        $categoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $this->tester->getRootCategoryKey(),
            'localizedAttributes' => [['localeName' => $localeName, 'name' => $this->tester->generateCategoryName()]],
        ])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [['localeName' => $localeName, 'name' => $newCategoryName]]], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $localizedAttributes = $this->findLocalizedAttributesEntry($this->getSingleResourceAttributes($response)[static::ATTRIBUTE_LOCALIZED_ATTRIBUTES], $localeName);
        $this->assertSame($newCategoryName, $localizedAttributes['name']);
        $this->assertStringContainsString($newCategoryName, (string)$localizedAttributes['url'], 'A rename must regenerate the localized URL.');
    }

    public function testGivenNewParentCategoryKeyWhenPatchCategoryThenSubtreeIsMovedAndUrlsAreRegenerated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        [$categoryKey, $childCategoryKey, $newParentCategoryKey, $newParentName, $localeName] = $this->haveSubtreeAndNewParent();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_PARENT_CATEGORY_KEY => $newParentCategoryKey], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $updatedAttributes = $this->getSingleResourceAttributes($response);
        $this->assertSame($newParentCategoryKey, $updatedAttributes[static::ATTRIBUTE_PARENT_CATEGORY_KEY]);
        $movedUrl = (string)$this->findLocalizedAttributesEntry($updatedAttributes[static::ATTRIBUTE_LOCALIZED_ATTRIBUTES], $localeName)['url'];
        $this->assertStringContainsString($newParentName, $movedUrl, 'The moved category URL must contain the new parent path segment.');

        $childResponse = $this->handleApiRequest('GET', $this->tester->getCategoryUrl($childCategoryKey));
        $childUrl = (string)$this->findLocalizedAttributesEntry($this->getSingleResourceAttributes($childResponse)[static::ATTRIBUTE_LOCALIZED_ATTRIBUTES], $localeName)['url'];
        $this->assertStringContainsString($newParentName, $childUrl, 'A subtree move must regenerate the child URLs as well.');
    }

    public function testGivenNewPositionWhenPatchCategoryThenCategoryIsReorderedAmongSiblings(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $this->tester->getRootCategoryKey(),
            'position' => 1,
        ])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_POSITION => 42], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(42, $this->getSingleResourceAttributes($response)[static::ATTRIBUTE_POSITION]);
    }

    public function testGivenNewExtraParentCategoryKeysWhenPatchCategoryThenExtraParentsAreReplaced(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $oldExtraParentKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();
        $newExtraParentKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();
        $categoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'extraParentCategoryKeys' => [$oldExtraParentKey],
        ])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS => [$newExtraParentKey]], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$newExtraParentKey], $this->getSingleResourceAttributes($response)[static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS], 'PATCH must replace the extra parent list.');
    }

    /**
     * NOTE: covers the "clear with []" contract (send an empty array to remove all extra parents).
     */
    public function testGivenEmptyExtraParentCategoryKeysWhenPatchCategoryThenAllExtraParentsAreRemoved(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $extraParentKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();
        $categoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'extraParentCategoryKeys' => [$extraParentKey],
        ])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS => []], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getSingleResourceAttributes($response)[static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS], 'An explicit empty list must remove all extra parents.');
    }

    public function testGivenDescendantAsParentWhenPatchCategoryThenCycleIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $childCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $categoryKey])->getCategoryKeyOrFail();
        $grandchildCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $childCategoryKey])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_PARENT_CATEGORY_KEY => $grandchildCategoryKey], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_PARENT_CYCLE, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenRootStatusChangeWhenPatchCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($rootCategoryKey),
            $this->tester->buildCategoryRequestBody(['isRoot' => false], $rootCategoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_ROOT_STATUS_IMMUTABLE, implode(' ', $this->getErrorDetails($response)));
    }

    /**
     * Builds: root -> category -> child, plus a second potential parent under the root.
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
     */
    protected function haveSubtreeAndNewParent(): array
    {
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $localeName = $this->tester->getAvailableLocaleNames()[0];

        $categoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'localizedAttributes' => [['localeName' => $localeName, 'name' => $this->tester->generateCategoryName()]],
        ])->getCategoryKeyOrFail();

        $childCategoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $categoryKey,
            'localizedAttributes' => [['localeName' => $localeName, 'name' => $this->tester->generateCategoryName()]],
        ])->getCategoryKeyOrFail();

        $newParentName = $this->tester->generateCategoryName();
        $newParentCategoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'localizedAttributes' => [['localeName' => $localeName, 'name' => $newParentName]],
        ])->getCategoryKeyOrFail();

        return [$categoryKey, $childCategoryKey, $newParentCategoryKey, $newParentName, $localeName];
    }

    /**
     * @param array<array<string, mixed>> $localizedAttributes
     *
     * @return array<string, mixed>
     */
    protected function findLocalizedAttributesEntry(array $localizedAttributes, string $localeName): array
    {
        foreach ($localizedAttributes as $localizedAttributesEntry) {
            if (($localizedAttributesEntry['localeName'] ?? null) === $localeName) {
                return $localizedAttributesEntry;
            }
        }

        $this->fail(sprintf('No localized attributes entry found for locale "%s".', $localeName));
    }

    public function testGivenNewParentInDifferentCaseWhenPatchCategoryThenItIsMovedAndStoredKeyIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();
        $newParentCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_PARENT_CATEGORY_KEY => strtoupper($newParentCategoryKey)], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($newParentCategoryKey, $this->getSingleResourceAttributes($response)[static::ATTRIBUTE_PARENT_CATEGORY_KEY]);

        $readResponse = $this->handleApiRequest('GET', $this->tester->getCategoryUrl($categoryKey));
        $this->assertSame($newParentCategoryKey, $this->getSingleResourceAttributes($readResponse)[static::ATTRIBUTE_PARENT_CATEGORY_KEY]);
    }

    public function testGivenFlagOnlyPayloadWhenPatchCategoryThenOtherAttributesAreKept(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey, 'isActive' => true])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_IS_ACTIVE => false], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $attributes = $this->getSingleResourceAttributes($response);
        $this->assertFalse($attributes[static::ATTRIBUTE_IS_ACTIVE]);
        $this->assertSame($rootCategoryKey, $attributes[static::ATTRIBUTE_PARENT_CATEGORY_KEY]);
    }

    public function testGivenSeveralInvalidReferencesWhenPatchCategoryThenAllViolationsAreReportedTogether(): void
    {
        // Arrange: three independent violations in one payload prove validation is not fail-fast.
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([
                static::ATTRIBUTE_PARENT_CATEGORY_KEY => static::UNKNOWN_CATEGORY_KEY,
                static::ATTRIBUTE_STORES => [static::UNKNOWN_STORE_NAME],
                static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [['localeName' => static::UNKNOWN_LOCALE_NAME, 'name' => $this->tester->generateCategoryName()]],
            ], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(3, $this->getErrorDetails($response), 'Each violation must be its own `errors[]` entry.');
        $errorDetails = implode(' ', $this->getErrorDetails($response));
        $this->assertStringContainsString(static::UNKNOWN_CATEGORY_KEY, $errorDetails, 'The unknown parent must be reported.');
        $this->assertStringContainsString(static::UNKNOWN_STORE_NAME, $errorDetails, 'The unknown store must be reported in the same response.');
        $this->assertStringContainsString(static::MESSAGE_LOCALE_NOT_AVAILABLE, $errorDetails, 'The unknown locale must be reported in the same response.');
    }

    public function testGivenStoresWhenPatchCategoryThenStoreAssignmentPropagatesToMainChildren(): void
    {
        // Arrange: a store can only be added when the parent already carries it, so the parent is
        // seeded with the store explicitly instead of relying on the root's demo-data assignment.
        $this->tester->actingAsUser();
        $storeName = $this->tester->getExistingStoreNames()[0];
        $parentCategoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $this->tester->getRootCategoryKey(),
            static::ATTRIBUTE_STORES => [$storeName],
        ])->getCategoryKeyOrFail();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $parentCategoryKey])->getCategoryKeyOrFail();
        $childCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $categoryKey])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_STORES => [$storeName]], $categoryKey),
        );
        $childResponse = $this->handleApiRequest('GET', $this->tester->getCategoryUrl($childCategoryKey));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$storeName], $this->getSingleResourceAttributes($response)[static::ATTRIBUTE_STORES]);
        $this->assertRespondsWithStatus($childResponse, Response::HTTP_OK);
        $this->assertContains(
            $storeName,
            $this->getSingleResourceAttributes($childResponse)[static::ATTRIBUTE_STORES],
            'Store assignment must propagate to the main children, as in the Back Office.',
        );
    }

    public function testGivenImageSetsWhenPatchCategoryThenStoredImageSetsAreReplacedAndOmittedPropertyKeepsThem(): void
    {
        // Arrange: image sets are only writable through the API, so the category is created via POST.
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $attributes = $this->tester->buildValidCategoryAttributes([
            static::ATTRIBUTE_IMAGE_SETS => [$this->buildImageSet($localeName, 'default'), $this->buildImageSet($localeName, 'secondary')],
        ]);
        $categoryKey = $attributes[static::ATTRIBUTE_CATEGORY_KEY];
        $createResponse = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);
        $this->assertCount(2, $this->getSingleResourceAttributes($createResponse)[static::ATTRIBUTE_IMAGE_SETS]);

        // Act
        $replaceResponse = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_IMAGE_SETS => [$this->buildImageSet($localeName, 'thumbnail')]], $categoryKey),
        );
        $keepResponse = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_IS_ACTIVE => false], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($replaceResponse, Response::HTTP_OK);
        $replacedImageSets = $this->getSingleResourceAttributes($replaceResponse)[static::ATTRIBUTE_IMAGE_SETS];
        $this->assertCount(1, $replacedImageSets, 'PATCH must replace the stored image sets as a whole.');
        $this->assertSame('thumbnail', $replacedImageSets[0]['name']);

        $this->assertRespondsWithStatus($keepResponse, Response::HTTP_OK);
        $keptImageSets = $this->getSingleResourceAttributes($keepResponse)[static::ATTRIBUTE_IMAGE_SETS];
        $this->assertCount(1, $keptImageSets, 'An omitted imageSets property must keep the stored image sets.');
        $this->assertSame('thumbnail', $keptImageSets[0]['name']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildImageSet(string $localeName, string $name): array
    {
        return [
            'localeName' => $localeName,
            'name' => $name,
            'images' => [
                [
                    'externalUrlSmall' => sprintf('https://images.example.com/%s-s.jpg', $name),
                    'externalUrlLarge' => sprintf('https://images.example.com/%s-l.jpg', $name),
                    'sortOrder' => 0,
                ],
            ],
        ];
    }

    public function testGivenOwnKeyAsParentWhenPatchCategoryThenCycleIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl($categoryKey),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_PARENT_CATEGORY_KEY => strtoupper($categoryKey)], $categoryKey),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_PARENT_CYCLE, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenUnknownCategoryWhenPatchCategoryThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCategoryUrl(static::UNKNOWN_CATEGORY_KEY),
            $this->tester->buildCategoryRequestBody([static::ATTRIBUTE_IS_ACTIVE => false], static::UNKNOWN_CATEGORY_KEY),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }
}
