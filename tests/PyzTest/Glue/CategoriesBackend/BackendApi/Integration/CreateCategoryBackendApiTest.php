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
 * `POST /categories` over a booted GLUE_BACKEND kernel: happy path, case-insensitive parent keys
 * and the domain validations that reject the request before anything is written.
 *

 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CategoriesBackend
 * @group BackendApi
 * @group Integration
 * @group CreateCategoryBackendApiTest
 * Add your own group annotations below this line
 */
class CreateCategoryBackendApiTest extends AbstractCategoriesBackendApiTestCase
{
    protected const string MESSAGE_PARENT_NOT_FOUND = 'does not exist';

    protected const string MESSAGE_CATEGORY_KEY_EXISTS = 'already exists';

    protected const string MESSAGE_MISSING_LOCALIZED_NAME = 'Missing localized name for locale(s):';

    protected const string MESSAGE_ROOT_ALREADY_EXISTS = 'root category already exists';

    protected const string MESSAGE_ROOT_WITH_PARENT = 'parentCategoryKey must be omitted when isRoot is true';

    protected const string MESSAGE_PARENT_REQUIRED_FOR_NON_ROOT = 'parentCategoryKey is required unless isRoot is true';

    protected const string MESSAGE_SIBLING_NAME_EXISTS = 'already exists on the same level';

    protected const string MESSAGE_VALIDATION_NOT_BLANK = 'This value should not be blank.';

    protected const string MESSAGE_LOCALE_NOT_AVAILABLE = 'is not available';

    protected const string UNKNOWN_TEMPLATE_NAME = 'pm-backend-api-no-such-template';

    protected const string UNKNOWN_STORE_NAME = 'PM_UNKNOWN_STORE';

    public function testGivenMinimalPayloadWhenCreateCategoryThenBackOfficeDefaultsAreApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $categoryName = $this->tester->generateCategoryName();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $attributes = $this->tester->buildValidCategoryAttributes([
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => array_map(
                fn (string $localeName): array => ['localeName' => $localeName, 'name' => $categoryName],
                $localeNames,
            ),
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $createdAttributes = $this->getSingleResourceAttributes($response);
        $this->assertFalse($createdAttributes[static::ATTRIBUTE_IS_ACTIVE], 'Default on POST must be isActive=false.');
        $this->assertTrue($createdAttributes['isInMenu'], 'Default on POST must be isInMenu=true.');
        $this->assertTrue($createdAttributes['isSearchable'], 'Default on POST must be isSearchable=true.');
        $this->assertTrue($createdAttributes['isClickable'], 'Default on POST must be isClickable=true.');
        foreach ($localeNames as $localeName) {
            $localizedAttributes = $this->findLocalizedAttributesEntry($createdAttributes[static::ATTRIBUTE_LOCALIZED_ATTRIBUTES], $localeName);
            $this->assertNotNull($localizedAttributes['url'], sprintf('A localized URL must be generated on create for locale "%s".', $localeName));
            $this->assertStringContainsString($categoryName, $localizedAttributes['url']);
        }
    }

    public function testGivenFullPayloadWithTwoLocalesWhenCreateCategoryThenAllAttributesArePersisted(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $storeName = $this->tester->getExistingStoreNames()[0];
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        // The extra parent needs a localized name per used locale — otherwise its placement URL
        // path collapses to the main node URL and the create is rejected as a URL collision (422).
        $extraParentCategoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'localizedAttributes' => [
                ['localeName' => $localeNames[0], 'name' => $this->tester->generateCategoryName()],
                ['localeName' => $localeNames[1], 'name' => $this->tester->generateCategoryName()],
            ],
        ])->getCategoryKeyOrFail();
        $attributes = $this->tester->buildValidCategoryAttributes([
            static::ATTRIBUTE_IS_ACTIVE => true,
            static::ATTRIBUTE_POSITION => 7,
            static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS => [$extraParentCategoryKey],
            'stores' => [$storeName],
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [
                ['localeName' => $localeNames[0], 'name' => $this->tester->generateCategoryName(), 'metaTitle' => 'Meta 0'],
                ['localeName' => $localeNames[1], 'name' => $this->tester->generateCategoryName(), 'metaTitle' => 'Meta 1'],
            ],
            'imageSets' => [
                [
                    'localeName' => $localeNames[0],
                    'name' => 'default',
                    'images' => [
                        ['externalUrlSmall' => 'https://images.example.com/pm-s.jpg', 'externalUrlLarge' => 'https://images.example.com/pm-l.jpg', 'sortOrder' => 0],
                    ],
                ],
            ],
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $createdAttributes = $this->getSingleResourceAttributes($response);
        $this->assertTrue($createdAttributes[static::ATTRIBUTE_IS_ACTIVE]);
        $this->assertSame(7, $createdAttributes[static::ATTRIBUTE_POSITION]);
        $this->assertSame([$extraParentCategoryKey], $createdAttributes[static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS]);
        $this->assertSame([$storeName], $createdAttributes['stores']);
        foreach ($localeNames as $localeName) {
            $localizedAttributes = $this->findLocalizedAttributesEntry($createdAttributes[static::ATTRIBUTE_LOCALIZED_ATTRIBUTES], $localeName);
            $this->assertNotNull($localizedAttributes['url'], sprintf('A URL must be generated for locale "%s".', $localeName));
        }
        $this->assertCount(1, $createdAttributes['imageSets']);
        $imageSet = $createdAttributes['imageSets'][0];
        $this->assertSame('default', $imageSet['name']);
        $this->assertSame('https://images.example.com/pm-s.jpg', $imageSet['images'][0]['externalUrlSmall']);
    }

    /**
     * KNOWN BUG (found 2026-09-02): the created category always ends up with the default template
     * ("Catalog (default)") regardless of the templateName sent. `CategoryRequestMapper` sets only
     * `fkCategoryTemplate`, but `CategoryCreator::executeCreateCategoryCollectionTransaction()`
     * overwrites both `categoryTemplate` and `fkCategoryTemplate` with the default whenever the
     * nested `categoryTemplate` transfer is not set. This test asserts the documented contract
     * (trimmed template name is matched and persisted) — do not delete it while the fix is pending.
     */
    public function testGivenTemplateNameWithSurroundingWhitespaceWhenCreateCategoryThenTemplateIsMatchedTrimmed(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $templateName = $this->tester->haveCategoryTemplate();
        $attributes = $this->tester->buildValidCategoryAttributes(['templateName' => sprintf('  %s  ', $templateName)]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertSame($templateName, $this->getSingleResourceAttributes($response)['templateName']);
    }

    public function testGivenRootCategoryExistsWhenCreateSecondRootThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->getRootCategoryKey();
        $attributes = $this->tester->buildValidCategoryAttributes([static::ATTRIBUTE_PARENT_CATEGORY_KEY => null, 'isRoot' => true]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_ROOT_ALREADY_EXISTS, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenIsRootTogetherWithParentCategoryKeyWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes(['isRoot' => true]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_ROOT_WITH_PARENT, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenMissingParentCategoryKeyForNonRootWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes([static::ATTRIBUTE_PARENT_CATEGORY_KEY => null]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_PARENT_REQUIRED_FOR_NON_ROOT, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenTemplateNameInDifferentCaseWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $templateName = $this->tester->haveCategoryTemplate();
        $attributes = $this->tester->buildValidCategoryAttributes(['templateName' => strtoupper($templateName)]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_PARENT_NOT_FOUND, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenUnknownExtraParentWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes([static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS => [static::UNKNOWN_CATEGORY_KEY]]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_PARENT_NOT_FOUND, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenSiblingWithSameLocalizedNameWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $siblingName = $this->tester->generateCategoryName();
        $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'localizedAttributes' => [['localeName' => $localeName, 'name' => $siblingName]],
        ]);
        $localizedAttributes = array_map(
            fn (string $name): array => ['localeName' => $name, 'name' => $this->tester->generateCategoryName()],
            $this->tester->getAvailableLocaleNames(),
        );
        $localizedAttributes[0]['name'] = $siblingName;
        $attributes = $this->tester->buildValidCategoryAttributes([
            static::ATTRIBUTE_PARENT_CATEGORY_KEY => $rootCategoryKey,
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => $localizedAttributes,
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_SIBLING_NAME_EXISTS, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenMissingLocalizedAttributesWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes([static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => []]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = implode(' ', $this->getErrorDetails($response));
        $this->assertStringContainsString(static::ATTRIBUTE_LOCALIZED_ATTRIBUTES, $errorDetails);
        $this->assertStringContainsString(static::MESSAGE_VALIDATION_NOT_BLANK, $errorDetails);
    }

    public function testGivenAllLocalesButOneEmptyNameWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        $localizedAttributes = array_map(
            fn (string $localeName): array => ['localeName' => $localeName, 'name' => $this->tester->generateCategoryName()],
            $localeNames,
        );
        $localizedAttributes[1]['name'] = '';
        $attributes = $this->tester->buildValidCategoryAttributes([static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => $localizedAttributes]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert: an empty (as opposed to absent) name is rejected by the framework's own
        // property-level validation before the domain's missing-locale check ever runs, so the
        // detail references the property path by array index, not the locale name.
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = implode(' ', $this->getErrorDetails($response));
        $this->assertStringContainsString('localizedAttributes.1.name', $errorDetails);
        $this->assertStringContainsString(static::MESSAGE_VALIDATION_NOT_BLANK, $errorDetails);
    }

    public function testGivenUnknownLocaleWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes([
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [['localeName' => 'xx_XX', 'name' => $this->tester->generateCategoryName()]],
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = implode(' ', $this->getErrorDetails($response));
        $this->assertStringContainsString('xx_XX', $errorDetails);
        $this->assertStringContainsString(static::MESSAGE_LOCALE_NOT_AVAILABLE, $errorDetails);
    }

    public function testGivenUnknownStoreWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes(['stores' => ['PM_UNKNOWN_STORE']]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = implode(' ', $this->getErrorDetails($response));
        $this->assertStringContainsString('PM_UNKNOWN_STORE', $errorDetails);
        $this->assertStringContainsString(static::MESSAGE_PARENT_NOT_FOUND, $errorDetails);
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

    public function testGivenValidPayloadWhenCreateCategoryThenItIsCreatedAndReadable(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes([static::ATTRIBUTE_IS_ACTIVE => true, static::ATTRIBUTE_POSITION => 7]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $createdAttributes = $this->getSingleResourceAttributes($response);
        $this->assertSame($attributes[static::ATTRIBUTE_CATEGORY_KEY], $createdAttributes[static::ATTRIBUTE_CATEGORY_KEY]);
        $this->assertSame($attributes[static::ATTRIBUTE_PARENT_CATEGORY_KEY], $createdAttributes[static::ATTRIBUTE_PARENT_CATEGORY_KEY]);
        $this->assertTrue($createdAttributes[static::ATTRIBUTE_IS_ACTIVE]);
        $this->assertSame(7, $createdAttributes[static::ATTRIBUTE_POSITION]);

        $readResponse = $this->handleApiRequest('GET', $this->tester->getCategoryUrl($attributes[static::ATTRIBUTE_CATEGORY_KEY]));
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);
    }

    public function testGivenParentKeysInDifferentCaseWhenCreateCategoryThenStoredKeysAreReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $rootCategoryKey = $this->tester->getRootCategoryKey();
        $parentCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $rootCategoryKey])->getCategoryKeyOrFail();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $extraParentCategoryKey = $this->tester->haveCategoryViaFacade([
            'parentCategoryKey' => $rootCategoryKey,
            'localizedAttributes' => array_map(fn (string $localeName): array => ['localeName' => $localeName, 'name' => $this->tester->generateCategoryName()], $localeNames),
        ])->getCategoryKeyOrFail();
        $attributes = $this->tester->buildValidCategoryAttributes([
            static::ATTRIBUTE_PARENT_CATEGORY_KEY => strtoupper($parentCategoryKey),
            static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS => [strtoupper($extraParentCategoryKey)],
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $createdAttributes = $this->getSingleResourceAttributes($response);
        $this->assertSame($parentCategoryKey, $createdAttributes[static::ATTRIBUTE_PARENT_CATEGORY_KEY]);
        $this->assertSame([$extraParentCategoryKey], $createdAttributes[static::ATTRIBUTE_EXTRA_PARENT_CATEGORY_KEYS]);
    }

    public function testGivenSeveralInvalidReferencesWhenCreateCategoryThenAllViolationsAreReportedTogether(): void
    {
        // Arrange: three independent violations in one payload prove validation is not fail-fast.
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes([
            static::ATTRIBUTE_PARENT_CATEGORY_KEY => static::UNKNOWN_CATEGORY_KEY,
            'templateName' => static::UNKNOWN_TEMPLATE_NAME,
            'stores' => [static::UNKNOWN_STORE_NAME],
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(3, $this->getErrorDetails($response), 'Each violation must be its own `errors[]` entry.');
        $errorDetails = implode(' ', $this->getErrorDetails($response));
        $this->assertStringContainsString(static::UNKNOWN_CATEGORY_KEY, $errorDetails, 'The unknown parent must be reported.');
        $this->assertStringContainsString(static::UNKNOWN_TEMPLATE_NAME, $errorDetails, 'The unknown template must be reported in the same response.');
        $this->assertStringContainsString(static::UNKNOWN_STORE_NAME, $errorDetails, 'The unknown store must be reported in the same response.');
    }

    public function testGivenUnknownParentWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCategoryAttributes([static::ATTRIBUTE_PARENT_CATEGORY_KEY => static::UNKNOWN_CATEGORY_KEY]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_PARENT_NOT_FOUND, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenExistingCategoryKeyWhenCreateCategoryThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $existingCategoryKey = $this->tester->haveCategoryViaFacade(['parentCategoryKey' => $this->tester->getRootCategoryKey()])->getCategoryKeyOrFail();
        $attributes = $this->tester->buildValidCategoryAttributes([static::ATTRIBUTE_CATEGORY_KEY => $existingCategoryKey]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_CATEGORY_KEY_EXISTS, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenSingleLocalePayloadWhenCreateCategoryThenMissingLocalesAreRejected(): void
    {
        // Arrange: the Back Office create form requires a name for EVERY configured locale — the API mirrors that.
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        $attributes = $this->tester->buildValidCategoryAttributes([
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [
                ['localeName' => $localeNames[0], 'name' => $this->tester->generateCategoryName()],
            ],
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = implode(' ', $this->getErrorDetails($response));
        $this->assertStringContainsString(static::MESSAGE_MISSING_LOCALIZED_NAME, $errorDetails);
        foreach (array_slice($localeNames, 1) as $missingLocaleName) {
            $this->assertStringContainsString($missingLocaleName, $errorDetails);
        }
    }

    public function testGivenNoAuthenticationWhenCreateCategoryThenUnauthorizedIsReturned(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidCategoryAttributes();

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getCategoryCollectionUrl(), $this->tester->buildCategoryRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
