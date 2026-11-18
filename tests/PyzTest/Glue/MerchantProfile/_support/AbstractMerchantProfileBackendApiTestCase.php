<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\MerchantProfile;

use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Merchant-profile arrangement for the backend integration suite. The JSON:API envelope itself is
 * asserted by {@see JsonApiResponseAssertionsTrait}, shared with every other API Platform suite.
 */
abstract class AbstractMerchantProfileBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string ATTRIBUTE_MERCHANT_REFERENCE = 'merchantReference';

    protected const string ATTRIBUTE_NAME = 'name';

    protected const string ATTRIBUTE_EMAIL = 'email';

    protected const string ATTRIBUTE_IS_ACTIVE = 'isActive';

    protected const string ATTRIBUTE_REGISTRATION_NUMBER = 'registrationNumber';

    protected const string ATTRIBUTE_STORES = 'stores';

    protected const string ATTRIBUTE_MERCHANT_URLS = 'merchantUrls';

    protected const string ATTRIBUTE_URL = 'url';

    protected const string ATTRIBUTE_PUBLIC_PHONE = 'publicPhone';

    protected const string ATTRIBUTE_PUBLIC_EMAIL = 'publicEmail';

    protected const string ATTRIBUTE_CONTACT_PERSON_FIRST_NAME = 'contactPersonFirstName';

    protected const string ATTRIBUTE_LOGO_URL = 'logoUrl';

    protected const string ATTRIBUTE_ADDRESS = 'address';

    protected const string ATTRIBUTE_COUNTRY_ISO2_CODE = 'countryIso2Code';

    protected const string ATTRIBUTE_CITY = 'city';

    protected const string ATTRIBUTE_ZIP_CODE = 'zipCode';

    protected const string ATTRIBUTE_LOCALIZED = 'localizedAttributes';

    protected const string ATTRIBUTE_LOCALE_NAME = 'localeName';

    protected const string ATTRIBUTE_DESCRIPTION = 'description';

    protected const string ATTRIBUTE_BANNER_URL = 'bannerUrl';

    protected const string ATTRIBUTE_IMPRINT = 'imprint';

    protected MerchantProfileBackendApiIntegrationTester $tester;

    /**
     * The error `detail` is the message template of the exception factory; a `%s` placeholder matches any value.
     */
    protected function assertRespondsWithErrorDetail(Response $response, int $expectedStatus, string $expectedDetailTemplate): void
    {
        $this->assertRespondsWithStatus($response, $expectedStatus);

        $pattern = sprintf('/^%s$/', str_replace('%s', '.+', preg_quote($expectedDetailTemplate, '/')));

        foreach ($this->getErrorDetails($response) as $errorDetail) {
            if (preg_match($pattern, $errorDetail) === 1) {
                return;
            }
        }

        $this->fail(sprintf('No error detail matches "%s": %s', $expectedDetailTemplate, implode(' | ', $this->getErrorDetails($response))));
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, array<string, string|null>>
     */
    protected function getLocalizedAttributesByLocaleName(array $attributes): array
    {
        return $this->indexByLocaleName($attributes[static::ATTRIBUTE_LOCALIZED] ?? []);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, array<string, string|null>>
     */
    protected function getMerchantUrlsByLocaleName(array $attributes): array
    {
        return $this->indexByLocaleName($attributes[static::ATTRIBUTE_MERCHANT_URLS] ?? []);
    }

    /**
     * @param array<int, array<string, string|null>> $entries
     *
     * @return array<string, array<string, string|null>>
     */
    protected function indexByLocaleName(array $entries): array
    {
        $entriesByLocaleName = [];

        foreach ($entries as $entry) {
            $entriesByLocaleName[$entry[static::ATTRIBUTE_LOCALE_NAME]] = $entry;
        }

        return $entriesByLocaleName;
    }
}
