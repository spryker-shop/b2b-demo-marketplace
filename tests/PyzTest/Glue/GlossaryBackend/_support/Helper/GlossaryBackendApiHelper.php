<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\GlossaryBackend\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\KeyTranslationTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\TranslationTransfer;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * Request builders and glossary fixtures for the `glossary-keys` Backend API integration tests.
 * Fixtures go through the Glossary facade, so they are rolled back with the test transaction.
 */
class GlossaryBackendApiHelper extends Module
{
    use LocatorHelperTrait;

    public const string RESOURCE_GLOSSARY_KEYS = 'glossary-keys';

    protected const string ATTRIBUTE_KEY = 'key';

    protected const string ATTRIBUTE_TRANSLATIONS = 'translations';

    protected const string ATTRIBUTE_LOCALE_NAME = 'localeName';

    protected const string ATTRIBUTE_VALUE = 'value';

    protected const string KEY_PREFIX = 'glossary-backend-api.';

    protected const string VALUE_PREFIX = 'Glossary backend API value ';

    public function getGlossaryKeyCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_GLOSSARY_KEYS) . $this->formatQuery($query);
    }

    public function getGlossaryKeyUrl(string $key): string
    {
        return sprintf('/%s/%s', static::RESOURCE_GLOSSARY_KEYS, rawurlencode($key));
    }

    /**
     * A complete, valid POST payload: generated key and a generated value in every configured locale.
     *
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidGlossaryKeyAttributes(array $override = []): array
    {
        return array_merge([
            static::ATTRIBUTE_KEY => $this->generateGlossaryKey(),
            static::ATTRIBUTE_TRANSLATIONS => $this->buildTranslationEntries($this->buildValuesForAllLocales()),
        ], $override);
    }

    /**
     * @param array<string, string|null> $valuesIndexedByLocaleName
     *
     * @return array<array<string, string|null>>
     */
    public function buildTranslationEntries(array $valuesIndexedByLocaleName): array
    {
        $translationEntries = [];
        foreach ($valuesIndexedByLocaleName as $localeName => $value) {
            $translationEntries[] = [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_VALUE => $value];
        }

        return $translationEntries;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildGlossaryKeyRequestBody(array $attributes, ?string $key = null): string
    {
        $data = [
            'type' => static::RESOURCE_GLOSSARY_KEYS,
            'attributes' => $attributes,
        ];

        if ($key !== null) {
            $data['id'] = $key;
        }

        return (string)json_encode(['data' => $data]);
    }

    /**
     * Persists a key with the given translations (all configured locales when omitted) through the facade.
     *
     * @param array<string, string>|null $valuesIndexedByLocaleName
     */
    public function haveGlossaryKey(?array $valuesIndexedByLocaleName = null, ?string $key = null): string
    {
        $key ??= $this->generateGlossaryKey();

        $this->getGlossaryFacade()->saveGlossaryKeyTranslations(
            (new KeyTranslationTransfer())
                ->setGlossaryKey($key)
                ->setLocales($valuesIndexedByLocaleName ?? $this->buildValuesForAllLocales()),
        );

        return $key;
    }

    /**
     * Deactivates the translation the way the Back Office does when the field is cleared.
     */
    public function haveDeactivatedTranslation(string $key, string $localeName): void
    {
        $this->getGlossaryFacade()->deleteTranslation($key, (new LocaleTransfer())->setLocaleName($localeName));
    }

    /**
     * The stored row of one translation, or null when the key has no row for the locale at all.
     */
    public function findStoredTranslation(string $key, string $localeName): ?TranslationTransfer
    {
        $translationTransfers = $this->getGlossaryFacade()->getTranslationsByGlossaryKeyAndLocales(
            $key,
            [(new LocaleTransfer())->setLocaleName($localeName)],
        );

        return $translationTransfers[0] ?? null;
    }

    /**
     * @return array<string> Locale names configured for the current store, e.g. ['de_DE', 'en_US'].
     */
    public function getAvailableLocaleNames(): array
    {
        return array_keys($this->getLocaleFacade()->getLocaleCollection());
    }

    public function generateGlossaryKey(): string
    {
        return uniqid(static::KEY_PREFIX, false);
    }

    public function generateTranslationValue(): string
    {
        return uniqid(static::VALUE_PREFIX, false);
    }

    /**
     * @return array<string, string>
     */
    public function buildValuesForAllLocales(): array
    {
        $valuesIndexedByLocaleName = [];
        foreach ($this->getAvailableLocaleNames() as $localeName) {
            $valuesIndexedByLocaleName[$localeName] = $this->generateTranslationValue();
        }

        return $valuesIndexedByLocaleName;
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function formatQuery(array $query): string
    {
        if ($query === []) {
            return '';
        }

        return '?' . http_build_query($query);
    }

    protected function getGlossaryFacade(): GlossaryFacadeInterface
    {
        return $this->getLocator()->glossary()->facade();
    }

    protected function getLocaleFacade(): LocaleFacadeInterface
    {
        return $this->getLocator()->locale()->facade();
    }
}
