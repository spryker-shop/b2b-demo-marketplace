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
 * @group UpdateMerchantBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateMerchantBackendApiTest extends AbstractMerchantBackendApiTestCase
{
    protected const string UNKNOWN_STORE_NAME = 'STORE-DOES-NOT-EXIST';

    protected const string OTHER_MERCHANT_REFERENCE = 'MER-BA-NOT-THE-ADDRESSED-ONE';

    protected const string UNKNOWN_WAREHOUSE_NAME = 'WAREHOUSE-DOES-NOT-EXIST';

    public function testGivenNoAuthenticationWhenUpdateMerchantThenItRespondsUnauthorized(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenANewNameWhenUpdateMerchantThenItRespondsWithTheUpdatedMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_NAME => static::UPDATED_NAME,
                static::ATTRIBUTE_EMAIL => $merchantTransfer->getEmailOrFail(),
                static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_WAITING_FOR_APPROVAL,
            ],
            $this->getResourceAttributes($response),
        );
    }

    /**
     * A partial update must leave the properties it does not carry alone, which the response of the
     * update itself does not prove - it is built from the transfer the processor merged onto.
     */
    public function testGivenOnlyTheNameWhenUpdateMerchantThenTheOtherPropertiesArePreserved(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_NAME => static::UPDATED_NAME,
                static::ATTRIBUTE_EMAIL => $merchantTransfer->getEmailOrFail(),
                static::ATTRIBUTE_STORES => [$this->tester->getStoreName()],
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenNoRegistrationNumberWhenUpdateMerchantThenTheCurrentOneIsPreserved(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();
        $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_REGISTRATION_NUMBER => 'HRB 12345']),
        );

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame('HRB 12345', $this->getResourceAttributes($response)[static::ATTRIBUTE_REGISTRATION_NUMBER]);
    }

    /**
     * A registration number cannot be unset any other way, so sending `null` explicitly must clear
     * it rather than being treated the same as omitting the property.
     */
    public function testGivenAnExplicitNullRegistrationNumberWhenUpdateMerchantThenItIsCleared(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();
        $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_REGISTRATION_NUMBER => 'HRB 12345']),
        );

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_REGISTRATION_NUMBER => null]),
        );

        // Assert - a null attribute is omitted from the JSON:API response rather than serialized as null.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertNull($this->getResourceAttributes($response)[static::ATTRIBUTE_REGISTRATION_NUMBER] ?? null);
    }

    /**
     * The merchant reference addresses the merchant in the URL, so a reference in the payload is
     * ignored rather than allowed to re-address or rename the merchant.
     */
    public function testGivenAnotherMerchantReferenceWhenUpdateMerchantThenItIsIgnored(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([
                static::ATTRIBUTE_MERCHANT_REFERENCE => static::OTHER_MERCHANT_REFERENCE,
                static::ATTRIBUTE_NAME => static::UPDATED_NAME,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($merchantTransfer->getMerchantReferenceOrFail(), $this->getResourceId($response));
    }

    /**
     * Sending `stores` replaces the whole assignment rather than adding to it.
     */
    public function testGivenAnotherStoreWhenUpdateMerchantThenTheAssignmentIsReplaced(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $secondStoreName = $this->tester->haveStore($this->tester->getSecondStoreName())->getNameOrFail();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STORES => [$secondStoreName]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$secondStoreName], $this->getResourceAttributes($response)[static::ATTRIBUTE_STORES] ?? null);
    }

    public function testGivenIsOpenForRelationRequestWhenUpdateMerchantThenItIsApplied(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_OPEN_FOR_RELATION_REQUEST => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue(
            $this->getResourceAttributes($response)[static::ATTRIBUTE_IS_OPEN_FOR_RELATION_REQUEST] ?? null,
        );
    }

    /**
     * Left out of the payload, the flag keeps its current value rather than resetting to a default -
     * the same contract `stores`/`status`/`isActive` are held to on a partial update.
     */
    public function testGivenNoIsOpenForRelationRequestWhenUpdateMerchantThenTheCurrentValueIsPreserved(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();
        $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_OPEN_FOR_RELATION_REQUEST => true]),
        );

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue(
            $this->getResourceAttributes($response)[static::ATTRIBUTE_IS_OPEN_FOR_RELATION_REQUEST] ?? null,
        );
    }

    /**
     * Merchant URLs upsert per locale, because a merchant URL cannot be deleted: a locale absent from
     * the payload keeps the URL it has, while a locale present in it is rewritten.
     */
    public function testGivenAMerchantUrlWhenUpdateMerchantThenTheUrlOfThatLocaleIsReplaced(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $merchantUrl = $this->tester->buildMerchantUrl();
        $this->tester->actingAsUser();

        // Act
        $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_MERCHANT_URLS => [$merchantUrl]]),
        );
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains(
            $this->tester->buildExpectedPersistedMerchantUrl($merchantUrl),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_MERCHANT_URLS] ?? [],
        );
    }

    /**
     * A URL is required for every locale of every store the merchant is assigned to, even when the
     * PATCH itself does not touch `stores` or `merchantUrls` - a merchant that already has a gap must
     * not be allowed to keep it through an unrelated update.
     */
    public function testGivenAMerchantMissingAStoreLocaleUrlWhenUpdateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithoutUrls();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_MISSING_MERCHANT_URL_LOCALE,
        );
    }

    /**
     * The warehouse assignment is read-only, so a client that sends it gets the assignment the
     * merchant already has rather than what it asked for.
     */
    public function testGivenAReadOnlyAttributeWhenUpdateMerchantThenItIsIgnored(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([
                static::ATTRIBUTE_WAREHOUSES => [static::UNKNOWN_WAREHOUSE_NAME],
                static::ATTRIBUTE_NAME => static::UPDATED_NAME,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $resourceAttributes = $this->getResourceAttributes($response);
        $this->assertSame(static::UPDATED_NAME, $resourceAttributes[static::ATTRIBUTE_NAME] ?? null);
        $this->assertNotContains(
            static::UNKNOWN_WAREHOUSE_NAME,
            $resourceAttributes[static::ATTRIBUTE_WAREHOUSES] ?? [],
        );
    }

    public function testGivenAnUnknownStoreWhenUpdateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STORES => [static::UNKNOWN_STORE_NAME]]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_UNKNOWN_STORE,
        );
    }

    public function testGivenTheEmailOfAnotherMerchantWhenUpdateMerchantThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $otherMerchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([
                static::ATTRIBUTE_EMAIL => $otherMerchantTransfer->getEmailOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_MERCHANT_VALIDATION,
        );
    }

    public function testGivenAMalformedEmailWhenUpdateMerchantThenItRespondsWithAValidationError(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_EMAIL => 'not-an-email']),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_EMAIL);
    }

    public function testGivenAnUnknownReferenceWhenUpdateMerchantThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl(static::UNKNOWN_MERCHANT_REFERENCE),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            static::RESPONSE_CODE_MERCHANT_NOT_FOUND,
        );
    }
}
