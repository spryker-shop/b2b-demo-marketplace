<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Merchant\BackendApi\Integration;

use PyzTest\Glue\Merchant\AbstractMerchantBackendApiTestCase;
use Spryker\Zed\Merchant\MerchantConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Merchant
 * @group BackendApi
 * @group Integration
 * @group CreateMerchantBackendApiTest
 * Add your own group annotations below this line
 */
class CreateMerchantBackendApiTest extends AbstractMerchantBackendApiTestCase
{
    protected const string UNKNOWN_STORE_NAME = 'STORE-DOES-NOT-EXIST';

    protected const string UNKNOWN_LOCALE_NAME = 'xx_XX';

    protected const int OVER_LONG_URL_LENGTH = 256;

    public function testGivenNoAuthenticationWhenCreateMerchantThenItRespondsUnauthorized(): void
    {
        // Arrange
        $body = $this->tester->buildMerchantRequestBody($this->tester->buildValidMerchantAttributes());

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('POST', $this->tester->getMerchantCollectionUrl(), $body);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenValidAttributesWhenCreateMerchantThenItRespondsWithTheCreatedMerchant(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertSame($attributes[static::ATTRIBUTE_MERCHANT_REFERENCE], $this->getResourceId($response));
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_NAME => $attributes[static::ATTRIBUTE_NAME],
                static::ATTRIBUTE_EMAIL => $attributes[static::ATTRIBUTE_EMAIL],
                static::ATTRIBUTE_REGISTRATION_NUMBER => $attributes[static::ATTRIBUTE_REGISTRATION_NUMBER],
                static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_WAITING_FOR_APPROVAL,
                static::ATTRIBUTE_IS_ACTIVE => false,
            ],
            $this->getResourceAttributes($response),
        );
    }

    /**
     * The merchant has to be readable afterwards, which the response alone does not prove: the
     * processor answers from the transfer it just wrote, not from a re-read.
     */
    public function testGivenValidAttributesWhenCreateMerchantThenTheMerchantIsPersisted(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes();
        $this->tester->actingAsUser();

        // Act
        $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantUrl((string)$attributes[static::ATTRIBUTE_MERCHANT_REFERENCE]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $attributes[static::ATTRIBUTE_NAME],
            $this->getResourceAttributes($response)[static::ATTRIBUTE_NAME] ?? null,
        );
    }

    /**
     * `stores` is optional, and a merchant created without one must not reach
     * {@see \Spryker\Zed\Merchant\Business\Creator\MerchantCreator::assertDefaultMerchantRequirements()}
     * without a store relation - that would be a 500 rather than a created merchant.
     */
    public function testGivenNoStoresWhenCreateMerchantThenItRespondsWithTheCreatedMerchant(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertSame([], $this->getResourceAttributes($response)[static::ATTRIBUTE_STORES] ?? null);
    }

    public function testGivenStoresWhenCreateMerchantThenTheMerchantIsAssignedToThem(): void
    {
        // Arrange
        $storeName = $this->tester->haveStore()->getNameOrFail();
        $attributes = $this->tester->buildValidMerchantAttributes([static::ATTRIBUTE_STORES => [$storeName]]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertSame([$storeName], $this->getResourceAttributes($response)[static::ATTRIBUTE_STORES] ?? null);
    }

    public function testGivenAStatusAndActivityWhenCreateMerchantThenTheyAreApplied(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED,
            static::ATTRIBUTE_IS_ACTIVE => true,
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED,
                static::ATTRIBUTE_IS_ACTIVE => true,
            ],
            $this->getResourceAttributes($response),
        );
    }

    /**
     * The status is bounded, so a value outside the vocabulary has to be refused rather than written
     * to the column and left for the collection filter to trip over.
     */
    public function testGivenAnUnsupportedStatusWhenCreateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_STATUS => static::UNSUPPORTED_STATUS,
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenMerchantUrlsWhenCreateMerchantThenTheyAreReturnedAndPersisted(): void
    {
        // Arrange
        [$merchantUrls, $merchantUrl] = $this->tester->buildMerchantUrlsForEveryLocaleWithOneOverridden(
            '/' . uniqid('merchant-ba-'),
        );
        $attributes = $this->tester->buildValidMerchantAttributes([static::ATTRIBUTE_MERCHANT_URLS => $merchantUrls]);
        $this->tester->actingAsUser();

        // Act
        $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantUrl((string)$attributes[static::ATTRIBUTE_MERCHANT_REFERENCE]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains(
            $this->tester->buildExpectedPersistedMerchantUrl($merchantUrl),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_MERCHANT_URLS] ?? [],
        );
    }

    /**
     * `stores` is optional, so a merchant created without one has no store-specific locale to cover -
     * the Backend API must not reject an empty URL collection in that case.
     */
    public function testGivenNoMerchantUrlsAndNoStoresWhenCreateMerchantThenItRespondsWithTheCreatedMerchant(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([static::ATTRIBUTE_MERCHANT_URLS => []]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
    }

    /**
     * A URL is required for every locale of every store the merchant is assigned to - a merchant
     * created through the API without one must not silently skip the storefront presence the store
     * requires.
     */
    public function testGivenAStoreAndAMissingLocaleUrlWhenCreateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $storeName = $this->tester->getStoreName();
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_STORES => [$storeName],
            static::ATTRIBUTE_MERCHANT_URLS => $this->tester->buildMerchantUrlsMissingALocaleForStore($storeName),
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_MISSING_MERCHANT_URL_LOCALE,
        );
    }

    /**
     * A blank URL is refused rather than stored as an empty `spy_url` row, which would collide with
     * the next merchant to send one on the unique index - a 500 far away from the request at fault.
     */
    public function testGivenABlankMerchantUrlWhenCreateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_MERCHANT_URLS => [$this->tester->buildMerchantUrl('')],
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_MERCHANT_VALIDATION,
        );
    }

    /**
     * `spy_url.url` is a `VARCHAR(255)`, so an over-long URL has to be reported as a field the caller
     * can shorten rather than as a write that failed deep inside the persistence layer.
     */
    public function testGivenAnOverLongMerchantUrlWhenCreateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_MERCHANT_URLS => [
                $this->tester->buildMerchantUrl('/' . str_repeat('a', static::OVER_LONG_URL_LENGTH)),
            ],
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_MERCHANT_VALIDATION,
        );
    }

    public function testGivenAnUnknownLocaleWhenCreateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_MERCHANT_URLS => [
                $this->tester->buildMerchantUrl(null, static::UNKNOWN_LOCALE_NAME),
            ],
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_UNKNOWN_LOCALE,
        );
    }

    public function testGivenABlankNameWhenCreateMerchantThenItRespondsWithAValidationError(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([static::ATTRIBUTE_NAME => '']);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_NAME);
    }

    public function testGivenAMalformedEmailWhenCreateMerchantThenItRespondsWithAValidationError(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([static::ATTRIBUTE_EMAIL => 'not-an-email']);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_EMAIL);
    }

    public function testGivenAnAlreadyUsedEmailWhenCreateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $existingMerchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_EMAIL => $existingMerchantTransfer->getEmailOrFail(),
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_MERCHANT_VALIDATION,
        );
    }

    public function testGivenAnAlreadyUsedMerchantReferenceWhenCreateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $existingMerchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_MERCHANT_REFERENCE => $existingMerchantTransfer->getMerchantReferenceOrFail(),
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_MERCHANT_VALIDATION,
        );
    }

    public function testGivenAnUnknownStoreWhenCreateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidMerchantAttributes([
            static::ATTRIBUTE_STORES => [static::UNKNOWN_STORE_NAME],
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_UNKNOWN_STORE,
        );
    }

    public function testGivenAnInvalidTokenWhenCreateMerchantThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($this->tester->buildValidMerchantAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Creating a merchant is behind the ACL as well as behind the token, so an operator the ACL
     * refuses has to be answered 403 rather than allowed through on the token alone.
     */
    public function testGivenAnOperatorTheAclRefusesWhenCreateMerchantThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getMerchantCollectionUrl(),
            $this->tester->buildMerchantRequestBody($this->tester->buildValidMerchantAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
