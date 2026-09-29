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
 * `POST /glossary-keys` over a booted GLUE_BACKEND kernel: happy path with all or a subset of the
 * locales, and the 422 guards (duplicate key, unknown / duplicate locale, empty or null value).
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group GlossaryBackend
 * @group BackendApi
 * @group Integration
 * @group CreateGlossaryKeyBackendApiTest
 * Add your own group annotations below this line
 */
class CreateGlossaryKeyBackendApiTest extends AbstractGlossaryBackendApiTestCase
{
    protected const string MESSAGE_KEY_EXISTS = 'already exists';

    protected const string MESSAGE_LOCALE_NOT_AVAILABLE = 'is not available';

    protected const string MESSAGE_LOCALE_DUPLICATE = 'more than once';

    public function testGivenTranslationsForAllLocalesWhenCreatingThenKeyIsCreatedAndReadable(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidGlossaryKeyAttributes();
        $key = $attributes[static::ATTRIBUTE_KEY];
        $expectedValues = $this->indexTranslationValuesByLocaleName($attributes[static::ATTRIBUTE_TRANSLATIONS]);
        ksort($expectedValues);

        // Act
        $createResponse = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));
        $readResponse = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyUrl($key));

        // Assert
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);
        $createdAttributes = $this->getResourceAttributes($createResponse);
        $this->assertSame($key, $createdAttributes[static::ATTRIBUTE_KEY]);
        $this->assertSame($expectedValues, $this->indexTranslationValuesByLocaleName($createdAttributes[static::ATTRIBUTE_TRANSLATIONS]));

        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);
        $this->assertSame($expectedValues, $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($readResponse)[static::ATTRIBUTE_TRANSLATIONS]));
    }

    public function testGivenTranslationsForSubsetOfLocalesWhenCreatingThenMissingLocalesAreReportedWithNullValue(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        [$translatedLocaleName, $untranslatedLocaleName] = $localeNames;
        $attributes = $this->tester->buildValidGlossaryKeyAttributes([
            static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$translatedLocaleName => $this->tester->generateTranslationValue()]),
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $valuesIndexedByLocaleName = $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS]);
        $this->assertNotNull($valuesIndexedByLocaleName[$translatedLocaleName]);
        $this->assertNull($valuesIndexedByLocaleName[$untranslatedLocaleName]);
        $this->assertNull($this->tester->findStoredTranslation($attributes[static::ATTRIBUTE_KEY], $untranslatedLocaleName), 'No row is written for a locale that was not sent.');
    }

    public function testGivenNoTranslationsWhenCreatingThenKeyIsCreatedWithNullValueForEveryLocale(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidGlossaryKeyAttributes([static::ATTRIBUTE_TRANSLATIONS => []]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $translations = $this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS];
        $this->assertCount(count($this->tester->getAvailableLocaleNames()), $translations);
        $this->assertSame([null], array_values(array_unique(array_column($translations, static::ATTRIBUTE_VALUE))));
    }

    public function testGivenExistingKeyWhenCreatingThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $existingKey = $this->tester->haveGlossaryKey();
        $attributes = $this->tester->buildValidGlossaryKeyAttributes([static::ATTRIBUTE_KEY => $existingKey]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_KEY_EXISTS, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenUnknownLocaleWhenCreatingThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidGlossaryKeyAttributes([
            static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([static::UNKNOWN_LOCALE_NAME => $this->tester->generateTranslationValue()]),
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = implode(' ', $this->getErrorDetails($response));
        $this->assertStringContainsString(static::UNKNOWN_LOCALE_NAME, $errorDetails);
        $this->assertStringContainsString(static::MESSAGE_LOCALE_NOT_AVAILABLE, $errorDetails);
        $this->assertNull($this->tester->findStoredTranslation($attributes[static::ATTRIBUTE_KEY], $this->tester->getAvailableLocaleNames()[0]), 'Nothing is written when the payload is rejected.');
    }

    public function testGivenSameLocaleTwiceWhenCreatingThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $attributes = $this->tester->buildValidGlossaryKeyAttributes([
            static::ATTRIBUTE_TRANSLATIONS => [
                [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_VALUE => $this->tester->generateTranslationValue()],
                [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_VALUE => $this->tester->generateTranslationValue()],
            ],
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_LOCALE_DUPLICATE, implode(' ', $this->getErrorDetails($response)));
    }

    /**
     * @dataProvider rejectedTranslationValueDataProvider
     */
    public function testGivenEmptyOrNullTranslationValueWhenCreatingThenItIsRejected(?string $value): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidGlossaryKeyAttributes([
            static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$this->tester->getAvailableLocaleNames()[0] => $value]),
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertNull($this->tester->findStoredTranslation($attributes[static::ATTRIBUTE_KEY], $this->tester->getAvailableLocaleNames()[0]));
    }

    /**
     * @return array<string, array<string|null>>
     */
    public static function rejectedTranslationValueDataProvider(): array
    {
        return [
            'empty string' => [''],
            'blank string' => [' '],
            'null is a removal marker and has no meaning on create' => [null],
        ];
    }

    public function testGivenSeveralInvalidReferencesWhenCreatingThenAllViolationsAreReportedTogether(): void
    {
        // Arrange: an existing key plus two bad locale entries prove validation is not fail-fast.
        $this->tester->actingAsUser();
        $existingKey = $this->tester->haveGlossaryKey();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $attributes = $this->tester->buildValidGlossaryKeyAttributes([
            static::ATTRIBUTE_KEY => $existingKey,
            static::ATTRIBUTE_TRANSLATIONS => [
                [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_VALUE => $this->tester->generateTranslationValue()],
                [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_VALUE => $this->tester->generateTranslationValue()],
                [static::ATTRIBUTE_LOCALE_NAME => static::UNKNOWN_LOCALE_NAME, static::ATTRIBUTE_VALUE => $this->tester->generateTranslationValue()],
            ],
        ]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = $this->getErrorDetails($response);
        $this->assertCount(3, $errorDetails, 'Each violation must be its own `errors[]` entry.');
        $joinedErrorDetails = implode(' ', $errorDetails);
        $this->assertStringContainsString(static::MESSAGE_KEY_EXISTS, $joinedErrorDetails);
        $this->assertStringContainsString(static::MESSAGE_LOCALE_DUPLICATE, $joinedErrorDetails);
        $this->assertStringContainsString(static::UNKNOWN_LOCALE_NAME, $joinedErrorDetails);
    }

    public function testGivenExistingKeyInDifferentCaseWhenCreatingThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $existingKey = $this->tester->haveGlossaryKey();
        $attributes = $this->tester->buildValidGlossaryKeyAttributes([static::ATTRIBUTE_KEY => strtoupper($existingKey)]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_KEY_EXISTS, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenMissingKeyWhenCreatingThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidGlossaryKeyAttributes();
        unset($attributes[static::ATTRIBUTE_KEY]);

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenOperatorWithoutAclAccessWhenCreatingThenForbiddenIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();
        $attributes = $this->tester->buildValidGlossaryKeyAttributes();

        // Act
        $response = $this->handleApiRequest('POST', $this->tester->getGlossaryKeyCollectionUrl(), $this->tester->buildGlossaryKeyRequestBody($attributes));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
        $this->assertNull($this->tester->findStoredTranslation($attributes[static::ATTRIBUTE_KEY], $this->tester->getAvailableLocaleNames()[0]), 'Nothing is written without ACL access.');
    }

    public function testGivenNoAuthenticationWhenCreatingThenUnauthorizedIsReturned(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getGlossaryKeyCollectionUrl(),
            $this->tester->buildGlossaryKeyRequestBody($this->tester->buildValidGlossaryKeyAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
