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
 * `POST /multi-factor-auth-type-deactivate` over a booted GLUE_BACKEND kernel.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group MultiFactorAuthBackend
 * @group BackendApi
 * @group Integration
 * @group DeactivateMultiFactorAuthTypeBackendApiTest
 * Add your own group annotations below this line
 */
class DeactivateMultiFactorAuthTypeBackendApiTest extends AbstractMultiFactorAuthBackendApiTestCase
{
    public function testGivenAnActivatedTypeAndItsCodeWhenDeactivateThenTheTypeIsNoLongerActive(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $code = $this->tester->haveSentUserMultiFactorAuthCode($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
            $this->createMultiFactorAuthCodeHeader($code),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertNotSame(MultiFactorAuthConstants::STATUS_ACTIVE, $this->tester->findUserMultiFactorAuthStatus($userTransfer));
    }

    public function testGivenNoActivatedTypeWhenDeactivateThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
            $this->createMultiFactorAuthCodeHeader(MultiFactorAuthBackendApiHelper::INVALID_MULTI_FACTOR_AUTH_CODE),
        );

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_BAD_REQUEST,
            MultiFactorAuthConfig::ERROR_CODE_MULTI_FACTOR_AUTH_TYPE_NOT_FOUND,
            MultiFactorAuthConfig::ERROR_MESSAGE_MULTI_FACTOR_AUTH_TYPE_NOT_FOUND_FOR_USER,
        );
    }

    public function testGivenAnActivatedTypeAndAWrongCodeWhenDeactivateThenItIsForbidden(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $this->tester->haveSentUserMultiFactorAuthCode($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
            $this->createMultiFactorAuthCodeHeader(MultiFactorAuthBackendApiHelper::INVALID_MULTI_FACTOR_AUTH_CODE),
        );

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_FORBIDDEN,
            MultiFactorAuthConfig::ERROR_CODE_MULTI_FACTOR_AUTH_CODE_INVALID,
            MultiFactorAuthConfig::ERROR_MESSAGE_MULTI_FACTOR_AUTH_CODE_INVALID,
        );
        $this->assertSame(MultiFactorAuthConstants::STATUS_ACTIVE, $this->tester->findUserMultiFactorAuthStatus($userTransfer));
    }

    public function testGivenNoCodeHeaderWhenDeactivateThenItIsForbidden(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
        );

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_FORBIDDEN,
            MultiFactorAuthConfig::ERROR_CODE_MULTI_FACTOR_AUTH_CODE_MISSING,
            MultiFactorAuthConfig::ERROR_MESSAGE_MULTI_FACTOR_AUTH_CODE_MISSING,
        );
    }
}
