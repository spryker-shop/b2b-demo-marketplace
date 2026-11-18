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
 * `GET /glossary-keys/{key}` over a booted GLUE_BACKEND kernel: the complete per-locale
 * translation list and the not-found guard.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group GlossaryBackend
 * @group BackendApi
 * @group Integration
 * @group GetSingleGlossaryKeyBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleGlossaryKeyBackendApiTest extends AbstractGlossaryBackendApiTestCase
{
    public function testGivenKeyWithAllTranslationsWhenRetrievingThenEveryLocaleCarriesItsValue(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $valuesIndexedByLocaleName = $this->tester->buildValuesForAllLocales();
        $key = $this->tester->haveGlossaryKey($valuesIndexedByLocaleName);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyUrl($key));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $attributes = $this->getResourceAttributes($response);
        $this->assertSame($key, $attributes[static::ATTRIBUTE_KEY]);
        ksort($valuesIndexedByLocaleName);
        $this->assertSame(
            $valuesIndexedByLocaleName,
            $this->indexTranslationValuesByLocaleName($attributes[static::ATTRIBUTE_TRANSLATIONS]),
            'Translations are complete and ordered by localeName.',
        );
    }

    public function testGivenKeyWithoutTranslationInSomeLocaleWhenRetrievingThenThatLocaleIsReportedWithNullValue(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $this->assertGreaterThan(1, count($localeNames), 'This test needs at least two configured locales.');
        [$translatedLocaleName, $untranslatedLocaleName] = $localeNames;
        $key = $this->tester->haveGlossaryKey([$translatedLocaleName => $this->tester->generateTranslationValue()]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyUrl($key));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $valuesIndexedByLocaleName = $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS]);
        $this->assertCount(count($localeNames), $valuesIndexedByLocaleName, 'Every configured locale must be listed.');
        $this->assertNotNull($valuesIndexedByLocaleName[$translatedLocaleName]);
        $this->assertArrayHasKey($untranslatedLocaleName, $valuesIndexedByLocaleName);
        $this->assertNull($valuesIndexedByLocaleName[$untranslatedLocaleName]);
    }

    public function testGivenDeactivatedTranslationWhenRetrievingThenItIsReportedWithNullValue(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeName = $this->tester->getAvailableLocaleNames()[0];
        $key = $this->tester->haveGlossaryKey();
        $this->tester->haveDeactivatedTranslation($key, $localeName);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyUrl($key));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $valuesIndexedByLocaleName = $this->indexTranslationValuesByLocaleName($this->getResourceAttributes($response)[static::ATTRIBUTE_TRANSLATIONS]);
        $this->assertNull($valuesIndexedByLocaleName[$localeName], 'A deactivated translation reads back as null, exactly like a missing one.');
    }

    public function testGivenKeyInDifferentCaseWhenRetrievingThenStoredKeyIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $key = $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyUrl(strtoupper($key)));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($key, $this->getResourceAttributes($response)[static::ATTRIBUTE_KEY], 'Keys are matched case-insensitively and the stored form is returned.');
    }

    public function testGivenKeyContainingSlashWhenRetrievingThenItIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $key = $this->tester->haveGlossaryKey(null, sprintf('%s.n/a', $this->tester->generateGlossaryKey()));

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyUrl($key));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($key, $this->getResourceAttributes($response)[static::ATTRIBUTE_KEY]);
    }

    public function testGivenUnknownKeyWhenRetrievingThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyUrl(static::UNKNOWN_GLOSSARY_KEY));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenRetrievingThenUnauthorizedIsReturned(): void
    {
        // Arrange
        $key = $this->tester->haveGlossaryKey();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getGlossaryKeyUrl($key));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
