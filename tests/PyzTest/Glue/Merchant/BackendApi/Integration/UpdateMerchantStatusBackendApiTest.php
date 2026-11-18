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
 * @group UpdateMerchantStatusBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateMerchantStatusBackendApiTest extends AbstractMerchantBackendApiTestCase
{
    public function testGivenNoAuthenticationWhenUpdateMerchantStatusThenItRespondsUnauthorized(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAMerchantWaitingForApprovalWhenApprovedThenItRespondsWithTheApprovedMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            MerchantConfig::STATUS_APPROVED,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_STATUS] ?? null,
        );
    }

    public function testGivenAMerchantWaitingForApprovalWhenDeniedThenItRespondsWithTheDeniedMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_DENIED]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            MerchantConfig::STATUS_DENIED,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_STATUS] ?? null,
        );
    }

    public function testGivenADeniedMerchantWhenApprovedThenItRespondsWithTheApprovedMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveDeniedMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            MerchantConfig::STATUS_APPROVED,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_STATUS] ?? null,
        );
    }

    /**
     * The change has to be persisted, which the response of the update alone does not prove: it is
     * built from the transfer the processor wrote, not from a re-read.
     */
    public function testGivenAMerchantWaitingForApprovalWhenApprovedThenTheStatusIsPersisted(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED]),
        );
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            MerchantConfig::STATUS_APPROVED,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_STATUS] ?? null,
        );
    }

    /**
     * Repeating the status the merchant already has is not a transition, so it is accepted and leaves
     * the merchant as it is.
     */
    public function testGivenAnApprovedMerchantWhenApprovedAgainThenItRespondsWithTheApprovedMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveApprovedMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            MerchantConfig::STATUS_APPROVED,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_STATUS] ?? null,
        );
    }

    /**
     * An approved merchant cannot go back to waiting for approval, so the transition is refused
     * rather than written to the column.
     */
    public function testGivenATransitionThatIsNotAllowedWhenUpdateMerchantStatusThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveApprovedMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([
                static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_WAITING_FOR_APPROVAL,
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_MERCHANT_VALIDATION,
        );
    }

    /**
     * The status is bounded, so a value outside the vocabulary has to be refused rather than written
     * to the column and left for the collection filter to trip over.
     */
    public function testGivenAnUnsupportedStatusWhenUpdateMerchantStatusThenItRespondsUnprocessable(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => static::UNSUPPORTED_STATUS]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * An update that carries no status has to leave it alone, rather than reset it to the default of
     * a freshly created merchant.
     */
    public function testGivenNoStatusWhenUpdateMerchantThenTheStatusIsPreserved(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveApprovedMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_NAME => static::UPDATED_NAME]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            MerchantConfig::STATUS_APPROVED,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_STATUS] ?? null,
        );
    }

    public function testGivenAnUnknownReferenceWhenUpdateMerchantStatusThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl(static::UNKNOWN_MERCHANT_REFERENCE),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            static::RESPONSE_CODE_MERCHANT_NOT_FOUND,
        );
    }

    /**
     * Approving a merchant grants it access, so an operator the ACL refuses has to be answered 403
     * rather than allowed through on the token alone.
     */
    public function testGivenAnOperatorTheAclRefusesWhenUpdateMerchantStatusThenItRespondsForbidden(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantRequestBody([static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_APPROVED]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
