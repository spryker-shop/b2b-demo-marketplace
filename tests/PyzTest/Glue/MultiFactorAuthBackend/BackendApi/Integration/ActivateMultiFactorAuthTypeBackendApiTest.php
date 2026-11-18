<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\MultiFactorAuthBackend\BackendApi\Integration;

use PyzTest\Glue\MultiFactorAuthBackend\AbstractMultiFactorAuthBackendApiTestCase;
use PyzTest\Glue\MultiFactorAuthBackend\Helper\MultiFactorAuthBackendApiHelper;
use Spryker\Glue\MultiFactorAuth\MultiFactorAuthConfig;
use Spryker\Shared\MultiFactorAuth\MultiFactorAuthConstants;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /multi-factor-auth-type-activate` over a booted GLUE_BACKEND kernel.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group MultiFactorAuthBackend
 * @group BackendApi
 * @group Integration
 * @group ActivateMultiFactorAuthTypeBackendApiTest
 * Add your own group annotations below this line
 */
class ActivateMultiFactorAuthTypeBackendApiTest extends AbstractMultiFactorAuthBackendApiTestCase
{
    public function testGivenARegisteredTypeWhenActivateThenItIsPendingAndACodeIsIssued(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertSame(MultiFactorAuthConstants::STATUS_PENDING_ACTIVATION, $this->tester->findUserMultiFactorAuthStatus($userTransfer));
        $this->assertNotNull($this->tester->findLatestUserMultiFactorAuthCode($userTransfer));
    }

    public function testGivenAMerchantUserWhenActivateThenItIsPending(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->actingAsMerchantUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertSame(MultiFactorAuthConstants::STATUS_PENDING_ACTIVATION, $this->tester->findUserMultiFactorAuthStatus($userTransfer));
    }

    public function testGivenAnUnknownTypeWhenActivateThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::UNKNOWN_MULTI_FACTOR_AUTH_TYPE],
        );

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_BAD_REQUEST,
            MultiFactorAuthConfig::ERROR_CODE_MULTI_FACTOR_AUTH_TYPE_NOT_FOUND,
            MultiFactorAuthConfig::ERROR_MESSAGE_MULTI_FACTOR_AUTH_TYPE_NOT_FOUND,
        );
    }

    public function testGivenNoTypeWhenActivateThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            [],
        );

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_BAD_REQUEST,
            MultiFactorAuthConfig::ERROR_CODE_MULTI_FACTOR_AUTH_TYPE_MISSING,
            MultiFactorAuthConfig::ERROR_MESSAGE_MULTI_FACTOR_AUTH_TYPE_MISSING,
        );
    }

    public function testGivenAnActivatedTypeWhenActivateItAgainThenItIsRejected(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_ACTIVATE,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
        );

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_BAD_REQUEST,
            MultiFactorAuthConfig::ERROR_CODE_MULTI_FACTOR_AUTH_VERIFY_FAILED,
            MultiFactorAuthConfig::ERROR_MESSAGE_MULTI_FACTOR_AUTH_VERIFY_FAILED,
        );
    }
}
