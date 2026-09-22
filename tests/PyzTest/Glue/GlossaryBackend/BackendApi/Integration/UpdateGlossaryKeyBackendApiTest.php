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
 * `PATCH /glossary-keys/{key}` over a booted GLUE_BACKEND kernel: merge by localeName, removal via
 * `value: null` (row kept, deactivated), reactivation, and the 422 / 404 guards.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group GlossaryBackend
 * @group BackendApi
 * @group Integration
 * @group UpdateGlossaryKeyBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateGlossaryKeyBackendApiTest extends AbstractGlossaryBackendApiTestCase
{
    protected const string MESSAGE_KEY_IMMUTABLE = 'key is immutable';

    protected const string MESSAGE_LOCALE_NOT_AVAILABLE = 'is not available';

    public function testGivenOneLocaleWhenPatchingThenOnlyThatTranslationChangesAndOthersAreKept(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        $storedValues = $this->tester->buildValuesForAllLocales();
        $key = $this->tester->haveGlossaryKey($storedValues);
        $newValue = $this->tester->generateTranslationValue();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$localeNames[0] => $newValue]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $valuesIndexedByLocaleName = $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS]);
        $this->assertSame($newValue, $valuesIndexedByLocaleName[$localeNames[0]]);
        $this->assertSame($storedValues[$localeNames[1]], $valuesIndexedByLocaleName[$localeNames[1]], 'An omitted locale keeps its stored translation.');
    }

    public function testGivenSeveralLocalesWhenPatchingThenAllOfThemAreApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $key = $this->tester->haveGlossaryKey();
        $newValues = $this->tester->buildValuesForAllLocales();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries($newValues)], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        ksort($newValues);
        $this->assertSame($newValues, $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS]));
    }

    public function testGivenMissingLocaleWhenPatchingThenTranslationIsCreated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        [$translatedLocaleName, $missingLocaleName] = $localeNames;
        $key = $this->tester->haveGlossaryKey([$translatedLocaleName => $this->tester->generateTranslationValue()]);
        $newValue = $this->tester->generateTranslationValue();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$missingLocaleName => $newValue]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($newValue, $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS])[$missingLocaleName]);
        $storedTranslation = $this->tester->findStoredTranslation($key, $missingLocaleName);
        $this->assertNotNull($storedTranslation);
        $this->assertTrue($storedTranslation->getIsActive());
    }

    public function testGivenNullValueWhenPatchingThenTranslationIsDeactivatedButTheRowIsKept(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        $storedValues = $this->tester->buildValuesForAllLocales();
        $key = $this->tester->haveGlossaryKey($storedValues);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$localeNames[0] => null]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $valuesIndexedByLocaleName = $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS]);
        $this->assertArrayHasKey($localeNames[0], $valuesIndexedByLocaleName, 'The removed locale is still listed.');
        $this->assertNull($valuesIndexedByLocaleName[$localeNames[0]]);
        $this->assertSame($storedValues[$localeNames[1]], $valuesIndexedByLocaleName[$localeNames[1]]);

        $storedTranslation = $this->tester->findStoredTranslation($key, $localeNames[0]);
        $this->assertNotNull($storedTranslation, 'The row is kept, as when the Back Office clears the field.');
        $this->assertFalse($storedTranslation->getIsActive());
        $this->assertSame('', $storedTranslation->getValue(), 'The stored text is cleared, as when the Back Office clears the field.');
    }

    public function testGivenNullValueForLocaleWithoutTranslationWhenPatchingThenRequestIsIdempotent(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        [$translatedLocaleName, $missingLocaleName] = $localeNames;
        $key = $this->tester->haveGlossaryKey([$translatedLocaleName => $this->tester->generateTranslationValue()]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$missingLocaleName => null]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertNull($this->tester->findStoredTranslation($key, $missingLocaleName), 'Removing a translation that never existed must not create a row.');
    }

    public function testGivenValueForDeactivatedTranslationWhenPatchingThenItIsReactivated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $key = $this->tester->haveGlossaryKey();
        $this->tester->haveDeactivatedTranslation($key, $localeName);
        $newValue = $this->tester->generateTranslationValue();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$localeName => $newValue]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($newValue, $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS])[$localeName]);
        $storedTranslation = $this->tester->findStoredTranslation($key, $localeName);
        $this->assertNotNull($storedTranslation);
        $this->assertTrue($storedTranslation->getIsActive(), 'Writing a value to a deactivated translation reactivates it.');
        $this->assertSame($newValue, $storedTranslation->getValue());
    }

    public function testGivenEmptyStringValueWhenPatchingThenItIsRejectedAndNothingChanges(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $storedValues = $this->tester->buildValuesForAllLocales();
        $key = $this->tester->haveGlossaryKey($storedValues);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$localeName => '']),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $storedTranslation = $this->tester->findStoredTranslation($key, $localeName);
        $this->assertNotNull($storedTranslation);
        $this->assertSame($storedValues[$localeName], $storedTranslation->getValue());
        $this->assertTrue($storedTranslation->getIsActive());
    }

    public function testGivenUnknownLocaleWhenPatchingThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $key = $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([static::UNKNOWN_LOCALE_NAME => $this->tester->generateTranslationValue()]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_LOCALE_NOT_AVAILABLE, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenDifferentKeyInBodyWhenPatchingThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $key = $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([static::ATTRIBUTE_KEY => $this->tester->generateGlossaryKey()], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(static::MESSAGE_KEY_IMMUTABLE, implode(' ', $this->getErrorDetails($response)));
    }

    public function testGivenUnknownKeyWhenPatchingThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl(static::UNKNOWN_GLOSSARY_KEY),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$this->tester->getAvailableLocaleNames()[0] => $this->tester->generateTranslationValue()]),
            ], static::UNKNOWN_GLOSSARY_KEY),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenValueAndNullForDifferentLocalesInOneRequestWhenPatchingThenBothAreApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        [$overwrittenLocaleName, $removedLocaleName] = $localeNames;
        $key = $this->tester->haveGlossaryKey();
        $newValue = $this->tester->generateTranslationValue();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$overwrittenLocaleName => $newValue, $removedLocaleName => null]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $valuesIndexedByLocaleName = $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS]);
        $this->assertSame($newValue, $valuesIndexedByLocaleName[$overwrittenLocaleName]);
        $this->assertNull($valuesIndexedByLocaleName[$removedLocaleName]);
        $this->assertFalse($this->tester->findStoredTranslation($key, $removedLocaleName)?->getIsActive(), 'The removed locale is deactivated in the same request.');
    }

    public function testGivenSeveralInvalidEntriesWhenPatchingThenAllViolationsAreReportedTogether(): void
    {
        // Arrange: unknown locale, empty value and a duplicated locale in one payload prove validation is not fail-fast.
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $storedValues = $this->tester->buildValuesForAllLocales();
        $key = $this->tester->haveGlossaryKey($storedValues);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => [
                    [static::ATTRIBUTE_LOCALE_NAME => static::UNKNOWN_LOCALE_NAME, static::ATTRIBUTE_VALUE => $this->tester->generateTranslationValue()],
                    [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_VALUE => ' '],
                    [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_VALUE => $this->tester->generateTranslationValue()],
                ],
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(3, $this->getErrorDetails($response), 'Each violation must be its own `errors[]` entry.');
        $this->assertSame($storedValues[$localeName], $this->tester->findStoredTranslation($key, $localeName)?->getValue(), 'Nothing is written when the payload is rejected.');
    }

    public function testGivenOperatorWithoutAclAccessWhenPatchingThenForbiddenIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $storedValues = $this->tester->buildValuesForAllLocales();
        $key = $this->tester->haveGlossaryKey($storedValues);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$localeName => $this->tester->generateTranslationValue()]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
        $this->assertSame($storedValues[$localeName], $this->tester->findStoredTranslation($key, $localeName)?->getValue(), 'Nothing is written without ACL access.');
    }

    public function testGivenNoAuthenticationWhenPatchingThenUnauthorizedIsReturned(): void
    {
        // Arrange
        $key = $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getGlossaryKeyUrl($key),
            $this->tester->buildGlossaryKeyRequestBody([
                static::ATTRIBUTE_TRANSLATIONS => $this->tester->buildTranslationEntries([$this->tester->getAvailableLocaleNames()[0] => $this->tester->generateTranslationValue()]),
            ], $key),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
