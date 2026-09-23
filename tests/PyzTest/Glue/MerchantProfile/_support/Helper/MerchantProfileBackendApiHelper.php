<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\MerchantProfile\Helper;

use ArrayObject;
use Codeception\Module;
use Generated\Shared\DataBuilder\MerchantProfileAddressBuilder;
use Generated\Shared\DataBuilder\MerchantProfileBuilder;
use Generated\Shared\Transfer\LocaleConditionsTransfer;
use Generated\Shared\Transfer\LocaleCriteriaTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\MerchantCriteriaTransfer;
use Generated\Shared\Transfer\MerchantProfileGlossaryAttributeValuesTransfer;
use Generated\Shared\Transfer\MerchantProfileLocalizedGlossaryAttributesTransfer;
use Generated\Shared\Transfer\MerchantProfileTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Generated\Shared\Transfer\UserTransfer;
use Orm\Zed\Locale\Persistence\SpyLocaleStoreQuery;
use SprykerTest\Shared\Store\Helper\StoreDataHelper;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use SprykerTest\Shared\User\Helper\UserDataHelper;
use SprykerTest\Zed\Locale\Helper\LocaleDataHelper;
use SprykerTest\Zed\Merchant\Helper\MerchantHelper;
use SprykerTest\Zed\MerchantUser\Helper\MerchantUserHelper;

/**
 * Fixture arrangement and request building for the merchant profile Backend API.
 *
 * Routes are returned as paths with a leading slash, which is what
 * {@see \SprykerTest\ApiPlatform\Test\AbstractApiTestCase::handleApiRequest()} resolves against the
 * suite's base URL.
 */
class MerchantProfileBackendApiHelper extends Module
{
    use DataCleanupHelperTrait;
    use LocatorHelperTrait;

    public const string URL_OWN_MERCHANT_PROFILE = '/merchant-profile';

    public const string URL_MERCHANT_PROFILES = '/merchant-profiles';

    public const string RESOURCE_MERCHANT_PROFILES = 'merchant-profiles';

    public const string RESOURCE_MERCHANT_PROFILE = 'merchant-profile';

    public const string UNKNOWN_MERCHANT_REFERENCE = 'MER-merchant-profile-api-does-not-exist';

    /**
     * `ZZ` is present in the demo country data, so an "unknown country" assertion needs a code that
     * really is absent.
     */
    public const string UNKNOWN_ISO2_CODE = 'QQ';

    public const string ISO2_CODE = 'DE';

    /**
     * @uses \Spryker\Zed\Merchant\MerchantConfig::STATUS_WAITING_FOR_APPROVAL
     */
    protected const string MERCHANT_STATUS_WAITING_FOR_APPROVAL = 'waiting-for-approval';

    protected const string LOCALE_NAME_OF_ANOTHER_STORE = 'xx_XX';

    protected const string STORE_NAME_OF_ANOTHER_STORE = 'XX';

    public function getMerchantProfileUrl(string $merchantReference): string
    {
        return sprintf('%s/%s', static::URL_MERCHANT_PROFILES, $merchantReference);
    }

    /**
     * The profile has to be seeded through `haveMerchant()`, not created afterwards:
     * `MerchantProfileMerchantPostCreatePlugin` requires it on the merchant transfer, and
     * `spy_merchant_profile.fk_merchant` is unique, so a second insert would fail. The merchant is
     * assigned to one store so the self route has store locales to work with.
     *
     * @param array<string, mixed> $seedData
     */
    public function haveMerchantWithProfile(array $seedData = []): MerchantTransfer
    {
        $merchantTransfer = $this->getMerchantHelper()->haveMerchant($seedData + [
            MerchantTransfer::STATUS => MerchantHelper::STATUS_APPROVED,
            MerchantTransfer::IS_ACTIVE => true,
            MerchantTransfer::STORE_RELATION => $this->getMerchantHelper()->getStoreRelationTransfer()->toArray(),
            MerchantTransfer::MERCHANT_PROFILE => $this->buildMerchantProfileWithAddress()->toArray(),
        ]);

        return $this->getMerchant($merchantTransfer->getMerchantReferenceOrFail());
    }

    public function haveMerchantWithProfileWaitingForApproval(): MerchantTransfer
    {
        return $this->haveMerchantWithProfile([
            MerchantTransfer::STATUS => static::MERCHANT_STATUS_WAITING_FOR_APPROVAL,
        ]);
    }

    public function haveUserOfMerchant(MerchantTransfer $merchantTransfer): UserTransfer
    {
        $userTransfer = $this->getUserDataHelper()->haveUser();
        $this->getMerchantUserHelper()->haveMerchantUser($merchantTransfer, $userTransfer);

        return $userTransfer;
    }

    /**
     * A locale assigned to a store the merchant is not assigned to. Only store-assigned locales count as
     * configured (`LocaleFacade::getLocaleCollection()` without criteria, as the Back Office forms read
     * them), so this is the locale the self route refuses and the Back Office route accepts. The
     * store relation is removed after the test; the locale row stays, `haveLocale()` reuses it.
     */
    public function haveLocaleOfAnotherStore(MerchantTransfer $merchantTransfer): LocaleTransfer
    {
        $localeTransfer = $this->getLocaleDataHelper()->haveLocale([LocaleTransfer::LOCALE_NAME => static::LOCALE_NAME_OF_ANOTHER_STORE]);
        $idStore = $this->getIdStoreNotAssignedToMerchant($merchantTransfer);
        $idLocale = $localeTransfer->getIdLocaleOrFail();

        $this->getLocaleDataHelper()->haveLocaleStore($idStore, $idLocale);
        $this->getDataCleanupHelper()->_addCleanup(function () use ($idStore, $idLocale): void {
            SpyLocaleStoreQuery::create()->filterByFkStore($idStore)->filterByFkLocale($idLocale)->delete();
        });

        // The locale reader memoizes store-assigned locales per process; `haveLocale()` on an existing locale resets that cache.
        return $this->getLocaleDataHelper()->haveLocale([LocaleTransfer::LOCALE_NAME => static::LOCALE_NAME_OF_ANOTHER_STORE]);
    }

    protected function getIdStoreNotAssignedToMerchant(MerchantTransfer $merchantTransfer): int
    {
        $merchantStoreNames = [];

        foreach ($merchantTransfer->getStoreRelationOrFail()->getStores() as $storeTransfer) {
            $merchantStoreNames[] = $storeTransfer->getNameOrFail();
        }

        foreach ($this->getLocator()->store()->facade()->getAllStores() as $storeTransfer) {
            if (!in_array($storeTransfer->getNameOrFail(), $merchantStoreNames, true)) {
                return $storeTransfer->getIdStoreOrFail();
            }
        }

        return $this->getStoreDataHelper()->haveStore([StoreTransfer::NAME => static::STORE_NAME_OF_ANOTHER_STORE])->getIdStoreOrFail();
    }

    /**
     * @param array<string, string|null> $glossaryAttributeValues
     */
    public function haveLocalizedProfileTexts(MerchantTransfer $merchantTransfer, array $glossaryAttributeValues): MerchantProfileTransfer
    {
        $localizedGlossaryAttributes = new ArrayObject();

        foreach ($this->getLocaleCollection() as $localeTransfer) {
            $localizedGlossaryAttributes->append(
                (new MerchantProfileLocalizedGlossaryAttributesTransfer())
                    ->setLocale($localeTransfer)
                    ->setMerchantProfileGlossaryAttributeValues(
                        (new MerchantProfileGlossaryAttributeValuesTransfer())->fromArray($glossaryAttributeValues, true),
                    ),
            );
        }

        $merchantProfileTransfer = $merchantTransfer->getMerchantProfileOrFail()
            ->setMerchantProfileLocalizedGlossaryAttributes($localizedGlossaryAttributes);

        return $this->getLocator()->merchantProfile()->facade()->updateMerchantProfile($merchantProfileTransfer);
    }

    /**
     * @return array<\Generated\Shared\Transfer\LocaleTransfer>
     */
    public function getLocaleCollection(): array
    {
        return $this->getLocator()->locale()->facade()->getLocaleCollection();
    }

    /**
     * @return array<string>
     */
    public function getStoreLocaleNames(MerchantTransfer $merchantTransfer): array
    {
        $storeNames = [];

        foreach ($merchantTransfer->getStoreRelationOrFail()->getStores() as $storeTransfer) {
            $storeNames[] = $storeTransfer->getNameOrFail();
        }

        $localeCriteriaTransfer = (new LocaleCriteriaTransfer())->setLocaleConditions(
            (new LocaleConditionsTransfer())->setStoreNames($storeNames),
        );

        $localeNames = [];

        foreach ($this->getLocator()->locale()->facade()->getLocaleCollection($localeCriteriaTransfer) as $localeTransfer) {
            $localeNames[] = $localeTransfer->getLocaleNameOrFail();
        }

        sort($localeNames);

        return $localeNames;
    }

    public function getMerchant(string $merchantReference): MerchantTransfer
    {
        $merchantTransfers = $this->getLocator()->merchant()->facade()->get(
            (new MerchantCriteriaTransfer())->setMerchantReference($merchantReference),
        )->getMerchants();

        $this->assertCount(1, $merchantTransfers, sprintf('Merchant "%s" must exist.', $merchantReference));

        return $merchantTransfers->offsetGet(0);
    }

    public function findMerchantProfile(string $merchantReference): MerchantProfileTransfer
    {
        return $this->getMerchant($merchantReference)->getMerchantProfileOrFail();
    }

    /**
     * The request document of the Back Office resource (`merchant-profiles`).
     *
     * @param array<string, mixed> $attributes
     */
    public function buildMerchantProfileRequestBody(array $attributes): string
    {
        return $this->buildRequestBody(static::RESOURCE_MERCHANT_PROFILES, $attributes);
    }

    /**
     * The request document of the self resource (`merchant-profile`).
     *
     * @param array<string, mixed> $attributes
     */
    public function buildOwnMerchantProfileRequestBody(array $attributes): string
    {
        return $this->buildRequestBody(static::RESOURCE_MERCHANT_PROFILE, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function buildRequestBody(string $resourceType, array $attributes): string
    {
        return (string)json_encode([
            'data' => [
                'type' => $resourceType,
                'attributes' => $attributes,
            ],
        ]);
    }

    protected function buildMerchantProfileWithAddress(): MerchantProfileTransfer
    {
        $merchantProfileAddressTransfer = (new MerchantProfileAddressBuilder())->build()
            ->setIdMerchantProfileAddress(null)
            ->setFkCountry($this->getIdCountry(static::ISO2_CODE));

        return (new MerchantProfileBuilder())->build()
            ->setAddressCollection(new ArrayObject([$merchantProfileAddressTransfer]));
    }

    protected function getIdCountry(string $iso2Code): int
    {
        return $this->getLocator()->country()->facade()->getCountryByIso2Code($iso2Code)->getIdCountryOrFail();
    }

    protected function getMerchantHelper(): MerchantHelper
    {
        /** @var \SprykerTest\Zed\Merchant\Helper\MerchantHelper $merchantHelper */
        $merchantHelper = $this->getModule('\\' . MerchantHelper::class);

        return $merchantHelper;
    }

    protected function getMerchantUserHelper(): MerchantUserHelper
    {
        /** @var \SprykerTest\Zed\MerchantUser\Helper\MerchantUserHelper $merchantUserHelper */
        $merchantUserHelper = $this->getModule('\\' . MerchantUserHelper::class);

        return $merchantUserHelper;
    }

    protected function getUserDataHelper(): UserDataHelper
    {
        /** @var \SprykerTest\Shared\User\Helper\UserDataHelper $userDataHelper */
        $userDataHelper = $this->getModule('\\' . UserDataHelper::class);

        return $userDataHelper;
    }

    protected function getStoreDataHelper(): StoreDataHelper
    {
        /** @var \SprykerTest\Shared\Store\Helper\StoreDataHelper $storeDataHelper */
        $storeDataHelper = $this->getModule('\\' . StoreDataHelper::class);

        return $storeDataHelper;
    }

    protected function getLocaleDataHelper(): LocaleDataHelper
    {
        /** @var \SprykerTest\Zed\Locale\Helper\LocaleDataHelper $localeDataHelper */
        $localeDataHelper = $this->getModule('\\' . LocaleDataHelper::class);

        return $localeDataHelper;
    }
}
