<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\MerchantProfile\BackendApi\Integration;

use Generated\Shared\Transfer\MerchantProfileGlossaryAttributeValuesTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use PyzTest\Glue\MerchantProfile\AbstractMerchantProfileBackendApiTestCase;
use PyzTest\Glue\MerchantProfile\Helper\MerchantProfileBackendApiHelper;
use Spryker\Glue\MerchantProfile\MerchantProfileConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * What a PATCH actually writes, on both the self and the Back Office route.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group MerchantProfile
 * @group BackendApi
 * @group Integration
 * @group UpdateMerchantProfileBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateMerchantProfileBackendApiTest extends AbstractMerchantProfileBackendApiTestCase
{
    protected const string PUBLIC_PHONE = '+49 30 111111';

    protected const string MERCHANT_NAME = 'Renamed by the API';

    protected const string DESCRIPTION_STORED = 'Stored description';

    protected const string IMPRINT_STORED = 'Stored imprint';

    protected const string BANNER_URL_STORED = 'https://images.example.com/banner-stored.png';

    protected const string DESCRIPTION_PATCHED = 'Patched description';

    protected const string DESCRIPTION_WITH_FORBIDDEN_TAG = '<p>Patched</p><script>alert(1)</script>';

    protected const string CITY_PATCHED = 'Patched city';

    protected const string MERCHANT_URL_PREFIX_FORMAT = '/%s/merchant/%s';

    protected const string REGISTRATION_NUMBER = 'HRB 000001';

    public function testGivenAMerchantUserWhenPatchOwnProfileThenOnlyTheSubmittedPropertiesChange(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $merchantProfileTransfer = $merchantTransfer->getMerchantProfileOrFail();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame(static::PUBLIC_PHONE, $attributes[static::ATTRIBUTE_PUBLIC_PHONE]);
        $this->assertSame($merchantProfileTransfer->getPublicEmail(), $attributes[static::ATTRIBUTE_PUBLIC_EMAIL]);
        $this->assertSame($merchantProfileTransfer->getContactPersonFirstName(), $attributes[static::ATTRIBUTE_CONTACT_PERSON_FIRST_NAME]);
        $this->assertSame($merchantProfileTransfer->getLogoUrl(), $attributes[static::ATTRIBUTE_LOGO_URL]);
        $this->assertSame($merchantTransfer->getName(), $attributes[static::ATTRIBUTE_NAME]);
    }

    public function testGivenAMerchantUserWhenPatchMerchantFieldsThenTheMerchantIsUpdated(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_NAME => static::MERCHANT_NAME,
                static::ATTRIBUTE_REGISTRATION_NUMBER => static::REGISTRATION_NUMBER,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame(static::MERCHANT_NAME, $attributes[static::ATTRIBUTE_NAME]);
        $this->assertSame(static::REGISTRATION_NUMBER, $attributes[static::ATTRIBUTE_REGISTRATION_NUMBER]);

        $storedMerchantTransfer = $this->tester->getMerchant($merchantTransfer->getMerchantReferenceOrFail());
        $this->assertSame(static::MERCHANT_NAME, $storedMerchantTransfer->getName());
        $this->assertSame(static::REGISTRATION_NUMBER, $storedMerchantTransfer->getRegistrationNumber());
    }

    /**
     * `isActive` is the "Store Status" switch of the Merchant Portal profile page, so a merchant user
     * takes its own store offline through the API as well.
     */
    public function testGivenIsActiveFalseWhenPatchOwnProfileThenTheMerchantIsTakenOffline(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([static::ATTRIBUTE_IS_ACTIVE => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertFalse($this->getResourceAttributes($response)[static::ATTRIBUTE_IS_ACTIVE]);
        $this->assertFalse($this->tester->getMerchant($merchantTransfer->getMerchantReferenceOrFail())->getIsActive());
    }

    public function testGivenAMerchantUserWhenPatchOwnProfileThenTheAuthenticatedMerchantStaysTheOwner(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $otherMerchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE,
                static::ATTRIBUTE_MERCHANT_REFERENCE => $otherMerchantTransfer->getMerchantReferenceOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $merchantTransfer->getMerchantReferenceOrFail(),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_MERCHANT_REFERENCE],
        );
        $this->assertSame(
            $otherMerchantTransfer->getMerchantProfileOrFail()->getPublicPhone(),
            $this->tester->findMerchantProfile($otherMerchantTransfer->getMerchantReferenceOrFail())->getPublicPhone(),
            'The other merchant\'s profile must be untouched by a payload naming it.',
        );
    }

    public function testGivenNullForANullablePropertyWhenPatchThenThePropertyIsCleared(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => null]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertNull($this->getResourceAttributes($response)[static::ATTRIBUTE_PUBLIC_PHONE]);
        $this->assertNull($this->tester->findMerchantProfile($merchantTransfer->getMerchantReferenceOrFail())->getPublicPhone());
    }

    public function testGivenNullForARequiredPropertyWhenPatchThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([static::ATTRIBUTE_NAME => null]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSame(
            $merchantTransfer->getName(),
            $this->tester->getMerchant($merchantTransfer->getMerchantReferenceOrFail())->getName(),
        );
    }

    /**
     * The regression test for `MerchantProfileGlossaryWriter`, which deletes the translation of
     * every glossary attribute left empty within a submitted locale. Patching one text must not
     * silently drop the others of that locale.
     */
    public function testGivenALocalizedEntryWithOneTextWhenPatchThenTheOtherTextsOfThatLocaleSurvive(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->haveLocalizedProfileTexts($merchantTransfer, $this->buildStoredGlossaryAttributeValues());
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));
        $localeName = $this->tester->getStoreLocaleNames($merchantTransfer)[0];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_LOCALIZED => [
                    [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_DESCRIPTION => static::DESCRIPTION_PATCHED],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $localizedAttributes = $this->getLocalizedAttributesByLocaleName($this->getResourceAttributes($response));
        $this->assertSame(static::DESCRIPTION_PATCHED, $localizedAttributes[$localeName][static::ATTRIBUTE_DESCRIPTION]);
        $this->assertSame(
            static::IMPRINT_STORED,
            $localizedAttributes[$localeName][static::ATTRIBUTE_IMPRINT],
            'Patching one text of a locale must not delete the other texts of that locale.',
        );
    }

    public function testGivenALocalizedEntryForOneLocaleWhenPatchThenTheOtherLocalesAreUntouched(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->haveLocalizedProfileTexts($merchantTransfer, $this->buildStoredGlossaryAttributeValues());
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));
        $localeNames = $this->tester->getStoreLocaleNames($merchantTransfer);

        if (count($localeNames) < 2) {
            $this->markTestSkipped('The merchant store has a single locale, so cross-locale isolation cannot be observed.');
        }

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_LOCALIZED => [
                    [static::ATTRIBUTE_LOCALE_NAME => $localeNames[0], static::ATTRIBUTE_DESCRIPTION => static::DESCRIPTION_PATCHED],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $localizedAttributes = $this->getLocalizedAttributesByLocaleName($this->getResourceAttributes($response));
        $this->assertSame(static::DESCRIPTION_PATCHED, $localizedAttributes[$localeNames[0]][static::ATTRIBUTE_DESCRIPTION]);
        $this->assertSame(
            static::DESCRIPTION_STORED,
            $localizedAttributes[$localeNames[1]][static::ATTRIBUTE_DESCRIPTION],
            'A locale absent from the payload keeps its stored texts.',
        );
    }

    public function testGivenAnUnknownLocaleNameWhenPatchThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_LOCALIZED => [
                    [static::ATTRIBUTE_LOCALE_NAME => 'zz_ZZ', static::ATTRIBUTE_DESCRIPTION => static::DESCRIPTION_PATCHED],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorDetail($response, Response::HTTP_UNPROCESSABLE_ENTITY, MerchantProfileConfig::RESPONSE_DETAILS_UNKNOWN_LOCALE);
    }

    /**
     * Without dynamic store mode `LocaleFacade::getLocaleCollection()` ignores its criteria and returns the
     * locales of the store configuration for every store, so a locale outside the merchant's stores cannot
     * exist and the rule has nothing to reject.
     */
    public function testGivenALocaleOutsideTheMerchantStoresWhenPatchOwnProfileThenItRespondsUnprocessable(): void
    {
        // Arrange
        $this->skipUnlessDynamicStoreModeIsEnabled();
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $localeTransfer = $this->tester->haveLocaleOfAnotherStore($merchantTransfer);
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_LOCALIZED => [
                    [static::ATTRIBUTE_LOCALE_NAME => $localeTransfer->getLocaleNameOrFail(), static::ATTRIBUTE_DESCRIPTION => static::DESCRIPTION_PATCHED],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorDetail($response, Response::HTTP_UNPROCESSABLE_ENTITY, MerchantProfileConfig::RESPONSE_DETAILS_LOCALE_NOT_ALLOWED);
    }

    /**
     * Without dynamic store mode every configured locale belongs to every store, so the locale created
     * for another store is not configured at all and the Back Office route refuses it as unknown.
     */
    public function testGivenALocaleOutsideTheMerchantStoresWhenPatchByReferenceThenItIsAccepted(): void
    {
        // Arrange
        $this->skipUnlessDynamicStoreModeIsEnabled();
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $localeTransfer = $this->tester->haveLocaleOfAnotherStore($merchantTransfer);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantProfileRequestBody([
                static::ATTRIBUTE_LOCALIZED => [
                    [
                        static::ATTRIBUTE_LOCALE_NAME => $localeTransfer->getLocaleNameOrFail(),
                        static::ATTRIBUTE_DESCRIPTION => static::DESCRIPTION_PATCHED,
                        static::ATTRIBUTE_BANNER_URL => static::BANNER_URL_STORED,
                    ],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $localizedAttributes = $this->getLocalizedAttributesByLocaleName($this->getResourceAttributes($response));
        $this->assertSame(
            static::DESCRIPTION_PATCHED,
            $localizedAttributes[$localeTransfer->getLocaleNameOrFail()][static::ATTRIBUTE_DESCRIPTION],
        );
    }

    public function testGivenAForbiddenHtmlTagWhenPatchOwnProfileThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));
        $localeName = $this->tester->getStoreLocaleNames($merchantTransfer)[0];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_LOCALIZED => [
                    [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_DESCRIPTION => static::DESCRIPTION_WITH_FORBIDDEN_TAG],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAForbiddenHtmlTagWhenPatchByReferenceThenItIsAccepted(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();
        $localeName = $this->tester->getStoreLocaleNames($merchantTransfer)[0];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantProfileRequestBody([
                static::ATTRIBUTE_LOCALIZED => [
                    [
                        static::ATTRIBUTE_LOCALE_NAME => $localeName,
                        static::ATTRIBUTE_DESCRIPTION => static::DESCRIPTION_WITH_FORBIDDEN_TAG,
                        static::ATTRIBUTE_BANNER_URL => static::BANNER_URL_STORED,
                    ],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            static::DESCRIPTION_WITH_FORBIDDEN_TAG,
            $this->getLocalizedAttributesByLocaleName($this->getResourceAttributes($response))[$localeName][static::ATTRIBUTE_DESCRIPTION],
        );
    }

    public function testGivenAMerchantUrlWithoutTheLocalePrefixWhenPatchOwnProfileThenThePrefixIsPrepended(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));
        $localeName = $this->tester->getStoreLocaleNames($merchantTransfer)[0];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_MERCHANT_URLS => [
                    [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_URL => $this->buildMerchantUrlSuffix($merchantTransfer)],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $this->buildMerchantUrl($localeName, $merchantTransfer),
            $this->getMerchantUrlsByLocaleName($this->getResourceAttributes($response))[$localeName][static::ATTRIBUTE_URL],
        );
    }

    public function testGivenAMerchantUrlWithTheLocalePrefixWhenPatchOwnProfileThenItIsStoredAsIs(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));
        $localeName = $this->tester->getStoreLocaleNames($merchantTransfer)[0];
        $merchantUrl = $this->buildMerchantUrl($localeName, $merchantTransfer);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_MERCHANT_URLS => [
                    [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_URL => $merchantUrl],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $merchantUrl,
            $this->getMerchantUrlsByLocaleName($this->getResourceAttributes($response))[$localeName][static::ATTRIBUTE_URL],
        );
    }

    public function testGivenAMerchantUrlAlreadyUsedByAnotherMerchantWhenPatchOwnProfileThenItRespondsUnprocessable(): void
    {
        // Arrange
        $ownerMerchantTransfer = $this->tester->haveMerchantWithProfile();
        $localeName = $this->tester->getStoreLocaleNames($ownerMerchantTransfer)[0];
        $takenMerchantUrl = $this->buildMerchantUrl($localeName, $ownerMerchantTransfer);
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($ownerMerchantTransfer));
        $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_MERCHANT_URLS => [
                    [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_URL => $takenMerchantUrl],
                ],
            ]),
        );
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([
                static::ATTRIBUTE_MERCHANT_URLS => [
                    [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_URL => $takenMerchantUrl],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAContactPersonTitleOutsideTheEnumWhenPatchThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody(['contactPersonTitle' => 'Captain']),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenABackOfficeUserWhenPatchAnyProfileByReferenceThenItIsUpdated(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(static::PUBLIC_PHONE, $this->getResourceAttributes($response)[static::ATTRIBUTE_PUBLIC_PHONE]);
        $this->assertSame(
            static::PUBLIC_PHONE,
            $this->tester->findMerchantProfile($merchantTransfer->getMerchantReferenceOrFail())->getPublicPhone(),
        );
    }

    public function testGivenMerchantFieldsWhenPatchByReferenceThenTheyAreIgnored(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantProfileRequestBody([
                static::ATTRIBUTE_NAME => static::MERCHANT_NAME,
                static::ATTRIBUTE_IS_ACTIVE => false,
                static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame(static::PUBLIC_PHONE, $attributes[static::ATTRIBUTE_PUBLIC_PHONE]);
        $this->assertSame($merchantTransfer->getName(), $attributes[static::ATTRIBUTE_NAME], 'Merchant fields are read-only on the Back Office resource.');
        $this->assertTrue($attributes[static::ATTRIBUTE_IS_ACTIVE]);
    }

    public function testGivenAPatchWhenTheProfileIsReadAgainThenBothRepresentationsMatch(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();
        $merchantProfileUrl = $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail());

        // Act
        $patchResponse = $this->handleApiRequest(
            'PATCH',
            $merchantProfileUrl,
            $this->tester->buildMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );
        $getResponse = $this->handleApiRequest('GET', $merchantProfileUrl);

        // Assert
        $this->assertRespondsWithStatus($patchResponse, Response::HTTP_OK);
        $this->assertRespondsWithStatus($getResponse, Response::HTTP_OK);
        $this->assertSame(
            $this->getResourceAttributes($getResponse),
            $this->getResourceAttributes($patchResponse),
            'A PATCH returns the same representation a subsequent GET does.',
        );
    }

    public function testGivenAGetThenTheStoresOfTheMerchantAreReturned(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $storeNames = [];

        foreach ($merchantTransfer->getStoreRelationOrFail()->getStores() as $storeTransfer) {
            $storeNames[] = $storeTransfer->getNameOrFail();
        }

        sort($storeNames);

        $this->assertSame($storeNames, $this->getResourceAttributes($response)[static::ATTRIBUTE_STORES]);
    }

    public function testGivenAnAddressFieldWhenPatchThenTheOtherAddressFieldsAreKept(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();
        $merchantProfileUrl = $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail());
        $storedAddress = $this->getResourceAttributes($this->handleApiRequest('GET', $merchantProfileUrl))[static::ATTRIBUTE_ADDRESS];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $merchantProfileUrl,
            $this->tester->buildMerchantProfileRequestBody([
                static::ATTRIBUTE_ADDRESS => [static::ATTRIBUTE_CITY => static::CITY_PATCHED],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $address = $this->getResourceAttributes($response)[static::ATTRIBUTE_ADDRESS];
        $this->assertSame(static::CITY_PATCHED, $address[static::ATTRIBUTE_CITY]);
        $this->assertSame($storedAddress[static::ATTRIBUTE_ZIP_CODE], $address[static::ATTRIBUTE_ZIP_CODE], 'A field the payload omits keeps its stored value.');
        $this->assertSame($storedAddress[static::ATTRIBUTE_COUNTRY_ISO2_CODE], $address[static::ATTRIBUTE_COUNTRY_ISO2_CODE]);
        $this->assertCount(
            1,
            $this->tester->findMerchantProfile($merchantTransfer->getMerchantReferenceOrFail())->getAddressCollection(),
            'Updating the address must not add a row.',
        );
    }

    public function testGivenAnUnknownCountryWhenPatchTheAddressThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantProfileRequestBody([
                static::ATTRIBUTE_ADDRESS => [static::ATTRIBUTE_COUNTRY_ISO2_CODE => MerchantProfileBackendApiHelper::UNKNOWN_ISO2_CODE],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorDetail($response, Response::HTTP_UNPROCESSABLE_ENTITY, MerchantProfileConfig::RESPONSE_DETAILS_UNKNOWN_COUNTRY);
    }

    public function testGivenNoAddressInThePayloadWhenPatchThenTheStoredAddressIsUntouched(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();
        $merchantProfileUrl = $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail());
        $storedAddress = $this->getResourceAttributes($this->handleApiRequest('GET', $merchantProfileUrl))[static::ATTRIBUTE_ADDRESS];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $merchantProfileUrl,
            $this->tester->buildMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($storedAddress, $this->getResourceAttributes($response)[static::ATTRIBUTE_ADDRESS]);
    }

    /**
     * @return array<string, string>
     */
    protected function buildStoredGlossaryAttributeValues(): array
    {
        return [
            MerchantProfileGlossaryAttributeValuesTransfer::DESCRIPTION_GLOSSARY_KEY => static::DESCRIPTION_STORED,
            MerchantProfileGlossaryAttributeValuesTransfer::IMPRINT_GLOSSARY_KEY => static::IMPRINT_STORED,
            MerchantProfileGlossaryAttributeValuesTransfer::BANNER_URL_GLOSSARY_KEY => static::BANNER_URL_STORED,
        ];
    }

    protected function buildMerchantUrl(string $localeName, MerchantTransfer $merchantTransfer): string
    {
        return sprintf(static::MERCHANT_URL_PREFIX_FORMAT, $this->getLanguageCode($localeName), $this->buildMerchantUrlSuffix($merchantTransfer));
    }

    protected function buildMerchantUrlSuffix(MerchantTransfer $merchantTransfer): string
    {
        return strtolower($merchantTransfer->getMerchantReferenceOrFail());
    }

    protected function skipUnlessDynamicStoreModeIsEnabled(): void
    {
        if ($this->tester->isDynamicStoreEnabled()) {
            return;
        }

        $this->markTestSkipped('Requires SPRYKER_DYNAMIC_STORE_MODE=true: without it every configured locale belongs to every store.');
    }

    protected function getLanguageCode(string $localeName): string
    {
        return explode('_', $localeName)[0];
    }
}
