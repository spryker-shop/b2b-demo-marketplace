<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\MultiFactorAuthBackend\BackendApi\Integration;

use PyzTest\Glue\MultiFactorAuthBackend\AbstractMultiFactorAuthBackendApiTestCase;
use PyzTest\Glue\MultiFactorAuthBackend\Helper\MultiFactorAuthBackendApiHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /multi-factor-auth-types` over a booted GLUE_BACKEND kernel.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group MultiFactorAuthBackend
 * @group BackendApi
 * @group Integration
 * @group GetMultiFactorAuthTypesBackendApiTest
 * Add your own group annotations below this line
 */
class GetMultiFactorAuthTypesBackendApiTest extends AbstractMultiFactorAuthBackendApiTestCase
{
    protected const string SCOPE_USER = 'user';

    public function testGivenAUserWithoutMultiFactorAuthWhenGetTypesThenTheRegisteredTypeIsDeactivated(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPES);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            static::STATUS_LABEL_DEACTIVATED,
            $this->getStatusByType($response)[MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL] ?? null,
        );
    }

    public function testGivenAUserWithAnActivatedTypeWhenGetTypesThenItIsActivated(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->handleApiRequest('GET', MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPES);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL => static::STATUS_LABEL_ACTIVATED],
            $this->getStatusByType($response),
        );
    }

    public function testGivenAUserWithAPendingTypeWhenGetTypesThenItsActivationIsPending(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->havePendingUserMultiFactorAuth($userTransfer);
        $this->tester->actingAsUser($userTransfer);

        // Act
        $response = $this->handleApiRequest('GET', MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPES);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            static::STATUS_LABEL_PENDING_ACTIVATION,
            $this->getStatusByType($response)[MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL] ?? null,
        );
    }

    public function testGivenAMerchantUserWhenGetTypesThenTheyAreReturned(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser();
        $this->tester->haveActivatedUserMultiFactorAuth($userTransfer);
        $this->tester->actingAsMerchantUser($userTransfer);

        // Act
        $response = $this->handleApiRequest('GET', MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPES);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            static::STATUS_LABEL_ACTIVATED,
            $this->getStatusByType($response)[MultiFactorAuthBackendApiHelper::MULTI_FACTOR_AUTH_TYPE_EMAIL] ?? null,
        );
    }

    public function testGivenATokenWithoutAUserTypeWhenGetTypesThenItIsForbidden(): void
    {
        // Arrange
        $this->tester->actingWithScopes([static::SCOPE_USER]);

        // Act
        $response = $this->handleApiRequest('GET', MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPES);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenAnInvalidTokenWhenGetTypesThenItIsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', MultiFactorAuthBackendApiHelper::URL_MULTI_FACTOR_AUTH_TYPES);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
