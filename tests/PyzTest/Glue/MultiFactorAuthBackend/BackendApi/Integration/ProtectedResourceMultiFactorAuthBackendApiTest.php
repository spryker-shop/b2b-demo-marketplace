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
use Symfony\Component\HttpFoundation\Response;

/**
 * The `X-MFA-Code` step-up on write requests to the resources listed in
 * `MultiFactorAuthConfig::getMultiFactorAuthProtectedBackendResources()`, over a booted GLUE_BACKEND kernel.
 *
 * The project protects only a legacy resource, so the suite protects `multi-factor-auth-trigger` for the
 * duration of a test: the resource needs nothing but a user, and its own success answer (204) shows that the
 * step-up let the request through.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group MultiFactorAuthBackend
 * @group BackendApi
 * @group Integration
 * @group ProtectedResourceMultiFactorAuthBackendApiTest
 * Add your own group annotations below this line
 */
class ProtectedResourceMultiFactorAuthBackendApiTest extends AbstractMultiFactorAuthBackendApiTestCase
{
    public function testGivenAProtectedResourceAndAnActivatedTypeWhenWritingWithoutACodeThenItIsForbidden(): void
    {
        // Arrange
        $this->tester->protectBackendResources([MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TRIGGER]);
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TRIGGER,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TRIGGER,
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

    public function testGivenAProtectedResourceAndAnActivatedTypeWhenWritingWithAValidCodeThenItPasses(): void
    {
        // Arrange
        $this->tester->protectBackendResources([MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TRIGGER]);
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $code = $this->tester->haveSentUserMultiFactorAuthCode($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TRIGGER,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TRIGGER,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
            $this->createMultiFactorAuthCodeHeader($code),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
    }

    public function testGivenAProtectedResourceAndAnActivatedTypeWhenWritingWithAWrongCodeThenItIsForbidden(): void
    {
        // Arrange
        $this->tester->protectBackendResources([MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TRIGGER]);
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $this->tester->haveSentUserMultiFactorAuthCode($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TRIGGER,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TRIGGER,
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
    }

    /**
     * Without an activated type the step-up has nothing to check, so the resource answers as usual: here the
     * trigger rejects the type that is not activated (400/5906) instead of asking for a code (403/5900).
     */
    public function testGivenAProtectedResourceAndNoActivatedTypeWhenWritingWithoutACodeThenNoCodeIsRequired(): void
    {
        // Arrange
        $this->tester->protectBackendResources([MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TRIGGER]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->postMultiFactorAuth(
            MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TRIGGER,
            MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TRIGGER,
            [static::ATTRIBUTE_TYPE => MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL],
        );

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_BAD_REQUEST,
            MultiFactorAuthConfig::ERROR_CODE_MULTI_FACTOR_AUTH_TYPE_NOT_FOUND,
            MultiFactorAuthConfig::ERROR_MESSAGE_MULTI_FACTOR_AUTH_TYPE_NOT_FOUND_FOR_USER,
        );
    }

    public function testGivenAProtectedResourceWhenReadingWithoutACodeThenNoCodeIsRequired(): void
    {
        // Arrange
        $this->tester->protectBackendResources([MultiFactorAuthConfig::RESOURCE_MULTI_FACTOR_AUTH_TYPES]);
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->handleApiRequest('GET', MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPES);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
    }
}
