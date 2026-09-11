<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OauthBackendApi\BackendApi\Integration;

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
 * @group RefreshTokenBackendApiTest
 * Add your own group annotations below this line
 */
class RefreshTokenBackendApiTest extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string URL_TOKEN = '/token';

    protected const string URL_REFRESH_TOKENS = '/refresh-tokens';

    protected const string RESOURCE_TYPE_TOKENS = 'tokens';

    protected const string RESOURCE_TYPE_REFRESH_TOKENS = 'refresh-tokens';

    protected const string PASSWORD = 'Change123!';

    protected const string SCOPE_BACK_OFFICE_USER = 'back-office-user';

    protected const string JWT_CLAIM_SCOPES = 'scopes';

    protected OauthBackendApiBackendApiIntegrationTester $tester;

    public function testGivenIssuedRefreshTokenWhenRefreshingThenIssuesNewTokenPair(): void
    {
        // Arrange
        $issued = $this->issueTokenPair();

        // Act
        $response = $this->handleApiRequest('POST', static::URL_REFRESH_TOKENS, $this->buildRequestBody(static::RESOURCE_TYPE_REFRESH_TOKENS, ['refreshToken' => $issued['refreshToken']]));

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string)$response->getContent());
        $refreshed = $this->decodeJsonApi($response)['data']['attributes'];
        $this->assertNotEmpty($refreshed['accessToken']);
        $this->assertNotEmpty($refreshed['refreshToken']);
        $this->assertNotSame($issued['refreshToken'], $refreshed['refreshToken']);
        $this->assertSame('Bearer', $refreshed['tokenType']);
        $this->assertContains(static::SCOPE_BACK_OFFICE_USER, $this->decodeScopes($refreshed['accessToken']));
    }

    public function testGivenAlreadyUsedRefreshTokenWhenRefreshingThenRespondsUnauthorized(): void
    {
        // Arrange
        $issued = $this->issueTokenPair();
        $this->handleApiRequest('POST', static::URL_REFRESH_TOKENS, $this->buildRequestBody(static::RESOURCE_TYPE_REFRESH_TOKENS, ['refreshToken' => $issued['refreshToken']]));

        // Act
        $response = $this->handleApiRequest('POST', static::URL_REFRESH_TOKENS, $this->buildRequestBody(static::RESOURCE_TYPE_REFRESH_TOKENS, ['refreshToken' => $issued['refreshToken']]));

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testGivenUnknownRefreshTokenWhenRefreshingThenRespondsUnauthorized(): void
    {
        // Act
        $response = $this->handleApiRequest('POST', static::URL_REFRESH_TOKENS, $this->buildRequestBody(static::RESOURCE_TYPE_REFRESH_TOKENS, ['refreshToken' => 'not-a-refresh-token']));

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testGivenNoRefreshTokenWhenRefreshingThenRespondsUnprocessable(): void
    {
        // Act
        $response = $this->handleApiRequest('POST', static::URL_REFRESH_TOKENS, $this->buildRequestBody(static::RESOURCE_TYPE_REFRESH_TOKENS, []));

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @return array<string, mixed>
     */
    protected function issueTokenPair(): array
    {
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $this->buildRequestBody(static::RESOURCE_TYPE_TOKENS, ['username' => $userTransfer->getUsernameOrFail(), 'password' => static::PASSWORD]));

        return $this->decodeJsonApi($response)['data']['attributes'];
    }

    /**
     * @param array<string, string> $attributes
     */
    protected function buildRequestBody(string $type, array $attributes): string
    {
        return (string)json_encode(['data' => ['type' => $type, 'attributes' => (object)$attributes]]);
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
