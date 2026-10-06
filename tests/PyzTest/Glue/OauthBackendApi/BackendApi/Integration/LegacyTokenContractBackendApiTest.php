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
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OauthBackendApi
 * @group BackendApi
 * @group Integration
 * @group LegacyTokenContractBackendApiTest
 * Add your own group annotations below this line
 */
class LegacyTokenContractBackendApiTest extends BackendApiTestCase
{
    protected const string URL_TOKEN = '/token';

    protected const string PASSWORD = 'Change123!';

    protected const string CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    protected const string ERROR_CODE_INVALID_GRANT = 'invalid_grant';

    protected OauthBackendApiBackendApiIntegrationTester $tester;

    public function testGivenRobotStyleJsonTextUnderFormContentTypeWhenCreatingTokenThenAnswersFlatBody(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);
        $body = sprintf('{"grantType": "password","username": "%s","password": "%s"}', $userTransfer->getUsernameOrFail(), static::PASSWORD);

        // Act
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $body, ['Content-Type' => static::CONTENT_TYPE_FORM]);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $decoded = json_decode((string)$response->getContent(), true);
        $this->assertNotEmpty($decoded['access_token']);
        $this->assertNotEmpty($decoded['refresh_token']);
        $this->assertSame('Bearer', $decoded['token_type']);
        $this->assertArrayNotHasKey('data', $decoded);
    }

    public function testGivenCypressStyleFormFieldsWhenCreatingTokenThenAnswersFlatBody(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);
        $body = http_build_query(['grantType' => 'password', 'username' => $userTransfer->getUsernameOrFail(), 'password' => static::PASSWORD]);

        // Act
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $body, ['Content-Type' => static::CONTENT_TYPE_FORM]);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertNotEmpty(json_decode((string)$response->getContent(), true)['access_token']);
    }

    public function testGivenWrongPasswordInLegacyRequestWhenCreatingTokenThenAnswersLegacyErrorList(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);
        $body = http_build_query(['grantType' => 'password', 'username' => $userTransfer->getUsernameOrFail(), 'password' => 'not-the-password']);

        // Act
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $body, ['Content-Type' => static::CONTENT_TYPE_FORM]);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string)$response->getContent());
        $decoded = json_decode((string)$response->getContent(), true);
        $this->assertSame(static::ERROR_CODE_INVALID_GRANT, $decoded[0]['code']);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $decoded[0]['status']);
    }

    public function testGivenLegacyRefreshTokenGrantWhenRefreshingThenAnswersNewTokenPair(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);
        $issued = json_decode((string)$this->handleApiRequest(
            'POST',
            static::URL_TOKEN,
            http_build_query(['grantType' => 'password', 'username' => $userTransfer->getUsernameOrFail(), 'password' => static::PASSWORD]),
            ['Content-Type' => static::CONTENT_TYPE_FORM],
        )->getContent(), true);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            static::URL_TOKEN,
            http_build_query(['grant_type' => 'refresh_token', 'refresh_token' => $issued['refresh_token']]),
            ['Content-Type' => static::CONTENT_TYPE_FORM],
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $refreshed = json_decode((string)$response->getContent(), true);
        $this->assertNotEmpty($refreshed['access_token']);
        $this->assertNotSame($issued['refresh_token'], $refreshed['refresh_token']);
    }

    public function testGivenJsonApiRequestWhenCreatingTokenThenTheTokensResourceStillAnswers(): void
    {
        // Arrange
        $userTransfer = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD]);
        $body = (string)json_encode(['data' => ['type' => 'tokens', 'attributes' => ['username' => $userTransfer->getUsernameOrFail(), 'password' => static::PASSWORD]]]);

        // Act
        $response = $this->handleApiRequest('POST', static::URL_TOKEN, $body);

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string)$response->getContent());
        $this->assertNotEmpty(json_decode((string)$response->getContent(), true)['data']['attributes']['accessToken']);
    }
}
