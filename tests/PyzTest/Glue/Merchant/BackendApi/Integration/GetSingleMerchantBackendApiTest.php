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
 * @group GetSingleMerchantBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleMerchantBackendApiTest extends AbstractMerchantBackendApiTestCase
{
    public function testGivenNoAuthenticationWhenGetMerchantThenItRespondsUnauthorized(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAMerchantWhenGetMerchantThenItRespondsWithTheMerchant(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveWaitingForApprovalMerchant();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($merchantTransfer->getMerchantReferenceOrFail(), $this->getResourceId($response));
        $this->assertAttributesMatch(
            [
                static::ATTRIBUTE_NAME => $merchantTransfer->getNameOrFail(),
                static::ATTRIBUTE_STATUS => MerchantConfig::STATUS_WAITING_FOR_APPROVAL,
                static::ATTRIBUTE_IS_ACTIVE => false,
                static::ATTRIBUTE_STORES => [$this->tester->getStoreName()],
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnUnknownReferenceWhenGetMerchantThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getMerchantUrl(static::UNKNOWN_MERCHANT_REFERENCE));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            static::RESPONSE_CODE_MERCHANT_NOT_FOUND,
        );
    }
}
