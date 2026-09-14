<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OauthBackendApi\BackendApi\Integration;

use Generated\Shared\DataBuilder\MerchantProfileBuilder;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\UserTransfer;
use PyzTest\Glue\OauthBackendApi\OauthBackendApiBackendApiIntegrationTester;
use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OauthBackendApi
 * @group BackendApi
 * @group Integration
 * @group CreateTokenBackendApiTest
 * Add your own group annotations below this line
 */
class CreateTokenBackendApiTest extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string URL_TOKEN = '/token';

    protected const string RESOURCE_TYPE = 'tokens';

    protected const string PASSWORD = 'Change123!';

    protected const string WRONG_PASSWORD = 'not-the-password';

    protected const string SCOPE_BACK_OFFICE_USER = 'back-office-user';

    protected const string SCOPE_MERCHANT_USER = 'merchant-user';

    protected const string TOKEN_TYPE_BEARER = 'Bearer';

    protected const string JWT_CLAIM_SCOPES = 'scopes';

    protected OauthBackendApiBackendApiIntegrationTester $tester;

    public function testGivenBackOfficeUserCredentialsWhenCreatingTokenThenIssuesTokenWithBackOfficeUserScope(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);

        // Act
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $this->buildPasswordGrantBody($userTransfer->getUsernameOrFail(), static::PASSWORD));

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string)$response->getContent());
        $attributes = $this->decodeJsonApi($response)['data']['attributes'];
        $this->assertNotEmpty($attributes['accessToken']);
        $this->assertNotEmpty($attributes['refreshToken']);
        $this->assertSame(static::TOKEN_TYPE_BEARER, $attributes['tokenType']);
        $this->assertGreaterThan(0, $attributes['expiresIn']);
        $this->assertContains(static::SCOPE_BACK_OFFICE_USER, $this->decodeScopes($attributes['accessToken']));
        $this->assertNotContains(static::SCOPE_MERCHANT_USER, $this->decodeScopes($attributes['accessToken']));
    }

    public function testGivenMerchantUserCredentialsWhenCreatingTokenThenIssuesTokenWithMerchantUserScope(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);
        $this->tester->haveMerchantUser(
            $this->tester->haveMerchant([MerchantTransfer::MERCHANT_PROFILE => (new MerchantProfileBuilder())->build()->toArray()]),
            $userTransfer,
        );

        // Act
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $this->buildPasswordGrantBody($userTransfer->getUsernameOrFail(), static::PASSWORD));

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string)$response->getContent());
        $scopes = $this->decodeScopes($this->decodeJsonApi($response)['data']['attributes']['accessToken']);
        $this->assertContains(static::SCOPE_MERCHANT_USER, $scopes);
        $this->assertNotContains(static::SCOPE_BACK_OFFICE_USER, $scopes);
    }

    public function testGivenWrongPasswordWhenCreatingTokenThenRespondsUnauthorized(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);

        // Act
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $this->buildPasswordGrantBody($userTransfer->getUsernameOrFail(), static::WRONG_PASSWORD));

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode(), (string)$response->getContent());
        $this->assertNotEmpty($this->decodeJsonApi($response)['errors'][0]['code']);
    }

    public function testGivenNoPasswordWhenCreatingTokenThenRespondsUnprocessable(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);

        // Act
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $this->buildRequestBody(['username' => $userTransfer->getUsernameOrFail()]));

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
    }

    protected function buildPasswordGrantBody(string $username, string $password): string
    {
        return $this->buildRequestBody(['username' => $username, 'password' => $password]);
    }

    /**
     * @param array<string, string> $attributes
     */
    protected function buildRequestBody(array $attributes): string
    {
        return (string)json_encode(['data' => ['type' => static::RESOURCE_TYPE, 'attributes' => $attributes]]);
    }

    /**
     * @return array<string>
     */
    protected function decodeScopes(string $accessToken): array
    {
        $payload = explode('.', $accessToken)[1];
        $claims = json_decode((string)base64_decode(strtr($payload, '-_', '+/')), true);

        return $claims[static::JWT_CLAIM_SCOPES] ?? [];
    }
}
