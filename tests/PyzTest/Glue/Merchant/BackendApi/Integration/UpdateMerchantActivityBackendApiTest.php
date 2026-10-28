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
 * @group UpdateMerchantActivityBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateMerchantActivityBackendApiTest extends AbstractMerchantBackendApiTestCase
{
    public function testGivenNoAuthenticationWhenActivateMerchantThenItRespondsUnauthorized(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveApprovedMerchant();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_ACTIVE => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInactiveMerchantWhenActivateMerchantThenItRespondsWithTheActiveMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveApprovedMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_ACTIVE => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_IS_ACTIVE => true,
                static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED,
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnActiveMerchantWhenDeactivateMerchantThenItRespondsWithTheInactiveMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithStatus(MerchantConfig::STATUS_APPROVED, true);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_ACTIVE => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertFalse($this->getResourceAttributes($response)[static::ATTRIBUTE_IS_ACTIVE] ?? null);
    }

    /**
     * Activity is a desired end state rather than a transition, so asking for the state the merchant
     * is already in is accepted.
     */
    public function testGivenAnActiveMerchantWhenActivateMerchantThenItRespondsWithTheActiveMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithStatus(MerchantConfig::STATUS_APPROVED, true);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_ACTIVE => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue($this->getResourceAttributes($response)[static::ATTRIBUTE_IS_ACTIVE] ?? null);
    }

    /**
     * The change has to be persisted, which the response of the update alone does not prove: it is
     * built from the transfer the processor wrote, not from a re-read.
     */
    public function testGivenAnInactiveMerchantWhenActivateMerchantThenTheChangeIsPersisted(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveApprovedMerchant();
        $this->tester->actingAsUser();

        // Act
        $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_ACTIVE => true]),
        );
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue($this->getResourceAttributes($response)[static::ATTRIBUTE_IS_ACTIVE] ?? null);
    }

    /**
     * An update that carries no activity has to leave it alone, rather than reset it to the default
     * of a merchant that was never activated.
     */
    public function testGivenNoActivityWhenUpdateMerchantThenTheActivityIsPreserved(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithStatus(MerchantConfig::STATUS_APPROVED, true);
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue($this->getResourceAttributes($response)[static::ATTRIBUTE_IS_ACTIVE] ?? null);
    }

    public function testGivenAnUnknownReferenceWhenActivateMerchantThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl(static::UNKNOWN_MERCHANT_REFERENCE),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_ACTIVE => true]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            static::RESPONSE_CODE_MERCHANT_NOT_FOUND,
        );
    }

    /**
     * Activation grants the merchant its storefront presence, so an operator the ACL refuses has to
     * be answered 403 rather than allowed through on the token alone.
     */
    public function testGivenAnOperatorTheAclRefusesWhenActivateMerchantThenItRespondsForbidden(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveApprovedMerchant();
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_IS_ACTIVE => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
