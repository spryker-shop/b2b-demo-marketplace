<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Merchant\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\LocaleConditionsTransfer;
use Generated\Shared\Transfer\LocaleCriteriaTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\Merchant\MerchantConfig;
use SprykerTest\Shared\Store\Helper\StoreDataHelper;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use SprykerTest\Zed\Merchant\Helper\MerchantHelper;

class MerchantBackendApiHelper extends Module
{
    use LocatorHelperTrait;

    public const string RESOURCE_MERCHANTS = 'merchants';

    /**
     * Distinguishes the merchants of one test method from every other row in the database, so a
     * search term can isolate exactly them. The suite rolls its writes back, but the database it runs
     * against is the shared development one and already holds demo merchants.
     */
    protected const string LISTED_NAME_PREFIX = 'MerchantBackendApi';

    protected const string STORE_NAME = 'DE';

    protected const string SECOND_STORE_NAME = 'AT';

    protected const string MERCHANT_URL_KEY_LOCALE_NAME = 'localeName';

    protected const string MERCHANT_URL_KEY_URL = 'url';

    protected const string ATTRIBUTE_MERCHANT_URLS = 'merchantUrls';

    protected const string MERCHANT_URL_PREFIX = 'merchant-ba-';

    public function getStoreName(): string
    {
        return static::STORE_NAME;
    }

    public function getSecondStoreName(): string
    {
        return static::SECOND_STORE_NAME;
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidMerchantAttributes(array $override = []): array
    {
        return $override + [
            MerchantTransfer::MERCHANT_REFERENCE => strtoupper(uniqid('MER-BA-')),
            MerchantTransfer::NAME => static::LISTED_NAME_PREFIX . ' Created',
            MerchantTransfer::EMAIL => uniqid('merchant.backend.api.', true) . '@spryker.local',
            MerchantTransfer::REGISTRATION_NUMBER => 'HRB 12345',
            static::ATTRIBUTE_MERCHANT_URLS => $this->buildMerchantUrlsForEveryLocale(),
        ];
    }

    /**
     * A URL is required for every locale, the same requirement the Back Office form enforces - see
     * {@link \Spryker\Zed\Merchant\Business\Validator\UrlMerchantValidator}.
     *
     * @return array<int, array<string, string>>
     */
    public function buildMerchantUrlsForEveryLocale(): array
    {
        $merchantUrls = [];

        foreach ($this->getLocator()->locale()->facade()->getLocaleCollection() as $localeTransfer) {
            $merchantUrls[] = $this->buildMerchantUrl(null, $localeTransfer->getLocaleName());
        }

        return $merchantUrls;
    }

    /**
     * One locale gets the given URL, every other locale still gets a URL so the "every locale needs a
     * URL" validation the API enforces on create is satisfied.
     *
     * @return array{0: array<int, array<string, string>>, 1: array<string, string>}
     */
    public function buildMerchantUrlsForEveryLocaleWithOneOverridden(string $url): array
    {
        $localeTransfers = $this->getLocator()->locale()->facade()->getLocaleCollection();
        $overriddenLocaleName = (string)array_key_first($localeTransfers);
        $overriddenMerchantUrl = $this->buildMerchantUrl($url, $overriddenLocaleName);

        $merchantUrls = array_map(
            fn (array $merchantUrl): array => $merchantUrl[static::MERCHANT_URL_KEY_LOCALE_NAME] === $overriddenLocaleName
                ? $overriddenMerchantUrl
                : $merchantUrl,
            $this->buildMerchantUrlsForEveryLocale(),
        );

        return [$merchantUrls, $overriddenMerchantUrl];
    }

    /**
     * Every system locale gets a URL except one that the given store actually requires, so the
     * request is missing coverage for a locale the Backend API must enforce for that store.
     *
     * @return array<int, array<string, string>>
     */
    public function buildMerchantUrlsMissingALocaleForStore(string $storeName): array
    {
        $storeLocaleTransfers = $this->getLocator()->locale()->facade()->getLocaleCollection(
            (new LocaleCriteriaTransfer())->setLocaleConditions(
                (new LocaleConditionsTransfer())->setStoreNames([$storeName]),
            ),
        );

        $missingLocaleName = (string)array_key_first($storeLocaleTransfers);

        return array_values(array_filter(
            $this->buildMerchantUrlsForEveryLocale(),
            fn (array $merchantUrl): bool => $merchantUrl[static::MERCHANT_URL_KEY_LOCALE_NAME] !== $missingLocaleName,
        ));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildMerchantRequestBody(array $attributes, ?string $merchantReference = null): string
    {
        $data = [
            'type' => static::RESOURCE_MERCHANTS,
            'attributes' => $attributes,
        ];

        if ($merchantReference !== null) {
            $data['id'] = $merchantReference;
        }

        return (string)json_encode(['data' => $data]);
    }

    /**
     * @return non-empty-string
     */
    public function getMerchantUrl(string $merchantReference): string
    {
        return sprintf('/%s/%s', static::RESOURCE_MERCHANTS, $merchantReference);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getMerchantCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_MERCHANTS) . $this->formatQuery($query);
    }

    public function haveWaitingForApprovalMerchant(): MerchantTransfer
    {
        return $this->haveMerchantWithStatus(MerchantConfig::STATUS_WAITING_FOR_APPROVAL);
    }

    public function haveApprovedMerchant(): MerchantTransfer
    {
        return $this->haveMerchantWithStatus(MerchantConfig::STATUS_APPROVED);
    }

    public function haveDeniedMerchant(): MerchantTransfer
    {
        return $this->haveMerchantWithStatus(MerchantConfig::STATUS_DENIED);
    }

    /**
     * Provisioned through `haveActiveMerchantWithStore()` rather than `haveMerchant()` because the
     * project wires `MerchantProfileMerchantPostCreatePlugin`, which requires a merchant profile on
     * the transfer; only the former builds one.
     */
    public function haveMerchantWithStatus(string $status, bool $isActive = false): MerchantTransfer
    {
        return $this->getMerchantHelper()->haveActiveMerchantWithStore([
            MerchantTransfer::NAME => static::LISTED_NAME_PREFIX . ' ' . uniqid(),
            MerchantTransfer::STATUS => $status,
            MerchantTransfer::IS_ACTIVE => $isActive,
            MerchantTransfer::STORE_RELATION => [
                StoreRelationTransfer::ID_STORES => [$this->haveStore()->getIdStoreOrFail()],
            ],
        ]);
    }

    /**
     * A merchant assigned to a store but missing a URL for one of that store's locales, so a PATCH
     * that does not fill the gap must be rejected by the Backend API.
     */
    public function haveMerchantWithoutUrls(): MerchantTransfer
    {
        return $this->getMerchantHelper()->haveActiveMerchantWithStore([
            MerchantTransfer::NAME => static::LISTED_NAME_PREFIX . ' ' . uniqid(),
            MerchantTransfer::STATUS => MerchantConfig::STATUS_WAITING_FOR_APPROVAL,
            MerchantTransfer::IS_ACTIVE => false,
            MerchantTransfer::STORE_RELATION => [
                StoreRelationTransfer::ID_STORES => [$this->haveStore()->getIdStoreOrFail()],
            ],
            MerchantTransfer::URL_COLLECTION => [],
        ]);
    }

    /**
     * Both merchants carry the returned search term in their name, so a collection request can
     * isolate exactly them: the suite runs against the shared development database, which already
     * holds demo merchants. They differ in status and activity so a filter can tell them apart.
     *
     * @return array{0: string, 1: \Generated\Shared\Transfer\MerchantTransfer, 2: \Generated\Shared\Transfer\MerchantTransfer}
     */
    public function haveTwoListedMerchants(): array
    {
        $searchTerm = uniqid(static::LISTED_NAME_PREFIX);

        return [
            $searchTerm,
            $this->haveListedMerchant($searchTerm . ' Aaron', MerchantConfig::STATUS_APPROVED, true),
            $this->haveListedMerchant($searchTerm . ' Zoe', MerchantConfig::STATUS_DENIED, false),
        ];
    }

    /**
     * Three merchants, so that an item window can be asked for with an offset that is not a multiple
     * of the limit - the case in which converting the offset to a page number answers the wrong rows.
     *
     * @return array{0: string, 1: \Generated\Shared\Transfer\MerchantTransfer, 2: \Generated\Shared\Transfer\MerchantTransfer, 3: \Generated\Shared\Transfer\MerchantTransfer}
     */
    public function haveThreeListedMerchants(): array
    {
        $searchTerm = uniqid(static::LISTED_NAME_PREFIX);

        return [
            $searchTerm,
            $this->haveListedMerchant($searchTerm . ' Aaron', MerchantConfig::STATUS_APPROVED, true),
            $this->haveListedMerchant($searchTerm . ' Mary', MerchantConfig::STATUS_APPROVED, true),
            $this->haveListedMerchant($searchTerm . ' Zoe', MerchantConfig::STATUS_DENIED, false),
        ];
    }

    public function haveListedMerchant(string $name, string $status, bool $isActive): MerchantTransfer
    {
        return $this->getMerchantHelper()->haveActiveMerchantWithStore([
            MerchantTransfer::NAME => $name,
            MerchantTransfer::STATUS => $status,
            MerchantTransfer::IS_ACTIVE => $isActive,
            MerchantTransfer::STORE_RELATION => [
                StoreRelationTransfer::ID_STORES => [$this->haveStore()->getIdStoreOrFail()],
            ],
        ]);
    }

    /**
     * The name is unrelated to the given reference, so a collection search matching only by
     * reference can be told apart from one matching by name.
     */
    public function haveListedMerchantWithMerchantReference(string $merchantReference): MerchantTransfer
    {
        return $this->getMerchantHelper()->haveActiveMerchantWithStore([
            MerchantTransfer::MERCHANT_REFERENCE => $merchantReference,
            MerchantTransfer::NAME => static::LISTED_NAME_PREFIX . ' ' . uniqid(),
            MerchantTransfer::STATUS => MerchantConfig::STATUS_APPROVED,
            MerchantTransfer::IS_ACTIVE => true,
            MerchantTransfer::STORE_RELATION => [
                StoreRelationTransfer::ID_STORES => [$this->haveStore()->getIdStoreOrFail()],
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function buildMerchantUrl(?string $url = null, ?string $localeName = null): array
    {
        return [
            static::MERCHANT_URL_KEY_LOCALE_NAME => $localeName ?? $this->getAnyLocaleName(),
            static::MERCHANT_URL_KEY_URL => $url ?? '/' . uniqid(static::MERCHANT_URL_PREFIX),
        ];
    }

    /**
     * An ad-hoc locale created only for this test is invisible to the Backend API's locale lookup
     * when dynamic store mode is off, because that mode resolves locales from the store's static
     * configuration rather than the database - so the default here must be a real, already
     * store-assigned locale instead.
     */
    protected function getAnyLocaleName(): string
    {
        return (string)array_key_first($this->getLocator()->locale()->facade()->getLocaleCollection());
    }

    /**
     * The Backend API prepends a locale-specific prefix (e.g. `/de/merchant/`) to every URL it
     * persists - see {@link \Spryker\Zed\Merchant\Business\Url\MerchantUrlPrefixBuilder} - so a test
     * asserting on a persisted/returned URL must build its expectation with the same prefix.
     *
     * @param array<string, string> $merchantUrl
     *
     * @return array<string, string>
     */
    public function buildExpectedPersistedMerchantUrl(array $merchantUrl): array
    {
        $localeName = $merchantUrl[static::MERCHANT_URL_KEY_LOCALE_NAME];

        $localeTransfer = $this->getLocator()->locale()->facade()->getLocaleCollection(
            (new LocaleCriteriaTransfer())->setLocaleConditions(
                (new LocaleConditionsTransfer())->setLocaleNames([$localeName]),
            ),
        )[$localeName];

        $urlPrefix = $this->getLocator()->merchant()->facade()->buildMerchantUrlPrefixForLocale($localeTransfer);

        return [
            static::MERCHANT_URL_KEY_LOCALE_NAME => $localeName,
            static::MERCHANT_URL_KEY_URL => $urlPrefix . ltrim($merchantUrl[static::MERCHANT_URL_KEY_URL], '/'),
        ];
    }

    public function haveStore(?string $storeName = null): StoreTransfer
    {
        return $this->getStoreDataHelper()->haveStore([StoreTransfer::NAME => $storeName ?? static::STORE_NAME]);
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

    protected function getMerchantHelper(): MerchantHelper
    {
        /** @var \SprykerTest\Zed\Merchant\Helper\MerchantHelper $merchantHelper */
        $merchantHelper = $this->getModule('\\' . MerchantHelper::class);

        return $merchantHelper;
    }

    protected function getStoreDataHelper(): StoreDataHelper
    {
        /** @var \SprykerTest\Shared\Store\Helper\StoreDataHelper $storeDataHelper */
        $storeDataHelper = $this->getModule('\\' . StoreDataHelper::class);

        return $storeDataHelper;
    }
}
